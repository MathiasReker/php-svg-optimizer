<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDoctype;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
final class EntityReferenceTest extends TestCase
{
    /**
     * @param non-empty-string $expected
     *
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideResolvesEntityReferencesCases')]
    #[Test]
    public function resolvesEntityReferences(string $content, string $expected): void
    {
        $actual = (new SvgOptimizer(new StringProvider($content)))->optimize()->getContent();

        self::assertSame($expected, $actual);
        self::assertTrue((new \DOMDocument())->loadXML($expected), 'The output must be well-formed without the DOCTYPE.');
    }

    /**
     * @return iterable<string, array{string, non-empty-string}>
     */
    public static function provideResolvesEntityReferencesCases(): iterable
    {
        yield 'Resolves an entity in text content' => [
            '<!DOCTYPE svg [<!ENTITY w "10">]><svg xmlns="http://www.w3.org/2000/svg"><text>a &w; b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a 10 b</text></svg>',
        ];

        yield 'Resolves an entity in an attribute' => [
            '<!DOCTYPE svg [<!ENTITY w "10">]><svg xmlns="http://www.w3.org/2000/svg" width="&w;"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="10"/>',
        ];

        yield 'Resolves an entity in a namespace declaration' => [
            '<!DOCTYPE svg [<!ENTITY ns_x "urn:x">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="&ns_x;"><x:a/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:a/></svg>',
        ];

        yield 'Resolves an entity in a default namespace declaration on a child' => [
            '<!DOCTYPE svg [<!ENTITY ns_x "urn:x">]><svg xmlns="http://www.w3.org/2000/svg"><a xmlns="&ns_x;"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a xmlns="urn:x"/></svg>',
        ];

        yield 'Resolves single-quoted and nested entities' => [
            "<!DOCTYPE svg [<!ENTITY base 'urn:'><!ENTITY ns_x \"&base;x\">]><svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x=\"&ns_x;\"><x:a/></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:a/></svg>',
        ];

        yield 'Resolves character references in an entity and escapes the result' => [
            '<!DOCTYPE svg [<!ENTITY ns_x "urn:a&#38;#38;b&#x3A;c">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="&ns_x;"><x:a/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:a&#38;b:c"><x:a/></svg>',
        ];

        yield 'Keeps a predefined entity in a namespace declaration escaped' => [
            '<!DOCTYPE svg [<!ENTITY ns_x "urn:a&amp;b">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="&ns_x;"><x:a/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:a&#38;b"><x:a/></svg>',
        ];

        yield 'Expands an entity with markup to elements' => [
            '<!DOCTYPE svg [<!ENTITY m "<tspan>b</tspan>">]><svg xmlns="http://www.w3.org/2000/svg"><text>a &m;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a <tspan>b</tspan></text></svg>',
        ];

        yield 'Expands nested entities with markup and mixed content' => [
            '<!DOCTYPE svg [<!ENTITY dot "<circle r=\'1\'/>"><!ENTITY shape "<rect width=\'5\'/><g>&dot;</g>text">]><svg xmlns="http://www.w3.org/2000/svg"><g>&shape;</g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="5"/><g><circle r="1"/></g>text</g></svg>',
        ];

        yield 'Expands an entity whose text is not well-formed markup to text' => [
            '<!DOCTYPE svg [<!ENTITY t "a&#38;#60;b">]><svg xmlns="http://www.w3.org/2000/svg"><title>&t;</title></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a&lt;b</title></svg>',
        ];

        yield 'Expands an external entity to nothing without loading it' => [
            '<!DOCTYPE svg [<!ENTITY ext SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><text>a&ext;b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>ab</text></svg>',
        ];

        yield 'Keeps documents without a DOCTYPE unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><text>a &amp; b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><text>a &amp; b</text></svg>',
        ];

