<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Xml;

use MathiasReker\PhpSvgOptimizer\Tests\Double\LinearTimeTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\RegexFailureTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\TemporaryDirectoryTrait;
use MathiasReker\PhpSvgOptimizer\Xml\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\TextWhitespaceTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Parser::class)]
#[CoversTrait(EntityReferenceTrait::class)]
#[CoversTrait(TextWhitespaceTrait::class)]
#[UsesClass(Serializer::class)]
final class ParserTest extends TestCase
{
    use LinearTimeTrait;
    use RegexFailureTrait;
    use TemporaryDirectoryTrait;

    private Parser $parser;

    private Serializer $serializer;

    /**
     * @throws ParseException
     */
    #[Test]
    public function parsesAWellFormedDocument(): void
    {
        $content = '<root><child>Test</child></root>';
        $domDocument = $this->parser->parse($content);

        $xmlString = $domDocument->saveXML();

        self::assertNotFalse($xmlString, 'saveXML() returned false');
        self::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseWithLineFeedsAndTabs(): void
    {
        $content = "<root>\n\t<child>Test</child>\n</root>";
        $domDocument = $this->parser->parse($content);

        $xmlString = $this->serializer->serialize($domDocument);

        self::assertStringNotContainsString("\n", $xmlString, 'Newline characters were not removed.');
        self::assertStringNotContainsString("\t", $xmlString, 'Tab characters were not removed.');
        self::assertStringContainsString('<root><child>Test</child></root>', $xmlString);
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function rejectsADocumentThatIsNotWellFormedWithTheReason(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Failed to load DOMDocument: Opening and ending tag mismatch: unclosed line 1 and root on line 1, column 24.');

        $invalidXml = '<root><unclosed></root>';
        $this->parser->parse($invalidXml);
    }

    #[Test]
    public function parseRestoresTheLibxmlErrorStateAfterAFailure(): void
    {
        $previous = libxml_use_internal_errors(false);

        try {
            $this->parser->parse('<root><unclosed>');
            self::fail('Expected an ParseException.');
        } catch (ParseException) {
            self::assertFalse(libxml_use_internal_errors());
            self::assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseAppliesAttributeDefaultsToElementsFromEntities(): void
    {
        $domDocument = $this->parser->parse('<!DOCTYPE svg [<!ATTLIST rect fill CDATA "red"><!ENTITY e "<rect x=\'1\'/>">]><svg xmlns="http://www.w3.org/2000/svg"><g>&e;</g><rect/></svg>');

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><g><rect x="1" fill="red"/></g><rect fill="red"/></svg>', $this->serializer->serialize($domDocument));
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseThrowsExceptionOnEmptyContent(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('XML content cannot be empty.');

        $this->parser->parse('');
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseRestoresTheLibxmlErrorHandling(): void
    {
        $previous = libxml_use_internal_errors(false);

        try {
            $this->parser->parse('<root/>');
            self::assertFalse(libxml_use_internal_errors(true));

            $this->parser->parse('<root/>');
            self::assertTrue(libxml_use_internal_errors(false));
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    #[Test]
    public function parseRestoresTheLibxmlErrorHandlingWhenLoadingFails(): void
    {
        foreach ([false, true] as $internalErrors) {
            $previous = libxml_use_internal_errors($internalErrors);

            try {
                $this->parser->parse('<root><unclosed></root>');
                self::fail('Malformed XML must not load.');
            } catch (ParseException) {
                self::assertSame([], libxml_get_errors());
                self::assertSame($internalErrors, libxml_use_internal_errors($previous));
            }
        }
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseDoesNotLoadExternalDeclarations(): void
    {
        $directory = $this->createTemporaryDirectory();
        $dtdPath = $directory . '/external.dtd';
        $textPath = $directory . '/external.txt';
        file_put_contents($dtdPath, '<!ENTITY x "dtd">');
        file_put_contents($textPath, 'file');
        $content = \sprintf(
            '<!DOCTYPE svg SYSTEM "%s" [<!ENTITY ext SYSTEM "%s">]><svg xmlns="http://www.w3.org/2000/svg"><text>a&x;&ext;b</text></svg>',
            str_replace('\\', '/', $dtdPath),
            str_replace('\\', '/', $textPath),
        );

        $domDocument = $this->parser->parse($content);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><text>ab</text></svg>', $this->serializer->serialize($domDocument));
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseMergesCdataSectionsIntoTheText(): void
    {
        $domDocument = $this->parser->parse("<svg><style>\n  a<![CDATA[{b}]]>c\n</style></svg>");
        $style = $domDocument->getElementsByTagName('style')->item(0);

        self::assertInstanceOf(\DOMElement::class, $style);
        self::assertSame(1, $style->childNodes->length);
        self::assertInstanceOf(\DOMText::class, $style->firstChild);
        self::assertNotInstanceOf(\DOMCdataSection::class, $style->firstChild);
        self::assertSame("\n  a{b}c\n", $style->textContent);
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideParseRemovesBlankTextNodesLikeTheParserCases')]
    #[Test]
    public function parseRemovesBlankTextNodesLikeTheParser(string $content, string $expected): void
    {
        $domDocument = $this->parser->parse($content);

        self::assertSame($expected, $domDocument->saveXML($domDocument->documentElement));
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string}>
     */
    public static function provideParseRemovesBlankTextNodesLikeTheParserCases(): iterable
    {
        yield 'Whitespace between elements' => ["<svg>\n  <g>\n    <rect/>\n  </g>\n  <!-- c -->\n  <g/>\n</svg>", '<svg><g><rect/></g><!-- c --><g/></svg>'];

        yield 'Whitespace that is the only content of an element' => ['<svg><title> </title><g>  </g></svg>', '<svg><title> </title><g>  </g></svg>'];

        yield 'Whitespace after a first child that is text' => ['<svg><g>a<rect/> <rect/> <rect/></g></svg>', '<svg><g>a<rect/> <rect/> <rect/></g></svg>'];

        yield 'Whitespace after text that starts with whitespace' => ['<svg><g><rect/> a <rect/> <rect/></g><g><rect/>a b<rect/> <rect/></g></svg>', '<svg><g><rect/> a <rect/> <rect/></g><g><rect/>a b<rect/><rect/></g></svg>'];

        yield 'Whitespace after text where xml:space is default' => ['<svg><g xml:space="default"><rect/> a <rect/> <rect/></g></svg>', '<svg><g xml:space="default"><rect/> a <rect/><rect/></g></svg>'];

        yield 'Whitespace where xml:space is preserve' => ['<svg><g xml:space="preserve"><rect/> <rect/></g><g xml:space="preserve"> <rect/> <g> <rect/> </g></g><g xml:space="default"> <rect/> </g></svg>', '<svg><g xml:space="preserve"><rect/> <rect/></g><g xml:space="preserve"> <rect/> <g> <rect/> </g></g><g xml:space="default"><rect/></g></svg>'];
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseGivesADocumentWithoutDeclarationTheUtf8Encoding(): void
    {
        self::assertSame('UTF-8', $this->parser->parse('<svg xmlns="http://www.w3.org/2000/svg"/>')->encoding);
        self::assertSame('ISO-8859-1', $this->parser->parse('<?xml version="1.0" encoding="ISO-8859-1"?><svg xmlns="http://www.w3.org/2000/svg"/>')->encoding);
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideParseConvertsTheInputEncodingToUtf8Cases')]
    #[Test]
    public function parseConvertsTheInputEncodingToUtf8(string $content): void
    {
        $domDocument = $this->parser->parse($content);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><text>café €</text></svg>', $this->serializer->serialize($domDocument));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideParseConvertsTheInputEncodingToUtf8Cases(): iterable
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><text>café €</text></svg>';

        yield 'UTF-8 with a byte order mark' => ["\xEF\xBB\xBF" . $svg];

        yield 'UTF-16LE with a byte order mark' => ["\xFF\xFE" . mb_convert_encoding('<?xml version="1.0" encoding="UTF-16"?>' . $svg, 'UTF-16LE', 'UTF-8')];

        yield 'UTF-16BE with a byte order mark' => ["\xFE\xFF" . mb_convert_encoding('<?xml version="1.0" encoding="UTF-16"?>' . $svg, 'UTF-16BE', 'UTF-8')];

        yield 'Windows-1252 declared in the XML declaration' => ['<?xml version="1.0" encoding="windows-1252"?>' . mb_convert_encoding($svg, 'Windows-1252', 'UTF-8')];

        yield 'UTF-16 declared on UTF-8 bytes without a byte order mark' => ['<?xml version="1.0" encoding="utf-16"?>' . $svg];

        yield 'UTF-16LE declared in single quotes on UTF-8 bytes' => ["<?xml version='1.0' encoding='UTF-16LE' standalone='no'?>\n" . $svg];

        yield 'UTF-16BE declared on UTF-8 bytes with a byte order mark' => ["\xEF\xBB\xBF<?xml version=\"1.0\" encoding = \"Utf-16be\"?>" . $svg];
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseAppliesTheAttributeDefaultsOfTheInternalSubset(): void
    {
        $domDocument = $this->parser->parse('<!DOCTYPE svg [<!ATTLIST rect fill CDATA "red">]><svg xmlns="http://www.w3.org/2000/svg"><rect/><rect fill="blue"/></svg>');

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/><rect fill="blue"/></svg>', $this->serializer->serialize($domDocument));
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseIgnoresAttributeDefaultsWithAnExternalSubset(): void
    {
        $domDocument = $this->parser->parse('<!DOCTYPE svg SYSTEM "missing.dtd" [<!ATTLIST rect fill CDATA "red">]><svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>');

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>', $this->serializer->serialize($domDocument));
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseIgnoresAttributeDefaultsWithAPublicIdentifier(): void
    {
        $domDocument = $this->parser->parse('<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "" [<!ATTLIST rect fill CDATA "red">]><svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>');

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>', $this->serializer->serialize($domDocument));
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseRejectsBytesThatAreNotValidInTheDeclaredEncoding(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse("<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\xC3\x28</text></svg>");
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseResolvesEntitiesUsedAsNamespaceUris(): void
    {
        $domDocument = $this->parser->parse(
            '<!DOCTYPE svg [<!ENTITY ns_svg "http://www.w3.org/2000/svg"><!ENTITY ns_x "urn:x">]><svg xmlns="&ns_svg;" xmlns:x="&ns_x;"><x:a/></svg>',
        );

        self::assertSame('http://www.w3.org/2000/svg', $domDocument->documentElement?->namespaceURI);
        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:a/></svg>', $this->serializer->serialize($domDocument));
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parseExpandsAMarkupEntityIntoTheSvgNamespaceBesideANamespaceDeclarationEntity(): void
    {
        $domDocument = $this->parser->parse(
            '<!DOCTYPE svg [<!ENTITY ns "http://www.w3.org/2000/svg"><!ENTITY m "<rect width=\'1\'/>">]><svg xmlns="&ns;"><g>&m;</g><text>&ns;</text></svg>',
        );
        $rect = $domDocument->getElementsByTagNameNS('http://www.w3.org/2000/svg', 'rect')->item(0);

        self::assertSame('1', $rect?->getAttribute('width'));
        self::assertSame('http://www.w3.org/2000/svg', $domDocument->documentElement?->textContent);
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideParseIgnoresEntityDeclarationsInsideCommentsAndProcessingInstructionsCases')]
    #[Test]
    public function parseIgnoresEntityDeclarationsInsideCommentsAndProcessingInstructions(string $content, string $expected): void
    {
        self::assertSame($expected, $this->serializer->serialize($this->parser->parse($content)));
    }

    /**
     * @return iterable<string, array{non-empty-string, string}>
     */
    public static function provideParseIgnoresEntityDeclarationsInsideCommentsAndProcessingInstructionsCases(): iterable
    {
        yield 'declaration inside a comment after the real one' => [
            '<!DOCTYPE svg [<!ENTITY a "<g id=\'real\'/>"><!-- <!ENTITY a "<g id=\'commented\'/>"> -->]><svg xmlns="http://www.w3.org/2000/svg">&a;</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="real"/></svg>',
        ];

        yield 'declaration inside a processing instruction after the real one' => [
            '<!DOCTYPE svg [<!ENTITY a "<g id=\'real\'/>"><?pi <!ENTITY a "<g id=\'instruction\'/>"> ?>]><svg xmlns="http://www.w3.org/2000/svg">&a;</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="real"/></svg>',
        ];
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideParseKeepsTheTextOfEntitiesAlsoUsedAsNamespaceUrisCases')]
    #[Test]
    public function parseKeepsTheTextOfEntitiesAlsoUsedAsNamespaceUris(string $content, string $expected): void
    {
        self::assertSame($expected, $this->serializer->serialize($this->parser->parse($content)));
    }

    /**
     * @return iterable<string, array{non-empty-string, string}>
     */
    public static function provideParseKeepsTheTextOfEntitiesAlsoUsedAsNamespaceUrisCases(): iterable
    {
        $doctype = '<!DOCTYPE svg [<!ENTITY ns "http://www.w3.org/2000/svg"><!ENTITY a "x &amp; y"><!ENTITY m "<rect width=\'1\'/>">]>';

        yield 'entity in a namespace declaration and in text' => [
            $doctype . '<svg xmlns="&ns;"><text>abc</text><text>&ns;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>abc</text><text>http://www.w3.org/2000/svg</text></svg>',
        ];

        yield 'entity in a namespace declaration, in an attribute and in text' => [
            $doctype . '<svg xmlns="&ns;"><rect id="&ns;"/><text>&ns;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="http://www.w3.org/2000/svg"/><text>http://www.w3.org/2000/svg</text></svg>',
        ];

        yield 'entity in a namespace declaration and in text of nested elements' => [
            $doctype . '<svg xmlns="&ns;"><g><g><text>x &ns; y</text></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><text>x http://www.w3.org/2000/svg y</text></g></g></svg>',
        ];

        yield 'entity with a predefined entity beside a namespace declaration entity' => [
            $doctype . '<svg xmlns="&ns;"><text>&a;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x &amp; y</text></svg>',
        ];
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideParseRejectsEntityExpansionBeyondTheAllowedSizeCases')]
    #[Test]
    public function parseRejectsEntityExpansionBeyondTheAllowedSize(string $content): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Entity expansion exceeds the allowed size.');

        $this->parser->parse($content);
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideParseRejectsEntityExpansionBeyondTheAllowedSizeCases(): iterable
    {
        $large = str_repeat('A', 100_000);

        yield 'Many references to a large entity in text' => [
            '<!DOCTYPE svg [<!ENTITY a "' . $large . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 20) . '</text></svg>',
        ];

        yield 'Many references to a large entity in attributes' => [
            '<!DOCTYPE svg [<!ENTITY a "' . $large . '">]><svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g id="&a;"/>', 20) . '</svg>',
        ];

        yield 'References to a nested entity that repeats a large entity' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 50_000) . '"><!ENTITY b "&a;&a;&a;&a;&a;">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&b;', 10) . '</text></svg>',
        ];

        yield 'Large entity declared with character references' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('&#x41;', 20_000) . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 100) . '</text></svg>',
        ];

        yield 'References one character beyond the allowed size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '"><!ENTITY b "B">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 512) . '&amp;&b;</text></svg>',
        ];

        yield 'References to nested entities one character beyond the allowed size' => [
            self::nestedEntityDocument('&c;&d;'),
        ];

        yield 'Entities that reference each other' => [
            '<!DOCTYPE svg [<!ENTITY a "&b;"><!ENTITY b "&a;">]><svg xmlns="http://www.w3.org/2000/svg"><text>&a;</text></svg>',
        ];

        yield 'References in a large document beyond five times its size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '">]><svg xmlns="http://www.w3.org/2000/svg"><!--' . str_repeat('x', 400_000) . '--><text>' . str_repeat('&a;', 2_100) . '</text></svg>',
        ];

        yield 'A single reference to a nested entity beyond the allowed size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '"><!ENTITY b "' . str_repeat('&a;', 600) . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>&b;</text></svg>',
        ];
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideParseAcceptsEntityExpansionUpToTheAllowedSizeCases')]
    #[Test]
    public function parseAcceptsEntityExpansionUpToTheAllowedSize(string $content): void
    {
        $domDocument = $this->parser->parse($content);

        self::assertInstanceOf(\DOMElement::class, $domDocument->documentElement);
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideParseAcceptsEntityExpansionUpToTheAllowedSizeCases(): iterable
    {
        yield 'References that expand to exactly the allowed size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '"><!ENTITY b "B">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 512) . '&amp;</text></svg>',
        ];

        yield 'References to nested entities that expand to exactly the allowed size' => [
            self::nestedEntityDocument('&c;'),
        ];

        yield 'References in a large document that expand beyond the minimal allowed size' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '">]><svg xmlns="http://www.w3.org/2000/svg"><!--' . str_repeat('x', 400_000) . '--><text>' . str_repeat('&a;', 1_400) . '</text></svg>',
        ];
    }

    /**
     * @return non-empty-string
     */
    private static function nestedEntityDocument(string $suffix): string
    {
        return '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_024) . '"><!ENTITY ext SYSTEM "ext.txt"><!ENTITY b "&a;&amp;&ext;&a;"><!ENTITY c "' . str_repeat('C', 773) . '"><!ENTITY d "D">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&b;', 255) . $suffix . '</text></svg>';
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideParseExpandsEntitiesWithinTheAllowedSizeCases')]
    #[Test]
    public function parseExpandsEntitiesWithinTheAllowedSize(string $content, int $expectedLength): void
    {
        $domDocument = $this->parser->parse($content);

        self::assertSame($expectedLength, mb_strlen($domDocument->documentElement->textContent ?? ''));
    }

    /**
     * @return iterable<string, array{non-empty-string, int}>
     */
    public static function provideParseExpandsEntitiesWithinTheAllowedSizeCases(): iterable
    {
        yield 'Expansion below the size floor' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 1_000) . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>' . str_repeat('&a;', 500) . '</text></svg>',
            500_000,
        ];

        yield 'Large entity referenced once' => [
            '<!DOCTYPE svg [<!ENTITY a "' . str_repeat('A', 2_000_000) . '">]><svg xmlns="http://www.w3.org/2000/svg"><text>&a;</text></svg>',
            2_000_000,
        ];

        yield 'Nested, predefined and undeclared references inside an entity' => [
            '<!DOCTYPE svg [<!ENTITY a "aa"><!ENTITY b "&a;&amp;&a;">]><svg xmlns="http://www.w3.org/2000/svg"><text>&b;&b;</text></svg>',
            10,
        ];
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function parsesTextWithALongRunOfLineBreaksInLinearTimeWithoutJit(): void
    {
        self::assertLinearTime(
            static fn (int $size): string => '<svg xmlns="http://www.w3.org/2000/svg"><text>x' . str_repeat("\n", $size) . 'x</text></svg>',
            static fn (string $content): mixed => self::withoutJit(static fn (): \DOMDocument => (new Parser())->parse($content)),
            5_000,
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->serializer = new Serializer();
    }
}
