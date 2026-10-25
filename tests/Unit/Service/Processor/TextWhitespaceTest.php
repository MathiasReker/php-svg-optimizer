<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\TextWhitespaceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\XmlLoaderTrait;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(EntityReferenceTrait::class)]
#[CoversTrait(TextWhitespaceTrait::class)]
#[CoversTrait(XmlLoaderTrait::class)]
#[UsesClass(ExecutionOrder::class)]
final class TextWhitespaceTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideKeepsWhitespaceInTextCases')]
    #[Test]
    public function keepsWhitespaceInText(string $content, string $expected): void
    {
        $actual = (new SvgOptimizer(new StringProvider($content)))->optimize()->getContent();

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
     * @throws XmlProcessingException
     */
    #[Test]
    public function loadsAndSavesManyPrettyPrintedTextSpansInLinearTime(): void
    {
        $small = $this->secondsToLoadAndSave(1_250);
        $large = $this->secondsToLoadAndSave(5_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    /**
     * @throws XmlProcessingException
     */
    private function secondsToLoadAndSave(int $count): float
    {
        $domDocumentWrapper = new DomDocumentWrapper();
        $content = "<svg xmlns=\"http://www.w3.org/2000/svg\">\n  <text>" . str_repeat("\n    <tspan>w</tspan>", $count) . "\n  </text>\n</svg>";
        $start = hrtime(true);

        $saved = $domDocumentWrapper->saveToString($domDocumentWrapper->loadFromString($content));

        self::assertSame($count - 1, mb_substr_count($saved, '</tspan> <tspan>'));

        return (hrtime(true) - $start) / 1e9;
    }
}