        yield 'Keeps namespace declarations of documents without a DOCTYPE as serialized' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:a&amp;b"><x:a/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:a&#38;b"><x:a/></svg>',
        ];

        yield 'Expands an entity that consists of a reference to another entity' => [
            '<!DOCTYPE svg [<!ENTITY a "&b;"><!ENTITY b "x">]><svg xmlns="http://www.w3.org/2000/svg"><text>&a;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Expands an entity with markup that starts with text and a reference to another entity' => [
            '<!DOCTYPE svg [<!ENTITY a "x&b;"><!ENTITY b "<tspan>y</tspan>">]><svg xmlns="http://www.w3.org/2000/svg"><text>&a;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x<tspan>y</tspan></text></svg>',
        ];

        yield 'Resolves nested entities with markup up to the depth limit' => [
            '<!DOCTYPE svg [<!ENTITY m "<tspan>&e1;</tspan>"><!ENTITY e1 "1&e2;"><!ENTITY e2 "2&e3;"><!ENTITY e3 "3&e4;"><!ENTITY e4 "4&e5;"><!ENTITY e5 "5&e6;"><!ENTITY e6 "6&e7;"><!ENTITY e7 "7&e8;"><!ENTITY e8 "8&e9;"><!ENTITY e9 "9&e10;"><!ENTITY e10 "10">]><svg xmlns="http://www.w3.org/2000/svg"><text>&m;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>12345678</tspan></text></svg>',
        ];

        yield 'Expands nested entities without markup beyond the depth limit' => [
            '<!DOCTYPE svg [<!ENTITY e1 "1&e2;"><!ENTITY e2 "2&e3;"><!ENTITY e3 "3&e4;"><!ENTITY e4 "4&e5;"><!ENTITY e5 "5&e6;"><!ENTITY e6 "6&e7;"><!ENTITY e7 "7&e8;"><!ENTITY e8 "8&e9;"><!ENTITY e9 "9&e10;"><!ENTITY e10 "10">]><svg xmlns="http://www.w3.org/2000/svg"><text>&e1;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>12345678910</text></svg>',
        ];

        yield 'Resolves deeply nested entities in a namespace declaration' => [
            '<!DOCTYPE svg [<!ENTITY e1 "1&e2;"><!ENTITY e2 "2&e3;"><!ENTITY e3 "3&e4;"><!ENTITY e4 "4&e5;"><!ENTITY e5 "5&e6;"><!ENTITY e6 "6&e7;"><!ENTITY e7 "7&e8;"><!ENTITY e8 "8">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:&e1;"><x:a/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:12345678"><x:a/></svg>',
        ];

        yield 'Binds the first declaration of an entity declared twice' => [
            '<!DOCTYPE svg [<!ENTITY w "1"><!ENTITY w "2">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:&w;"><x:a/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:1"><x:a/></svg>',
        ];

        yield 'Resolves a multibyte character reference in an entity' => [
            '<!DOCTYPE svg [<!ENTITY w "urn:&#233;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="&w;"><x:a/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:é"><x:a/></svg>',
        ];

        yield 'Resolves a quote in a namespace declaration' => [
            '<!DOCTYPE svg [<!ENTITY ns_x "urn:&#38;quot;x">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="&ns_x;"><x:a/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x=\'urn:"x\'><x:a/></svg>',
        ];
    }

    /**
     * @param list<SvgOptimizerRuleInterface> $rules
     *
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideResolvesEntityReferencesInNamespacesWhenRulesRemoveTheDoctypeCases')]
    #[Test]
    public function resolvesEntityReferencesInNamespacesWhenRulesRemoveTheDoctype(array $rules): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider(
            "<!DOCTYPE svg [<!ENTITY ns \"http://x/\">]>\n<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x=\"&ns;\"><rect width=\"9\" height=\"9\"/></svg>",
        ));

        foreach ($rules as $rule) {
            $svgOptimizer->addRule($rule);
        }

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://x/"><rect width="9" height="9"/></svg>',
            $svgOptimizer->optimize()->getContent(),
        );
    }

    /**
     * @return iterable<string, array{list<SvgOptimizerRuleInterface>}>
     */
    public static function provideResolvesEntityReferencesInNamespacesWhenRulesRemoveTheDoctypeCases(): iterable
    {
        yield 'Removing the DOCTYPE' => [[new RemoveDoctype()]];

        yield 'Removing the DOCTYPE and parsing the document again' => [[new RemoveDoctype(), new RemoveInvisibleCharacters()]];
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function expandsEntityMarkupInTheNamespacesInScope(): void
    {
        $content = '<!DOCTYPE svg [<!ENTITY m "<rect/><x:a/><y:b/>">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" xmlns:y=\'urn:"y\'><g>&m;</g></svg>';
        $domDocument = (new DomDocumentWrapper())->loadFromString($content);

        $namespaces = [];

        $group = $domDocument->getElementsByTagName('g')->item(0);
        self::assertInstanceOf(\DOMElement::class, $group);

        foreach ($group->childNodes as $node) {
            $namespaces[$node->nodeName] = $node->namespaceURI;
        }

        self::assertSame(['rect' => 'http://www.w3.org/2000/svg', 'x:a' => 'urn:x', 'y:b' => 'urn:"y'], $namespaces);
    }

    /**
     * @throws \DOMException
     * @throws \ReflectionException
     */
    #[Test]
    public function parsesMarkupThatIsNotWellFormedToNoNodes(): void
    {
        $domDocument = new \DOMDocument();
        $domElement = $domDocument->createElementNS('http://www.w3.org/2000/svg', 'g');
        $domDocument->appendChild($domElement);

        $domDocumentWrapper = new DomDocumentWrapper();
        $reflectionMethod = new \ReflectionMethod($domDocumentWrapper, 'parseEntityMarkup');

        self::assertSame([], $reflectionMethod->invoke($domDocumentWrapper, $domElement, '<a>'));
    }

    /**
     * @throws XmlProcessingException
     */
    #[Test]
    public function restoresTheLibxmlErrorHandlingAfterExpandingEntityMarkup(): void
    {
        $previous = libxml_use_internal_errors(false);

        try {
            (new DomDocumentWrapper())->loadFromString('<!DOCTYPE svg [<!ENTITY m "<z:a/>">]><svg xmlns="http://www.w3.org/2000/svg"><g>&m;</g></svg>');

            self::assertFalse(libxml_use_internal_errors(false));
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[Test]
    public function expandsEntityMarkupInTheSvgNamespace(): void
    {
        $content = '<!DOCTYPE svg [<!ENTITY shape "<rect width=\'5\'/>">]><svg xmlns="http://www.w3.org/2000/svg">&shape;</svg>';
        $actual = (new SvgOptimizer(new StringProvider($content)))->optimize()->getContent();

        $domDocument = new \DOMDocument();
        $domDocument->loadXML('' === $actual ? '<x/>' : $actual);

        $rect = $domDocument->getElementsByTagName('rect')->item(0);

        self::assertInstanceOf(\DOMElement::class, $rect);
        self::assertSame('http://www.w3.org/2000/svg', $rect->namespaceURI);
    }
}
