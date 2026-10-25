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
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MigrateToSvg2;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MigrateToSvg2::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MigrateToSvg2Test extends TestCase
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
        self::assertSame($expected, $this->optimizeContent($content));
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimizeIsIdempotent(string $content, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($expected));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield from self::provideXlinkCases();
        yield from self::provideElementCases();
        yield from self::provideAttributeCases();
        yield from self::provideStyleCases();
        yield from self::provideEdgeCases();
        yield from self::provideTinyCases();
    }

    #[Test]
    public function ruleIsMarkedAsNotRisky(): void
    {
        self::assertFalse(MigrateToSvg2::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(MigrateToSvg2::shouldCheckSize());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideXlinkCases(): iterable
    {
        yield 'Keeps an animated attribute name with more than one colon' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#b"><animate attributeName="xlink:href:x" to="#c"/></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a href="#b"><animate attributeName="xlink:href:x" to="#c"/></a></svg>',
        ];

        yield 'Converts xlink:href and drops the declaration' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path id="a" d="M0 0h1"/><use xlink:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="a" d="M0 0h1"/><use href="#a"/></svg>',
        ];

        yield 'Converts xlink:href on every kind of element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><linearGradient id="g"/><linearGradient id="h" xlink:href="#g"/><pattern id="p" xlink:href="#q"/><filter id="f"><feImage xlink:href="i.png"/></filter><path id="m" d="M0 0h9"/></defs><a xlink:href="https://example.com/"><image xlink:href=" i.png "/></a><text><textPath xlink:href="#m">t</textPath></text><circle r="1"><animateMotion dur="1s"><mpath xlink:href="#m"/></animateMotion><set xlink:href="#m" attributeName="fill" to="red"/></circle><script xlink:href="s.js"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/><linearGradient id="h" href="#g"/><pattern id="p" href="#q"/><filter id="f"><feImage href="i.png"/></filter><path id="m" d="M0 0h9"/></defs><a href="https://example.com/"><image href=" i.png "/></a><text><textPath href="#m">t</textPath></text><circle r="1"><animateMotion dur="1s"><mpath href="#m"/></animateMotion><set attributeName="fill" to="red" href="#m"/></circle><script href="s.js"/></svg>',
        ];

        yield 'Converts xlink:href with a custom prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><use l:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a"/></svg>',
        ];

        yield 'Converts xlink:href in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><svg:use xlink:href="#a"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:use href="#a"/></svg:svg>',
        ];

        yield 'Converts xlink:href in a document without a namespace' => [
            '<svg xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#a"/></svg>',
            '<svg><use href="#a"/></svg>',
        ];

        yield 'Converts xlink:href declared on nested elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g xmlns:xlink="http://www.w3.org/1999/xlink"><g><use xlink:href="#a"/></g></g><g xmlns:x="http://www.w3.org/1999/xlink"><use x:href="#b"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><use href="#a"/></g></g><g><use href="#b"/></g></g></svg>',
        ];

        yield 'Drops xlink:href when a plain href is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use href="#a" xlink:href="#b"/><use href="" xlink:href="#c"/><use xlink:href="#d"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a"/><use href=""/><use href="#d"/></svg>',
        ];

        yield 'Converts xlink:show new on links without a target to a target' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:show=" new " href="#a"/><a xlink:show="new" target="_top" href="#b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#a"/><a target="_top" href="#b"/></svg>',
        ];

        yield 'Converts xlink:show new on links with an empty target' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:show="new" target="" href="#a"/><a xlink:show="new" target=" " href="#b"/><a xlink:show="new" href="#c"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a target="_blank" href="#a"/><a target=" " href="#b"/><a href="#c" target="_blank"/></svg>',
        ];

        yield 'Removes other xlink:show values and xlink:show on other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:show="replace" href="#a"/><a xlink:show="New" href="#b"/><use xlink:show="new" href="#c"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#a"/><a href="#b"/><use href="#c"/></svg>',
        ];

        yield 'Removes the XLink attributes that SVG 2 dropped' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><a l:actuate="onRequest" l:arcrole="r" l:role="r" l:type="simple" href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#a"/></svg>',
        ];

        yield 'Keeps xlink:title and unknown XLink attributes with their declaration' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#a" xlink:title="t"/><a xlink:label="l"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="t" href="#a"/><a xlink:label="l"/></svg>',
        ];

        yield 'Converts animations of xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:l="http://www.w3.org/1999/xlink"><use xlink:href="#a"><set attributeName=" xlink:href " to="#b"/><animate attributeName="l:href" values="#a;#b"/></use></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a"><set attributeName="href" to="#b"/><animate attributeName="href" values="#a;#b"/></use></svg>',
        ];

        yield 'Keeps animations of other prefixed attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:c="http://example.com/c"><use href="#a"><set attributeName="c:href" to="b"/><set attributeName="xlink:hrefs" to="b"/><set attributeName="xlink:title" to="b"/><set attributeName="href" to="b"/></use></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:c="http://example.com/c"><use href="#a"><set attributeName="c:href" to="b"/><set attributeName="xlink:hrefs" to="b"/><set attributeName="xlink:title" to="b"/><set attributeName="href" to="b"/></use></svg>',
        ];

        yield 'Drops only the declarations whose prefix is unused in attribute values' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="http://www.w3.org/1999/xlink"><g xmlns:b="http://www.w3.org/1999/xlink"><use b:href="#x" class="a:y"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="http://www.w3.org/1999/xlink"><g><use class="a:y" href="#x"/></g></svg>',
        ];

        yield 'Keeps xlink:href in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><foreignObject><svg><use xlink:href="#a"><set attributeName="xlink:href" to="#b"/></use></svg><math xmlns="http://www.w3.org/1998/Math/MathML" xlink:href="#c" xlink:type="simple"/></foreignObject><use xlink:href="#d"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><foreignObject><svg><use xlink:href="#a"><set attributeName="xlink:href" to="#b"/></use></svg><math xmlns="http://www.w3.org/1998/Math/MathML" xlink:href="#c" xlink:type="simple"/></foreignObject><use href="#d"/></svg>',
        ];

        yield 'Converts XLink attributes despite scripts and event attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><script>a()</script><use xlink:href="#a" onclick="b()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><use onclick="b()" href="#a"/></svg>',
        ];

        yield 'Keeps XLink attributes when a style element mentions xlink' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"><style>@namespace XLink url(http://www.w3.org/1999/xlink);</style><use xlink:href="#a" xlink:type="simple"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>@namespace XLink url(http://www.w3.org/1999/xlink);</style><use xlink:href="#a" xlink:type="simple"/></svg>',
        ];

        yield 'Keeps XLink attributes when a style element mentions href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>use[HREF]{fill:red}</style><use xlink:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>use[HREF]{fill:red}</style><use xlink:href="#a"/></svg>',
        ];

        yield 'Converts XLink attributes when a style element does not mention links' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>use{fill:red}</style><use xlink:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>use{fill:red}</style><use href="#a"/></svg>',
        ];

        yield 'Keeps XLink attributes and removed attributes when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events" version="1.1"><use xlink:href="#a" ev:event="click"/></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events" version="1.1"><use xlink:href="#a" ev:event="click"/></svg>',
        ];

        yield 'Keeps XLink attributes and removed attributes with a linked style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events" version="1.1"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><use xlink:href="#a" ev:event="click"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events" version="1.1"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><use xlink:href="#a" ev:event="click"/></svg>',
        ];

        yield 'Keeps XLink attributes and removed attributes with an imported style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events" version="1.1"><style>@import url(a.css);</style><use xlink:href="#a" ev:event="click"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events" version="1.1"><style>@import url(a.css);</style><use xlink:href="#a" ev:event="click"/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideElementCases(): iterable
    {
        yield 'Removes animateColor' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"><animateColor attributeName="fill" from="red" to="blue" dur="1s"><desc>a</desc></animateColor></rect><g><animateColor attributeName="stroke" to="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/><g/></svg>',
        ];

        yield 'Removes animateColor in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:animateColor attributeName="fill" to="red"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps animateColor in foreign content and with another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><animateColor attributeName="fill" to="red"/></svg></foreignObject><animatecolor attributeName="fill" to="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><animateColor attributeName="fill" to="red"/></svg></foreignObject><animatecolor attributeName="fill" to="red"/></svg>',
        ];

        yield 'Removes tref with its children' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><text id="t">Hello <tspan>world</tspan></text></defs><text><tref xlink:href="#t" x="1" dy="2" fill="red"><title>a</title></tref></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><text id="t">Hello <tspan>world</tspan></text></defs><text/></svg>',
        ];

        yield 'Removes tref whatever it references' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="a">A</text><text id="a">B</text><text xml:id="c">C</text><text><tref href=" #a "/><tref href="#c"/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="a">A</text><text id="a">B</text><text>C</text><text/></svg>',
        ];

        yield 'Removes tref with href and xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>text{fill:red}</style><text id="a">A</text><text id="b">B</text><text><tref href="#a" xlink:href="#b"/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>text{fill:red}</style><text id="a">A</text><text id="b">B</text><text/></svg>',
        ];

        yield 'Removes tref and altGlyph when a style element mentions xlink' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>[xlink|href]{fill:red}</style><text xml:id="c">C</text><text xml:id="c">D</text><text><tref xlink:href="#c"/><altGlyph xlink:href="#g">B</altGlyph></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[xlink|href]{fill:red}</style><text>C</text><text>D</text><text/></svg>',
        ];

        yield 'Removes tref that cannot be resolved' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="a">A</text><text>x<tref href="#missing"/><tref href="xa"/><tref href="a.svg#a"/><tref href="#"/><tref/>y</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="a">A</text><text>xy</text></svg>',
        ];

        yield 'Removes altGlyph with its content' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><text><altGlyph x="1" y="2" dx="3" dy="4" rotate="5" glyphRef="g" format="svg" xlink:href="#g" href="#g" fill="red">A<tspan>B</tspan></altGlyph></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text/></svg>',
        ];

        yield 'Keeps tref and altGlyph in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="a">A</text><foreignObject><svg><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="a">A</text><foreignObject><svg><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg></foreignObject></svg>',
        ];

        yield 'Removes tref and altGlyph when a style element selects tspan' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>TSpan{fill:red}</style><text id="a">A</text><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>TSpan{fill:red}</style><text id="a">A</text><text/></svg>',
        ];

        yield 'Removes tref when a style element selects it' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>tref{fill:red}</style><text id="a">A</text><text><tref href="#a"/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>tref{fill:red}</style><text id="a">A</text><text/></svg>',
        ];

        yield 'Removes altGlyph when a style element selects it' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>altGlyph{fill:red}</style><text><altGlyph>B</altGlyph></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>altGlyph{fill:red}</style><text/></svg>',
        ];

        yield 'Keeps tref and altGlyph when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><text id="a">A</text><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><text id="a">A</text><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg>',
        ];

        yield 'Removes the elements that SVG 2 dropped' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><font id="f" horiz-adv-x="1"><font-face font-family="F"><font-face-src><font-face-uri href="f.svg"><font-face-format string="svg"/></font-face-uri><font-face-name name="F"/></font-face-src><definition-src href="d"/></font-face><missing-glyph d="M0 0"/><glyph unicode="a"/><hkern u1="a" u2="b" k="1"/><vkern u1="a" u2="b" k="1"/></font><altGlyphDef id="d"><altGlyphItem><glyphRef href="#g"/></altGlyphItem></altGlyphDef><color-profile name="p"/></defs><glyphRef href="#g"/><font-face-src/><rect width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs/><rect width="1"/></svg>',
        ];

        yield 'Removes nested elements that SVG 2 dropped one by one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><glyphRef href="#g"/><font-face-src/><font-face-uri/><font-face-format/><font-face-name/><glyph/><missing-glyph/><hkern/><vkern/><definition-src/><altGlyphItem/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Keeps dropped elements in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><font xmlns="http://www.w3.org/1999/xhtml">a</font><svg><cursor id="c"/></svg></foreignObject><rect cursor="url(#c), auto"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><font xmlns="http://www.w3.org/1999/xhtml">a</font><svg><cursor id="c"/></svg></foreignObject><rect cursor="url(#c), auto"/></svg>',
        ];

        yield 'Keeps dropped elements when a style element depends on siblings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><font/><rect width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><font/><rect width="1"/></svg>',
        ];

        yield 'Removes cursor elements and keeps the cursor properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><cursor id="c" href="c.png"/><cursor id="d"/><g/><rect cursor="crosshair"/><rect cursor="url(#c),pointer"/><rect cursor=" url( \'#c\' ) 1 2 , url(#e), URL(#d) ,auto"/><rect cursor="url(#c)"/><rect cursor="url(c.cur), url(#cc), move"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><rect cursor="crosshair"/><rect cursor="url(#c),pointer"/><rect cursor=" url( \'#c\' ) 1 2 , url(#e), URL(#d) ,auto"/><rect cursor="url(#c)"/><rect cursor="url(c.cur), url(#cc), move"/></svg>',
        ];

        yield 'Keeps references to other removed elements in cursor attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><font id="f"/><cursor id="c"/><rect cursor="url(#f), auto"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect cursor="url(#f), auto"/></svg>',
        ];

        yield 'Removes cursor elements without an id and keeps cursor attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><cursor href="c.png"/><rect cursor="url(#c), pointer"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect cursor="url(#c), pointer"/></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideAttributeCases(): iterable
    {
        yield 'Removes the attributes that SVG 2 dropped' => [
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" baseProfile="full" contentScriptType="text/ecmascript" contentStyleType="text/css" zoomAndPan="magnify" enable-background="new 0 0 1 1"><view viewTarget="a"/><image color-profile="sRGB" clip="rect(0 1 1 0)"/><text kerning="0" glyph-orientation-horizontal="0" color-rendering="auto">a</text><g requiredFeatures="http://www.w3.org/TR/SVG11/feature#Shape" externalResourcesRequired="false"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><view/><image/><text>a</text><g/></svg>',
        ];

        yield 'Keeps the attributes that SVG 2 only deprecated' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" xml:lang="en" xml:base="a/"><text glyph-orientation-vertical="90" style="clip:auto">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" xml:lang="en" xml:base="a/"><text glyph-orientation-vertical="90" style="clip:auto">a</text></svg>',
        ];

        yield 'Keeps attributes with the same name in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://example.com/c" c:version="1" c:kerning="0"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://example.com/c" c:version="1" c:kerning="0"/>',
        ];

        yield 'Keeps dropped attributes in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1"><foreignObject><svg version="1.1"/><div xmlns="http://www.w3.org/1999/xhtml" style="kerning:0" clip="a">x</div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg version="1.1"/><div xmlns="http://www.w3.org/1999/xhtml" style="kerning:0" clip="a">x</div></foreignObject></svg>',
        ];

        yield 'Keeps dropped attributes selected by a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" baseProfile="full"><style>[version]{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1"><style>[version]{fill:red}</style></svg>',
        ];

        yield 'Removes XML Events attributes and their declaration' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events" xmlns:e="http://www.w3.org/2001/xml-events"><rect ev:event="click" e:handler="#h" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>',
        ];

        yield 'Keeps XML Events attributes in foreign content or selected by a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><style>[ev|handler]{fill:red}</style><foreignObject><svg><rect ev:event="click"/></svg></foreignObject><rect ev:handler="#h" ev:event="click"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><style>[ev|handler]{fill:red}</style><foreignObject><svg><rect ev:event="click"/></svg></foreignObject><rect ev:handler="#h"/></svg>',
        ];

        yield 'Removes XML Events elements and their declaration' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><ev:listener event="click" handler="#h"/><rect ev:event="click"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps XML Events elements when a style element depends on siblings, and removes their attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><style>rect:first-child{fill:red}</style><g><ev:listener event="click"/><rect ev:event="click"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><style>rect:first-child{fill:red}</style><g><ev:listener event="click"/><rect/></g></svg>',
        ];

        yield 'Keeps XML Events elements with content that can be referenced or takes effect' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><ev:listener id="l"/><ev:listener><rect id="r"/></ev:listener><ev:listener><style>rect{fill:red}</style></ev:listener><ev:listener><g/></ev:listener><use href="#r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><ev:listener><rect id="r"/></ev:listener><ev:listener><style>rect{fill:red}</style></ev:listener><use href="#r"/></svg>',
        ];

        yield 'Keeps dropped elements with content that can be referenced or takes effect' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><video><rect id="r"/></video><video><style>rect{fill:red}</style></video><font><set href="#r" attributeName="fill" to="red"/></font><font id="f"><font-face font-family="F"/><glyph id="g" d="M0 0"/><missing-glyph/></font><textArea><g/></textArea><use href="#r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><video><rect id="r"/></video><video><style>rect{fill:red}</style></video><font><set href="#r" attributeName="fill" to="red"/></font><use href="#r"/></svg>',
        ];

        yield 'Keeps dropped elements that a switch may render' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><video/><rect/></switch><switch><font><glyph/></font><rect/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><video/><rect/></switch><switch><font/><rect/></switch></svg>',
        ];

        yield 'Keeps XML Events elements in foreign content or selected by a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><style>ev|listener, [x|listener]{fill:red}</style><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><ev:listener event="a"/></div></foreignObject><ev:listener event="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><style>ev|listener, [x|listener]{fill:red}</style><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"><ev:listener event="a"/></div></foreignObject><ev:listener event="b"/></svg>',
        ];

        yield 'Keeps documents without SVG 1.1 features untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a"/><text style="Fill: red ;stroke:blue">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a"/><text style="Fill: red ;stroke:blue">a</text></svg>',
        ];

        yield 'Keeps empty documents untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideStyleCases(): iterable
    {
        yield 'Keeps the priority of a paint that falls back to none' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><solidColor id="a"/><rect style="fill:url(#a) !important"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:none !important"/></svg>',
        ];

        yield 'Removes dropped properties from inline styles' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="KERNING: 0;fill:red; enable-background:new;color-profile:auto;color-rendering:auto;glyph-orientation-horizontal:0deg">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="fill:red">a</text></svg>',
        ];

        yield 'Keeps inline styles that a style sheet selects' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[style*="kerning"]{fill:red}</style><text style="kerning:0" kerning="0">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[style*="kerning"]{fill:red}</style><text style="kerning:0">a</text></svg>',
        ];

        yield 'Removes the dropped view and redraw attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" currentView="a" useCurrentView="true" viewport="0 0 1 1" suspendRedraw="1"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes inline styles that only hold dropped properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="kerning:0;">a</text><text style="fill:red">b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text><text style="fill:red">b</text></svg>',
        ];

        yield 'Removes glyph-orientation-vertical from inline styles' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="glyph-orientation-vertical:auto">a</text><text style="Glyph-Orientation-Vertical: 0DEG ">b</text><text style="glyph-orientation-vertical:0">c</text><text style="glyph-orientation-vertical:90deg ! IMPORTANT;fill:red">d</text><text style="glyph-orientation-vertical:90">e</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text><text>b</text><text>c</text><text style="fill:red">d</text><text>e</text></svg>',
        ];

        yield 'Removes glyph-orientation-vertical values without an equivalent from inline styles' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="glyph-orientation-vertical:180deg;fill:red">a</text><text style="glyph-orientation-vertical">b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="fill:red">a</text><text>b</text></svg>',
        ];

        yield 'Keeps inline styles that cannot be split' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="kerning:0;font-family:\'a">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="kerning:0;font-family:\'a">a</text></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideEdgeCases(): iterable
    {
        yield 'Migrates every XLink attribute with the default prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#a" xlink:show="new" xlink:actuate="onRequest" xlink:type="simple" xlink:role="r" xlink:arcrole="r" xlink:title="t"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="t" href="#a" target="_blank"/></svg>',
        ];

        yield 'Migrates XLink attributes with two prefixes bound to the namespace at once' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:l="http://www.w3.org/1999/xlink"><a xlink:href="#a" l:show="new"/><use l:href="#b" xlink:type="simple"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#a" target="_blank"/><use href="#b"/></svg>',
        ];

        yield 'Keeps XLink attributes on foreign elements outside foreignObject' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:c="http://example.com/c"><c:x xlink:href="#a"><use xlink:href="#b"/></c:x></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:c="http://example.com/c"><c:x xlink:href="#a"><use href="#b"/></c:x></svg>',
        ];

        yield 'Drops an unused XLink declaration from an empty document' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Treats XLink and SVG values case-sensitively' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" VERSION="1"><a xlink:show="NEW" href="#a"/><text style="glyph-orientation-vertical:AUTO">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" VERSION="1"><a href="#a"/><text>a</text></svg>',
        ];

        yield 'Migrates deeply nested elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><g><g><g><g><g kerning="0"><use xlink:href="#a"><animateColor attributeName="fill" to="red"/></use></g></g></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><g><g><use href="#a"/></g></g></g></g></g></g></svg>',
        ];

        yield 'Removes tref pointing at itself, at nested tspans, at a missing id and at another document' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="n">a<tspan>b<tspan>c</tspan></tspan>d</text><text><tref id="s" href="#s"/><tref href="#n"/><tref href="#none"/><tref href="other.svg#n"/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text id="n">a<tspan>b<tspan>c</tspan></tspan>d</text><text/></svg>',
        ];

        yield 'Removes altGlyph with nested children and positioning lists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text><altGlyph x="1 2 3" y="4 5" dx="0 1" dy="1 0" rotate="0 10 20" glyphRef="g">A<tspan x="9">B<tspan>C</tspan></tspan>D</altGlyph></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text/></svg>',
        ];

        yield 'Removes animateColor with every timing attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animateColor attributeName="fill" attributeType="CSS" begin="0s;a.end" dur="2s" end="10s" min="1s" max="5s" restart="whenNotActive" repeatCount="indefinite" repeatDur="8s" fill="freeze" calcMode="spline" values="red;blue" keyTimes="0;1" keySplines="0 0 1 1" additive="sum" accumulate="sum"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Migrates inline styles with comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="fill:red;/* note; kerning:0 */kerning:0;KERNING:AUTO !important;stroke:blue">a</text><text style="/* x */fill:red">b</text><text style="glyph-orientation-vertical: /*a*/ 90 /*b*/">c</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="fill:red;stroke:blue">a</text><text style="/* x */fill:red">b</text><text>c</text></svg>',
        ];

        yield 'Keeps inline styles with an unclosed comment or a comment marker in a string' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="kerning:0;/* open">a</text><text style="font-family:&quot;a/*b*/&quot;;kerning:0">b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="kerning:0;/* open">a</text><text style="font-family:&quot;a/*b*/&quot;;kerning:0">b</text></svg>',
        ];

        yield 'Migrates documents with entities and CDATA' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style><![CDATA[rect{fill:red}]]></style><text id="a">x &amp; y &#60;</text><text><tref xlink:href="&#35;a"/></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><text id="a">x &amp; y &lt;</text><text/></svg>',
        ];

        yield 'Processes a later element after skipping an earlier one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><animateColor attributeName="fill" to="red"/><cursor id="c"/><text><altGlyph>A</altGlyph></text></svg></foreignObject><text id="a">A</text><animateColor attributeName="fill" to="blue"/><cursor id="d"/><text><tref href="#none"/><tref href="#a"/><altGlyph>B</altGlyph></text><rect cursor="url(#c), auto"/><rect cursor="url(#d), auto"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><animateColor attributeName="fill" to="red"/><cursor id="c"/><text><altGlyph>A</altGlyph></text></svg></foreignObject><text id="a">A</text><text/><rect cursor="url(#c), auto"/><rect cursor="url(#d), auto"/></svg>',
        ];

        yield 'Migrates an Illustrator document' => [
            '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 10 10" enable-background="new 0 0 10 10" xml:space="preserve"><g><defs><rect id="SVGID_1_" width="10" height="10"/></defs><clipPath id="SVGID_2_"><use xlink:href="#SVGID_1_" overflow="visible"/></clipPath><g clip-path="url(#SVGID_2_)"><image width="10" height="10" xlink:href="a.png" color-profile="sRGB"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" x="0px" y="0px" viewBox="0 0 10 10" xml:space="preserve"><g><defs><rect id="SVGID_1_" width="10" height="10"/></defs><clipPath id="SVGID_2_"><use overflow="visible" href="#SVGID_1_"/></clipPath><g clip-path="url(#SVGID_2_)"><image width="10" height="10" href="a.png"/></g></g></svg>',
        ];

        yield 'Migrates an Inkscape document with SVG fonts and text references' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" version="1.1" inkscape:version="1.0" sodipodi:docname="a.svg"><sodipodi:namedview inkscape:zoom="1"/><defs><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b" xlink:href="#a" inkscape:collect="always"/><font id="f" horiz-adv-x="500"><font-face font-family="F" units-per-em="1000"/><missing-glyph/><glyph unicode="a" d="M0 0"/></font></defs><g inkscape:groupmode="layer" inkscape:label="L"><text style="font-family:F;kerning:auto;glyph-orientation-vertical:0" glyph-orientation-vertical="0"><tref xlink:href="#t"/></text><text id="t">T</text><rect style="fill:url(#b)" width="1" height="1"><animateColor attributeName="fill" to="red" xlink:href="#r"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" inkscape:version="1.0" sodipodi:docname="a.svg"><sodipodi:namedview inkscape:zoom="1"/><defs><linearGradient id="a"><stop offset="0"/></linearGradient><linearGradient id="b" inkscape:collect="always" href="#a"/></defs><g inkscape:groupmode="layer" inkscape:label="L"><text style="font-family:F" glyph-orientation-vertical="0"/><text id="t">T</text><rect style="fill:url(#b)" width="1" height="1"/></g></svg>',
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private static function provideTinyCases(): iterable
    {
        yield 'Removes the SVG Tiny 1.2 elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><textArea width="9" height="9">a<tbreak/>b</textArea><text>c<tbreak/>d</text><animation href="a.svg" dur="1s"/><video href="a.mp4"/><audio href="a.ogg"/><prefetch href="a.png"/><handler type="application/ecmascript">a()</handler><listener event="click"/><rect width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>cd</text><rect width="1"/></svg>',
        ];

        yield 'Keeps SVG Tiny 1.2 elements in foreign content and discard' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><video xmlns="http://www.w3.org/1999/xhtml" src="a.mp4">v</video><svg><textArea>a</textArea></svg></foreignObject><rect width="1"><discard begin="2s"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><video xmlns="http://www.w3.org/1999/xhtml" src="a.mp4">v</video><svg><textArea>a</textArea></svg></foreignObject><rect width="1"><discard begin="2s"/></rect></svg>',
        ];

        yield 'Removes the SVG Tiny 1.2 attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" snapshotTime="1" syncBehaviorDefault="locked" syncToleranceDefault="1s" focusHighlight="none"><g focusable="true" nav-next="#a" nav-prev="#a" nav-up="#a" nav-up-right="#a" nav-right="#a" nav-down-right="#a" nav-down="#a" nav-down-left="#a" nav-left="#a" nav-up-left="#a"/><textArea/><text editable="simple" display-align="center" line-increment="10">a</text><animate syncBehavior="locked" syncTolerance="1s" syncMaster="true"/><g transformBehavior="pinned" overlay="top" initialVisibility="always" requiredFormats="image/png" requiredFonts="Arial" solid-color="red" solid-opacity="0.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/><text>a</text><animate/><g/></svg>',
        ];

        yield 'Removes SVG Tiny 1.2 properties from inline styles' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="display-align:center;Line-Increment:auto;fill:red;solid-color:red;solid-opacity:1">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="fill:red">a</text></svg>',
        ];

        yield 'Converts the SVG Tiny 1.2 timeline attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" playbackOrder="forwardOnly" timelineBegin=" onStart "><svg playbackOrder="all" timelineBegin="onLoad"/><svg playbackOrder="forwardonly" timelineBegin="OnLoad"/><svg playbackOrder="all" playbackorder="forwardonly"/><g playbackOrder="all" timelineBegin="onLoad"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" playbackorder="forwardonly" timelinebegin="loadbegin"><svg playbackorder="all" timelinebegin="loadend"/><svg/><svg playbackorder="forwardonly"/><g/></svg>',
        ];

        yield 'Keeps the SVG Tiny 1.2 timeline attributes when a style element selects them' => [
            '<svg xmlns="http://www.w3.org/2000/svg" playbackOrder="all" timelineBegin="onLoad"><style>[playbackOrder]{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" playbackOrder="all" timelinebegin="loadend"><style>[playbackOrder]{fill:red}</style></svg>',
        ];

        yield 'Replaces references to a removed solidColor with their fallback' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><solidColor id="s" solid-color="red"/><solidColor xml:id="t" solid-color="blue"/><linearGradient id="g"/></defs><rect fill="url(#s) green"/><rect fill=" url( \'#t\' ) "/><rect stroke="URL(#s)"/><rect fill="url(#g)" stroke="url(#other) red"/><rect style="fill:url(#s) blue !important;stroke: url(#t) !important;color:url(#s)"/><rect style="fill:url(#g)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/></defs><rect fill="green"/><rect fill=" url( \'#t\' ) "/><rect stroke="none"/><rect fill="url(#g)" stroke="url(#other) red"/><rect style="fill:blue !important;stroke: url(#t) !important;color:url(#s)"/><rect style="fill:url(#g)"/></svg>',
        ];

        yield 'Keeps references to a removed solidColor that only had an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[id]{fill:red}</style><solidColor xml:id="t"/><rect fill="url(#t) red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[id]{fill:red}</style><rect fill="url(#t) red"/></svg>',
        ];

        yield 'Replaces references to any removed element with their fallback' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><font id="f"/><textArea id="t"/><rect fill="url(#f) red"/><rect fill="url(#t)"/><rect style="stroke:url(#f) blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/><rect fill="none"/><rect style="stroke:blue"/></svg>',
        ];

        yield 'Keeps removable elements whose id is used more than once' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><solidColor id="s"/><linearGradient id="s"/><font id="f"/><font id="f"/><font id="g"/><rect fill="url(#s) red" stroke="url(#g) blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><solidColor id="s"/><linearGradient id="s"/><font id="f"/><font id="f"/><rect fill="url(#s) red" stroke="blue"/></svg>',
        ];

        yield 'Keeps references to solidColor when it cannot be removed' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><solidColor id="s"/><rect fill="url(#s) green"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><solidColor id="s"/><rect fill="url(#s) green"/></svg>',
        ];

        yield 'Removes xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect xml:id="a" width="1"/><use href="#a"/><g xml:id="b" id="b"/><g xml:id="c" id="d"/><g id="e"/><g xml:id="e"/><g xml:id="f"/><g xml:id="f"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/><use href="#a"/><g id="b"/><g id="d"/><g id="e"/><g/><g/><g/></svg>',
        ];

        yield 'Removes xml:id in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect xml:id="a"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect/></svg:svg>',
        ];

        yield 'Keeps xml:id in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg xml:id="a"/></foreignObject><c:x xmlns:c="http://example.com/c" xml:id="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg xml:id="a"/></foreignObject><c:x xmlns:c="http://example.com/c" xml:id="b"/></svg>',
        ];

        yield 'Removes xml:id that a style element selects by id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#a{fill:red}</style><rect xml:id="a"/><rect xml:id="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#a{fill:red}</style><rect/><rect/></svg>',
        ];

        yield 'Keeps xml:id when a style element selects ids by attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[ID^=b]{fill:red}</style><rect xml:id="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[ID^=b]{fill:red}</style><rect xml:id="b"/></svg>',
        ];

        yield 'Keeps xml:id when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><rect xml:id="b"/></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><rect xml:id="b"/></svg>',
        ];

        yield 'Keeps obsolete text elements with a linked style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><text id="a">A</text><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><text id="a">A</text><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg>',
        ];

        yield 'Keeps obsolete text elements with an imported style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><text id="a">A</text><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><text id="a">A</text><text><tref href="#a"/><altGlyph>B</altGlyph></text></svg>',
        ];

        yield 'Keeps xml:id with a linked style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><rect xml:id="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><rect xml:id="b"/></svg>',
        ];

        yield 'Keeps xml:id with an imported style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><rect xml:id="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><rect xml:id="b"/></svg>',
        ];

        yield 'Migrates an SVG Tiny 1.2 document' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events" version="1.2" baseProfile="tiny" viewBox="0 0 10 10" playbackOrder="all" timelineBegin="onLoad" snapshotTime="0"><solidColor xml:id="brand" solid-color="#f00"/><rect xml:id="r" width="5" height="5" fill="url(#brand) #f00" focusable="true" nav-right="url(#t)"/><textArea xml:id="t" width="5" height="5" editable="simple">Hi<tbreak/>there</textArea><use xlink:href="#r" x="5"/><handler xml:id="h" ev:event="click">a()</handler><ev:listener event="click" observer="r" handler="#h"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" playbackorder="all" timelinebegin="loadend"><rect width="5" height="5" fill="url(#brand) #f00"/><use x="5" href="#r"/></svg>',
        ];
    }

    #[Test]
    public function handlesManyNamespaceDeclarationsInLinearTime(): void
    {
        $small = $this->secondsToOptimize(2_000);
        $large = $this->secondsToOptimize(16_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><use xlink:href="#a"/></svg>');

        $start = hrtime(true);
        (new MigrateToSvg2())->optimize($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    private function optimizeContent(string $content): string
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new MigrateToSvg2());

        return $svgOptimizer->optimize()->getContent();
    }
}
