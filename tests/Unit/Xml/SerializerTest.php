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
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Xml\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use MathiasReker\PhpSvgOptimizer\Xml\TextWhitespaceTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Serializer::class)]
#[CoversTrait(TextWhitespaceTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(RemoveUnsafeMarkup::class)]
#[UsesClass(SvgValidator::class)]
#[UsesTrait(EntityReferenceTrait::class)]
final class SerializerTest extends TestCase
{
    private Parser $parser;

    private Serializer $serializer;

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideKeepsWhitespaceInTextCases')]
    #[Test]
    public function keepsWhitespaceInText(string $content, string $expected): void
    {
        $actual = (new Pipeline([]))->run($content);

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideKeepsWhitespaceInTextCases(): iterable
    {
        yield 'Keeps a line break between tspans as a space' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text y=\"15\"><tspan>A</tspan>\n<tspan>B</tspan></text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text y="15"><tspan>A</tspan> <tspan>B</tspan></text></svg>',
        ];

        yield 'Collapses a whitespace run between tspans to one space' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text><tspan>A</tspan>\n\t  <tspan>B</tspan></text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>A</tspan> <tspan>B</tspan></text></svg>',
        ];

        yield 'Keeps every whitespace character between tspans as a space where xml:space is preserve' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><g xml:space=\"preserve\"><text><tspan>A</tspan>\n\t <tspan>B</tspan></text></g></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><g xml:space="preserve"><text><tspan>A</tspan>   <tspan>B</tspan></text></g></svg>',
        ];

        yield 'Keeps leading whitespace in text where xml:space is preserve' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text xml:space=\"preserve\">\n<tspan>A</tspan></text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve"> <tspan>A</tspan></text></svg>',
        ];

        yield 'Removes trailing whitespace after whitespace between tspans' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text><tspan>A</tspan> <tspan>B</tspan>\n</text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>A</tspan> <tspan>B</tspan></text></svg>',
        ];

        yield 'Removes leading and trailing whitespace in text' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\n  <tspan>A</tspan>\n  <tspan> </tspan>\n</text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>A</tspan><tspan/></text></svg>',
        ];

        yield 'Collapses whitespace where a nearer xml:space is default' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xml:space=\"preserve\"><text xml:space=\"default\"><tspan>A</tspan>\n\t <tspan>B</tspan></text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><text xml:space="default"><tspan>A</tspan> <tspan>B</tspan></text></svg>',
        ];

        yield 'Keeps whitespace after a text span holding only whitespace, as text precedes it' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<tspan> </tspan> <tspan>b</tspan></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<tspan> </tspan> <tspan>b</tspan></text></svg>',
        ];

        yield 'Keeps whitespace between nested text content elements' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><path id=\"p\" d=\"M0 0h9\"/><text><textPath href=\"#p\"><tspan>A</tspan> <a href=\"#p\"><tspan>B</tspan></a>\n<tspan>C</tspan></textPath></text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="p" d="M0 0h9"/><text><textPath href="#p"><tspan>A</tspan> <a href="#p"><tspan>B</tspan></a> <tspan>C</tspan></textPath></text></svg>',
        ];

        yield 'Removes whitespace outside text' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\">\n  <g>\n    <rect width=\"1\"/>\n  </g>\n  <title> </title>\n  <tspan>A</tspan> <tspan>B</tspan>\n</svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1"/></g><title/><tspan>A</tspan><tspan>B</tspan></svg>',
        ];

        yield 'Collapses the indentation of pretty-printed text' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\n    Hello\n    World\n  </text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text> Hello World </text></svg>',
        ];

        yield 'Collapses a tab to a space' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>a\t\tb</text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a b</text></svg>',
        ];

        yield 'Keeps a line break without spaces, which renders differently in SVG 1.1' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>a\nb</text></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>a\nb</text></svg>",
        ];

        yield 'Keeps a carriage return written as a reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a&#13;b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a&#13;b</text></svg>',
        ];

        yield 'Collapses whitespace in a merged CDATA section' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>a <![CDATA[x\n  y]]></text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a x y</text></svg>',
        ];

        yield 'Keeps whitespace in text where xml:space is preserve' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text xml:space=\"preserve\">a\n  b</text></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text xml:space=\"preserve\">a\n  b</text></svg>",
        ];

        yield 'Keeps whitespace in text when the white-space property may be set' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>text{White-Space:pre}</style><text>a\n  b</text></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>text{White-Space:pre}</style><text>a\n  b</text></svg>",
        ];

        yield 'Keeps line breaks in style sheets, where they separate selectors' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>.a\n.b{fill:red}</style><g class=\"a\"><rect class=\"b\"/></g></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>.a\n.b{fill:red}</style><g class=\"a\"><rect class=\"b\"/></g></svg>",
        ];

        yield 'Keeps whitespace in a text element of another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:text>a   b</x:text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:text>a   b</x:text></svg>',
        ];

        yield 'Keeps whitespace in elements that are not rendered as text' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><title>a\n  b</title><desc>c\t d</desc></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><title>a\n  b</title><desc>c\t d</desc></svg>",
        ];

        yield 'Keeps a space between XHTML elements in a foreign object' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="300" height="100"><div xmlns="http://www.w3.org/1999/xhtml"><b>Hello</b> <i>World</i></div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="300" height="100"><div xmlns="http://www.w3.org/1999/xhtml"><b>Hello</b> <i>World</i></div></foreignObject></svg>',
        ];

        yield 'Keeps whitespace directly in a foreign object' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><foreignObject>\n  <i>a</i>\n  <i>b</i>\n</foreignObject></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><foreignObject>\n  <i>a</i>\n  <i>b</i>\n</foreignObject></svg>",
        ];

        yield 'Keeps whitespace in an element of another namespace' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x=\"urn:x\"><x:a>\n  <x:b/>\n  <x:b/>\n</x:a></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x=\"urn:x\"><x:a>\n  <x:b/>\n  <x:b/>\n</x:a></svg>",
        ];

        yield 'Keeps whitespace in text when a linked style sheet may set the white-space property' => [
            "<?xml-stylesheet type=\"text/css\" href=\"a.css\"?><svg xmlns=\"http://www.w3.org/2000/svg\"><text>a\n  b</text></svg>",
            "<?xml-stylesheet type=\"text/css\" href=\"a.css\"?><svg xmlns=\"http://www.w3.org/2000/svg\"><text>a\n  b</text></svg>",
        ];

        yield 'Keeps whitespace in text when an imported style sheet may set the white-space property' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>@IMPORT url(a.css);</style><text>a\n  b</text></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>@IMPORT url(a.css);</style><text>a\n  b</text></svg>",
        ];

        yield 'Keeps whitespace in text when an escaped property name may set the white-space property' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>text{white\\-space:pre}</style><text>a\n  b</text></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>text{white\\-space:pre}</style><text>a\n  b</text></svg>",
        ];

        yield 'Collapses whitespace in text when another processing instruction precedes the document' => [
            "<?other a?><svg xmlns=\"http://www.w3.org/2000/svg\"><text>a\n  b</text></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a b</text></svg>',
        ];
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function loadsAndSavesManyPrettyPrintedTextSpansInLinearTime(): void
    {
        $small = $this->secondsToLoadAndSave(1_250);
        $large = $this->secondsToLoadAndSave(5_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    /**
     * @throws ParseException
     */
    private function secondsToLoadAndSave(int $count): float
    {
        $content = "<svg xmlns=\"http://www.w3.org/2000/svg\">\n  <text>" . str_repeat("\n    <tspan>w</tspan>", $count) . "\n  </text>\n</svg>";
        $start = hrtime(true);

        $saved = (new Serializer())->serialize((new Parser())->parse($content));

        self::assertSame($count - 1, mb_substr_count($saved, '</tspan> <tspan>'));

        return (hrtime(true) - $start) / 1e9;
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideKeepsStylesheetInstructionsCases')]
    #[Test]
    public function keepsStylesheetInstructions(string $content, string $expected): void
    {
        self::assertSame($expected, (new Pipeline([]))->run($content));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideKeepsStylesheetInstructionsCases(): iterable
    {
        yield 'Keeps a stylesheet instruction before the document element' => [
            '<?xml version="1.0"?><?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps every stylesheet instruction in order, in any case' => [
            '<?xml-stylesheet href="a.css"?><?XML-STYLESHEET href="b.css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
            '<?xml-stylesheet href="a.css"?><?XML-STYLESHEET href="b.css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Drops other processing instructions before the document element' => [
            '<?xpacket begin=""?><?xml-stylesheet href="a.css"?><?xpacket end="w"?><svg xmlns="http://www.w3.org/2000/svg"/>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Writes nothing extra without stylesheet instructions' => [
            '<!-- c --><svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    #[Test]
    public function sanitizerStillRemovesStylesheetInstructions(): void
    {
        $input = '<?xml-stylesheet href="https://evil.test/a.css"?><svg xmlns="http://www.w3.org/2000/svg"/>';
        $pipeline = new Pipeline([new RemoveUnsafeMarkup()]);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', $pipeline->run($input));
    }

    #[Test]
    public function serializesTheDocumentElement(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<root><child>Test</child></root>');

        $result = $this->serializer->serialize($domDocument);

        self::assertStringContainsString('<root><child>Test</child></root>', $result);
    }

    #[Test]
    public function serializeRemovesIndentationButKeepsWhitespaceInContent(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<root>\n\t<child>\n\t\tTest\n\t</child>\n</root>");

        $result = $this->serializer->serialize($domDocument);

        self::assertSame("<root><child>\n\t\tTest\n\t</child></root>", $result);
    }

    #[Test]
    public function serializeWritesLineBreaksAsTheParserNormalizedThem(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<root>\r\n\t<child>\r\n\t\tTest\r\n\t</child>\r\n</root>");

        $result = $this->serializer->serialize($domDocument);

        self::assertSame("<root><child>\n\t\tTest\n\t</child></root>", $result);
    }

    #[Test]
    public function serializeWritesElementsWithIgnorableWhitespaceOnlyAsEmptyElements(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<svg xmlns=\"http://www.w3.org/2000/svg\"><g>\n  </g><text>a<tspan> </tspan>b</text></svg>");

        self::assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><text>a<tspan> </tspan>b</text></svg>',
            $this->serializer->serialize($domDocument),
        );
    }

    #[Test]
    public function serializeLeavesTheDocumentAsItWas(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<svg xmlns=\"http://www.w3.org/2000/svg\">\n  <g>\n    </g>\n  <text>a <tspan>b</tspan>\n  </text>\n</svg>");

        $before = $domDocument->saveXML();

        $this->serializer->serialize($domDocument);

        self::assertSame($before, $domDocument->saveXML());
    }

    #[Test]
    public function serializeHandlesEmptyDomDocument(): void
    {
        $domDocument = new \DOMDocument();
        $result = $this->serializer->serialize($domDocument);
        self::assertSame("<?xml version=\"1.0\"?>\n", $result);
    }

    #[Test]
    public function serializeKeepsWhitespaceInCdataSections(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<root><a><![CDATA[ ]]></a> <b/></root>');

        self::assertSame('<root><a><![CDATA[ ]]></a><b/></root>', $this->serializer->serialize($domDocument));
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideSerializeWritesAnEndTagForEmptyXhtmlElementsThatAreNotVoidCases')]
    #[Test]
    public function serializeWritesAnEndTagForEmptyXhtmlElementsThatAreNotVoid(string $content, string $expected): void
    {
        $domDocument = $this->parser->parse($content);
        $saved = $this->serializer->serialize($domDocument);

        self::assertSame($expected, $saved);
        self::assertSame($saved, $this->serializer->serialize($domDocument));
        self::assertSame($saved, $this->serializer->serialize($this->parser->parse($saved)));
    }

    /**
     * @return iterable<string, array{non-empty-string, string}>
     */
    public static function provideSerializeWritesAnEndTagForEmptyXhtmlElementsThatAreNotVoidCases(): iterable
    {
        yield 'Empty elements in foreignObject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><span/><p></p><script/>a</div></foreignObject><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><span></span><p></p><script></script>a</div></foreignObject><rect/></svg>',
        ];

        yield 'Void elements stay self-closing' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><br/><img src="a.png"/><hr></hr><BR/><wbr/></div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><br/><img src="a.png"/><hr/><BR/><wbr/></div></foreignObject></svg>',
        ];

        yield 'Elements whose names only contain the name of a void element' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><foreignObject><div xmlns=\"http://www.w3.org/1999/xhtml\"><abbr/><colgroup/><lin\u{212A}/></div></foreignObject></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><foreignObject><div xmlns=\"http://www.w3.org/1999/xhtml\"><abbr></abbr><colgroup></colgroup><lin\u{212A}></lin\u{212A}></div></foreignObject></svg>",
        ];

        yield 'Prefixed XHTML void element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml"><foreignObject><h:br/><h:img/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml"><foreignObject><h:br/><h:img/></foreignObject></svg>',
        ];

        yield 'Prefixed XHTML element holding only whitespace' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:h=\"http://www.w3.org/1999/xhtml\"><foreignObject><h:div>\n  </h:div></foreignObject></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:h=\"http://www.w3.org/1999/xhtml\"><foreignObject><h:div>\n  </h:div></foreignObject></svg>",
        ];

        yield 'Elements in other namespaces stay self-closing' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><foreignObject><div/><x:div xmlns:x="urn:x"/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><foreignObject><div/><x:div xmlns:x="urn:x"/></foreignObject></svg>',
        ];
    }

    /**
     * @param non-empty-string $content
     *
     * @throws ParseException
     */
    #[DataProvider('provideSerializeWritesNonAsciiCharactersAsUtf8Cases')]
    #[Test]
    public function serializeWritesNonAsciiCharactersAsUtf8(string $content): void
    {
        $saved = $this->serializer->serialize($this->parser->parse($content));

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" font-family="日本 é 😀"><text title="ü&amp;">日本 é 😀</text></svg>', $saved);
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideSerializeWritesNonAsciiCharactersAsUtf8Cases(): iterable
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" font-family="日本 é 😀"><text title="ü&amp;">日本 é 😀</text></svg>';

        yield 'Without an XML declaration' => [$svg];

        yield 'With an XML declaration without encoding' => ['<?xml version="1.0"?>' . $svg];

        yield 'With a UTF-8 declaration' => ['<?xml version="1.0" encoding="UTF-8"?>' . $svg];

        yield 'With a lowercase utf-8 declaration' => ["<?xml version='1.0' encoding='utf-8'?>" . $svg];

        yield 'As character references' => ['<svg xmlns="http://www.w3.org/2000/svg" font-family="&#x65E5;&#26412; &#xE9; &#x1F600;"><text title="&#xFC;&amp;">&#x65E5;&#x672C; &#xE9; &#x1F600;</text></svg>'];

        yield 'UTF-16LE' => ["\xFF\xFE" . mb_convert_encoding($svg, 'UTF-16LE', 'UTF-8')];
    }

    #[Test]
    public function serializeDoesNotSetAnEncodingOnADocumentWithoutOne(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" font-family="日本"/>');

        $saved = $this->serializer->serialize($domDocument);

        self::assertNotSame('', $saved);

        $reloaded = new \DOMDocument();
        $reloaded->loadXML($saved);

        self::assertStringStartsWith('<svg ', $saved);
        self::assertSame('日本', $reloaded->documentElement?->getAttribute('font-family'));
        self::assertNull($domDocument->encoding);
    }

    #[Test]
    public function serializeKeepsTheEncodingOfTheDocument(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML("<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?><svg xmlns=\"http://www.w3.org/2000/svg\" font-family=\"caf\xE9\"/>");

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg" font-family="café"/>', $this->serializer->serialize($domDocument));
        self::assertSame('ISO-8859-1', $domDocument->encoding);
    }

    #[Test]
    public function serializeLeavesNoPlaceholderInTheDocument(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"/><p xmlns="http://www.w3.org/1999/xhtml"/></foreignObject></svg>');

        $this->serializer->serialize($domDocument);

        self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"/><p xmlns="http://www.w3.org/1999/xhtml"/></foreignObject></svg>', $domDocument->saveXML($domDocument->documentElement));
    }

    /**
     * @throws ParseException
     */
    #[Test]
    public function serializeKeepsOnlyStylesheetInstructionsOutsideTheDocumentElement(): void
    {
        $domDocument = $this->parser->parse(
            '<?xml version="1.0"?><!-- before --><?foo bar?><?XML-STYLESHEET href="a.css"?><!DOCTYPE svg><svg xmlns="http://www.w3.org/2000/svg"/><!-- after --><?xml-stylesheet href="b.css"?>',
        );

        self::assertSame(
            '<?XML-STYLESHEET href="a.css"?><?xml-stylesheet href="b.css"?><svg xmlns="http://www.w3.org/2000/svg"/>',
            $this->serializer->serialize($domDocument),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->serializer = new Serializer();
    }
}
