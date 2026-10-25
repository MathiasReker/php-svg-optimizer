<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveEmptyTextElements::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidationException::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
final class RemoveEmptyTextElementsTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addPass(new RemoveEmptyTextElements());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);

        $repeated = new SvgOptimizer(new StringProvider($actual));
        $repeated->addPass(new RemoveEmptyTextElements());
        self::assertSame($actual, $repeated->optimize()->getContent());
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes a text whose only child is a comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text><!--c--></text><text><?pi c?></text><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5"/></svg>',
        ];

        yield 'Removes empty texts before and after a comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text/><!--c--><tspan/><!--d--><tref/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!--c--><!--d--></svg>',
        ];

        yield 'Keeps an empty text that a switch may render' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><text/><rect width="5" height="5"/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><text/><rect width="5" height="5"/></switch></svg>',
        ];

        yield 'Keeps an empty text with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="t"/><animate href="#t" attributeName="x" to="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="t"/><animate href="#t" attributeName="x" to="1"/></svg>',
        ];

        yield 'Keeps an empty text with a role' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text role="img" aria-label="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text role="img" aria-label="a"/></svg>',
        ];

        yield 'Keeps an empty text that can take focus' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text tabindex="0"/><text/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text tabindex="0"/></svg>',
        ];

        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps a text with only a non-breaking space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&#xA0;</text></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\u{A0}</text></svg>",
        ];

        yield 'keeps a text with only an entity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&amp;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&amp;</text></svg>',
        ];

        yield 'keeps a text with character data' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text><![CDATA[x]]></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'removes an empty text deep inside groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><text><tspan><tspan/></tspan></text></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g/></g></g></g></svg>',
        ];

        yield 'keeps an empty mixed-case text element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><Text/><TSPAN/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><Text/><TSPAN/></svg>',
        ];

        yield 'keeps a tref that references through a custom xlink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"><text>a<tref x:href="#t"/></text><text id="t">b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"><text>a<tref x:href="#t"/></text><text id="t">b</text></svg>',
        ];

        yield 'keeps a tref that references through a plain href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<tref href="#t"/></text><text id="t">b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<tref href="#t"/></text><text id="t">b</text></svg>',
        ];

        yield 'removes a tref with an empty reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><text>a<tref xlink:href=""/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><text>a</text></svg>',
        ];

        yield 'keeps empty text elements in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:text xmlns:x="urn:x"/><metadata><text xmlns="urn:y"/></metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:text xmlns:x="urn:x"/><metadata><text xmlns="urn:y"/></metadata></svg>',
        ];

        yield 'removes empty prefixed svg text elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:text><svg:tspan/></svg:text></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps empty text elements when an external style sheet is linked' => [
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><text/></svg>',
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><text/></svg>',
        ];

        yield 'keeps empty text elements when an XHTML link loads a style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml"><h:link rel="stylesheet" href="data:text/css,text%2Brect%7Bfill:red%7D"/><text/><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml"><h:link rel="stylesheet" href="data:text/css,text%2Brect%7Bfill:red%7D"/><text/><rect width="5" height="5"/></svg>',
        ];

        yield 'keeps empty text elements when a style sheet is imported' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><text/><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><text/><rect width="5" height="5"/></svg>',
        ];

        yield 'keeps a text that only holds an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text><set attributeName="x" to="1"/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text><set attributeName="x" to="1"/></text></svg>',
        ];

        yield 'removes a later empty tspan after a kept one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>a</tspan><tspan/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>a</tspan></text></svg>',
        ];

        yield 'Keeps a whitespace-only tspan, whose space renders between the surrounding text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<tspan> </tspan>b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<tspan> </tspan>b</text></svg>',
        ];

        yield 'Keeps preserved whitespace-only text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve" text-decoration="underline">  </text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve" text-decoration="underline">  </text></svg>',
        ];

        yield 'Removes empty <text> element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text/>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes empty <tspan> element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text><tspan/></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes <tref> with empty xlink:href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text><tref xlink:href=""/></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps <text> with children' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>Hello<tspan>World</tspan></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text>Hello<tspan>World</tspan></text></svg>
                XML,
        ];

        yield 'Keeps <tspan> with text' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text><tspan>Text</tspan></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text><tspan>Text</tspan></text></svg>
                XML,
        ];

        yield 'Removes multiple empty <text> and <tspan>' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text/><text><tspan/></text><rect width="1" height="1"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>
                XML,
        ];

        yield 'Removes nested empty elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>
                        <tspan><tref xlink:href=""/></tspan>
                        <tspan>Content</tspan>
                    </text>
                    <text/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text><tspan>Content</tspan></text></svg>
                XML,
        ];

        yield 'Keeps elements with whitespace-only text if parent is non-empty' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>   <tspan>Text</tspan>   </text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text><tspan>Text</tspan></text></svg>
                XML,
        ];

        yield 'Removes empty SVG elements gracefully' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps comments and mixed content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text><!-- comment --><tspan>Text</tspan></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text><!-- comment --><tspan>Text</tspan></text></svg>
                XML,
        ];

        yield 'Keeps empty text elements when a style sheet depends on sibling positions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><g><text></text><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><g><text/><rect width="5" height="5"/></g></svg>',
        ];

        yield 'Removes deeply nested empty elements with siblings' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>
                        <tspan><tref xlink:href=""/></tspan>
                        <tspan></tspan>
                    </text>
                    <rect width="2" height="2"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="2" height="2"/></svg>
                XML,
        ];
    }
}
