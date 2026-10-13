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
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
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
    }
}
