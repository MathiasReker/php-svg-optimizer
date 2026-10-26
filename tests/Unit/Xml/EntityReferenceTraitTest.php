<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Xml;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Xml\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Xml\TextWhitespaceTrait;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversTrait(EntityReferenceTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(RemoveInvisibleCharacters::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
#[UsesTrait(ElementTrait::class)]
#[UsesTrait(TextWhitespaceTrait::class)]
final class EntityReferenceTraitTest extends TestCase
{
    use EntityReferenceTrait;

    /**
     * @param non-empty-string $expected
     *
     * @throws InvalidSvgException
     */
    #[DataProvider('provideResolvesEntityReferencesCases')]
    #[Test]
    public function resolvesEntityReferences(string $content, string $expected): void
    {
        $actual = (new Pipeline([]))->run($content);

        self::assertNotSame('', $actual);
        self::assertSame($this->normalizeNamespaceDeclarations($expected), $this->normalizeNamespaceDeclarations($actual));
        self::assertTrue((new \DOMDocument())->loadXML($actual), 'The output must be well-formed without the DOCTYPE.');
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
     * @return string the markup with every namespace declaration written in double quotes with the same escaping, as
     *                libxml versions serialize namespace declarations differently
     */
    private function normalizeNamespaceDeclarations(string $markup): string
    {
        return (string) preg_replace_callback(
            '/\sxmlns(:[^\s=]+)?=(["\'])(.*?)\2/s',
            static fn (array $match): string => ' xmlns' . $match[1] . '="' . htmlspecialchars(html_entity_decode($match[3], \ENT_QUOTES | \ENT_XML1), \ENT_QUOTES | \ENT_XML1) . '"',
            $markup,
        );
    }

    /**
     * @param list<PassInterface> $rules
     *
     * @throws InvalidSvgException
     */
    #[DataProvider('provideResolvesEntityReferencesInNamespacesWhenTheDoctypeIsDroppedCases')]
    #[Test]
    public function resolvesEntityReferencesInNamespacesWhenTheDoctypeIsDropped(array $rules): void
    {
        $input = "<!DOCTYPE svg [<!ENTITY ns \"http://x/\">]>\n<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x=\"&ns;\"><rect width=\"9\" height=\"9\"/></svg>";
        $pipeline = new Pipeline($rules);

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://x/"><rect width="9" height="9"/></svg>',
            $pipeline->run($input),
        );
    }

    /**
     * @return iterable<string, array{list<PassInterface>}>
     */
    public static function provideResolvesEntityReferencesInNamespacesWhenTheDoctypeIsDroppedCases(): iterable
    {
        yield 'Serializing without a DOCTYPE' => [[]];

        yield 'Serializing without a DOCTYPE after parsing the document again' => [[new RemoveInvisibleCharacters()]];
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function expandsEntityMarkupInTheNamespacesInScope(): void
    {
        $content = '<!DOCTYPE svg [<!ENTITY m "<rect/><x:a/><y:b/>">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" xmlns:y=\'urn:"y\'><g>&m;</g></svg>';
        $domDocument = (new Parser())->parse($content);

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

        $parser = new Parser();
        $reflectionMethod = new \ReflectionMethod($parser, 'parseEntityMarkup');

        self::assertSame([], $reflectionMethod->invoke($parser, $domDocument, $domElement, '<a>'));
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function restoresTheLibxmlErrorHandlingAfterExpandingEntityMarkup(): void
    {
        $previous = libxml_use_internal_errors(false);

        try {
            (new Parser())->parse('<!DOCTYPE svg [<!ENTITY m "<z:a/>">]><svg xmlns="http://www.w3.org/2000/svg"><g>&m;</g></svg>');

            self::assertFalse(libxml_use_internal_errors(false));
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function expandsEntityMarkupInTheSvgNamespace(): void
    {
        $content = '<!DOCTYPE svg [<!ENTITY shape "<rect width=\'5\'/>">]><svg xmlns="http://www.w3.org/2000/svg">&shape;</svg>';
        $actual = (new Pipeline([]))->run($content);

        $domDocument = new \DOMDocument();
        $domDocument->loadXML('' === $actual ? '<x/>' : $actual);

        $rect = $domDocument->getElementsByTagName('rect')->item(0);

        self::assertInstanceOf(\DOMElement::class, $rect);
        self::assertSame('http://www.w3.org/2000/svg', $rect->namespaceURI);
    }

    #[DataProvider('provideRestoresThePreviousErrorModeCases')]
    #[Test]
    public function restoresThePreviousErrorMode(bool $useInternalErrors): void
    {
        $previous = libxml_use_internal_errors($useInternalErrors);

        try {
            $this->loadXmlSilently(new \DOMDocument(), '<svg><use xlink:href="#a"/></svg>');

            self::assertSame($useInternalErrors, libxml_use_internal_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function provideRestoresThePreviousErrorModeCases(): iterable
    {
        yield 'internal errors disabled' => [false];

        yield 'internal errors enabled' => [true];
    }

    #[Test]
    public function loadsRecoverableXmlWithoutLeavingErrorsBehind(): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $domDocument = new \DOMDocument();

            self::assertTrue($this->loadXmlSilently($domDocument, '<svg><use xlink:href="#a"/></svg>'));
            self::assertSame('svg', $domDocument->documentElement?->nodeName);
            self::assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    #[Test]
    public function returnsFalseForMalformedXml(): void
    {
        self::assertFalse($this->loadXmlSilently(new \DOMDocument(), '<svg>'));
    }

    #[Test]
    public function loadsWithoutOptionsByDefault(): void
    {
        $domDocument = new \DOMDocument();

        self::assertTrue($this->loadXmlSilently($domDocument, '<svg><![CDATA[a]]></svg>'));
        self::assertInstanceOf(\DOMCdataSection::class, $domDocument->documentElement?->firstChild);
    }

    #[Test]
    public function givesADocumentWithoutDeclaredEncodingTheUtf8Encoding(): void
    {
        $domDocument = new \DOMDocument();

        self::assertTrue($this->loadXmlSilently($domDocument, '<svg font-family="日本"/>'));
        self::assertSame('UTF-8', $domDocument->encoding);
        self::assertSame('<svg font-family="日本"/>', $domDocument->saveXML($domDocument->documentElement));
    }

    #[Test]
    public function keepsTheDeclaredEncoding(): void
    {
        $domDocument = new \DOMDocument();

        self::assertTrue($this->loadXmlSilently($domDocument, '<?xml version="1.0" encoding="ISO-8859-1"?><svg/>'));
        self::assertSame('ISO-8859-1', $domDocument->encoding);
    }

    #[Test]
    public function leavesTheEncodingUnsetWhenLoadingFails(): void
    {
        $domDocument = new \DOMDocument();

        self::assertFalse($this->loadXmlSilently($domDocument, '<svg'));
        self::assertNull($domDocument->encoding);
    }

    #[Test]
    public function passesTheOptions(): void
    {
        $domDocument = new \DOMDocument();

        self::assertTrue($this->loadXmlSilently($domDocument, '<svg><![CDATA[a]]></svg>', \LIBXML_NOCDATA));
        self::assertNotInstanceOf(\DOMCdataSection::class, $domDocument->documentElement?->firstChild);
        self::assertSame('a', $domDocument->documentElement?->textContent);
    }
}
