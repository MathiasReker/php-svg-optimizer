<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\XlinkMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MigrateDeprecatedAttributes::class)]
#[CoversClass(NamespaceUri::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeMigrationTrait::class)]
#[CoversTrait(DeclarationTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[CoversTrait(XlinkMigrationTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class MigrateDeprecatedAttributesTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new MigrateDeprecatedAttributes()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Converts xlink:show new to target only on links' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:show="new" href="#a"/><a xlink:show="new" href="#b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a"/><a href="#b" target="_blank"/></svg>',
        ];

        yield 'Removes baseProfile attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" baseProfile="tiny">
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Removes zoomAndPan attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" zoomAndPan="disable">
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="red"/></svg>
                XML,
        ];

        yield 'Removes requiredFeatures attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" requiredFeatures="http://www.w3.org/TR/SVG11/feature#BasicStructure">
                    <rect width="100" height="100" fill="green"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="green"/></svg>
                XML,
        ];

        yield 'Removes version attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1">
                    <rect width="100" height="100" fill="yellow"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="yellow"/></svg>
                XML,
        ];

        yield 'Removes glyph-orientation-vertical and xml:base' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:base="a/"><text glyph-orientation-vertical="0">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text></svg>',
        ];

        yield 'Keeps a deprecated attribute that a style sheet selects' => [
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" baseProfile="full"><style>[version]{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1"><style>[version]{fill:red}</style></svg>',
        ];

        yield 'Keeps xml:lang and xml:base when a style sheet selects lang' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:lang="en" xml:base="a/" version="1.1"><style>[lang]{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:lang="en" xml:base="a/"><style>[lang]{fill:red}</style></svg>',
        ];

        yield 'Keeps XLink attributes when a style sheet mentions href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>[href]{fill:red}</style><a xlink:href="#a" xlink:title="t" xlink:show="new"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>[href]{fill:red}</style><a xlink:href="#a" xlink:title="t" xlink:show="new"/></svg>',
        ];

        yield 'Keeps attributes in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" version="1" xml:lang="en" xlink:title="t"/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" version="1" xml:lang="en" xlink:title="t"></div></foreignObject></svg>',
        ];

        yield 'Replaces xlink:href with href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="#icon" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML,
        ];

        yield 'Removes xlink:title from an element other than a link, which ignores it' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <image xlink:title="Image Title" href="image.png"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><image href="image.png"/></svg>
                XML,
        ];

        yield 'Replaces xlink:title of a link with a title element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="Tip"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><title>Tip</title></a></svg>',
        ];

        yield 'Removes xlink:title from text content elements without adding a title element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style xlink:title="s">rect{fill:red}</style><title xlink:title="t">Doc</title><g xlink:title="g"><title>G</title></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><title>Doc</title><g><title>G</title></g></svg>',
        ];

        yield 'Keeps xlink:title of a link when a style sheet depends on the siblings of an element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>rect:first-child{fill:red}</style><a xlink:title="Tip"><rect/></a><rect xlink:title="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>rect:first-child{fill:red}</style><a xlink:title="Tip"><rect/></a><rect/></svg>',
        ];

        yield 'Puts the title element before existing children' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="Go &amp; see" href="#x"><text>x</text></a></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><a href="#x"><title>Go &amp; see</title><text>x</text></a></svg>
                XML,
        ];

        yield 'Keeps xlink:title next to an existing title element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="Old"><title>New</title></a></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="Old"><title>New</title></a></svg>
                XML,
        ];

        yield 'Drops an empty xlink:title' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title=" "/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><a/></svg>
                XML,
        ];

        yield 'Converts xlink:show new to target blank' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:show="new" href="#a"/><a xlink:show="replace" href="#b"/><a xlink:show="new" target="frame" href="#c"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><a href="#a" target="_blank"/><a href="#b"/><a target="frame" href="#c"/></svg>
                XML,
        ];

        yield 'Keeps the cursor presentation attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect cursor="pointer" width="1" height="1"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect cursor="pointer" width="1" height="1"/></svg>
                XML,
        ];

        yield 'Removes xmlns:xlink namespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect width="100" height="100" fill="pink"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="pink"/></svg>
                XML,
        ];

        yield 'Handles combination of attributes and xlink' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" baseProfile="full" version="1.1">
                    <use xlink:href="#icon" zoomAndPan="disable" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML,
        ];

        yield 'Does not remove non-deprecated attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect fill="purple"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="purple"/></svg>
                XML,
        ];

        yield 'Handles attributes with namespaces correctly' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="image.svg"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="image.svg"/></svg>
                XML,
        ];

        yield 'Handles no xlink attributes present' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <circle cx="100" cy="100" r="50" fill="orange"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><circle cx="100" cy="100" r="50" fill="orange"/></svg>
                XML,
        ];

        yield 'Removes xlink attributes in mixed content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="300" height="300">
                    <circle cx="100" cy="100" r="50" fill="green"/>
                    <use xlink:href="#someIcon" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><circle cx="100" cy="100" r="50" fill="green"/><use href="#someIcon"/></svg>
                XML,
        ];

        yield 'Does not replace if new attribute value is the same' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="#icon" href="#icon"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML,
        ];

        yield 'Adobe Illustrator tiny base profile example' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="500" height="180" baseProfile="tiny" version="1.2"><switch><foreignObject width="1" height="1" x="0" y="0" requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/><g><g fill="#3AB879"><path d=""/></g></g></switch></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="500" height="180"><switch><foreignObject width="1" height="1" x="0" y="0" requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/><g><g fill="#3AB879"><path d=""/></g></g></switch></svg>
                XML,
        ];

        yield 'Leaves unrelated attributes intact' => [
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle fill="red"/></svg>',
        ];

        yield 'Empty SVG' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'SVG with no deprecated attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'SVG with all deprecated attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" baseProfile="full" contentScriptType="text/ecmascript" contentStyleType="text/css" version="1.1" zoomAndPan="magnify"><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Replaces xml:lang with lang' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:lang="en"><text>Hello</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" lang="en"><text>Hello</text></svg>',
        ];

        yield 'Keeps href over xlink:href, which it takes precedence over' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#old" href="#new"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#new"/></svg>',
        ];

        yield 'Keeps xml:lang over lang, as it takes precedence' => [
            '<svg xmlns="http://www.w3.org/2000/svg" lang="de" xml:lang="en"><text>Hello</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" lang="en"><text>Hello</text></svg>',
        ];

        yield 'Replaces an empty xlink:href with an empty href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href=""><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href=""><text>x</text></a></svg>',
        ];

        yield 'Keeps xlink:show new with surrounding whitespace, which browsers do not treat as new' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:show=" new " href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#a"/></svg>',
        ];

        yield 'Converts xlink:title after an element that keeps its xlink:title' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="Old"><title>New</title></a><a xlink:title="Tip"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="Old"><title>New</title></a><a><title>Tip</title><text>x</text></a></svg>',
        ];

        yield 'Converts xlink:title even where the output grows' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a xmlns:xlink="http://www.w3.org/1999/xlink" xlink:title="t"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><title>t</title></a></svg>',
        ];

        yield 'Replaces xlink:href bound to a custom prefix instead of adding an empty href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><defs><rect id="r" width="5" height="5"/></defs><use f:href="#r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="r" width="5" height="5"/></defs><use href="#r"/></svg>',
        ];

        yield 'Migrates an animation of a prefixed href along with xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><rect id="a"/><rect id="b"/></defs><use xlink:href="#a"><set attributeName="xlink:href" to="#b" begin="1s"/></use></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="a"/><rect id="b"/></defs><use href="#a"><set attributeName="href" to="#b" begin="1s"/></use></svg>',
        ];

        yield 'Removes xlink:actuate and xlink:role' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#a" xlink:actuate="onRequest" xlink:role="x"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#a"><text>x</text></a></svg>',
        ];

        yield 'Keeps the XLink declaration on the root while an attribute value uses the prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><animate attributeName="xlink:title" to="t"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect><animate attributeName="xlink:title" to="t"/></rect></svg>',
        ];

        yield 'Replaces only the later of two xlink:href attributes when the first element has href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use href="#a" xlink:href="#b"/><use xlink:href="#c"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a"/><use href="#c"/></svg>',
        ];

        yield 'Replaces deprecated attributes in a document with a prefixed SVG namespace' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"><svg:use xlink:href="#a"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:use href="#a"/></svg:svg>',
        ];

        yield 'Keeps multibyte xml:lang and xlink:title values' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="日本 😀" xml:lang="ja"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a lang="ja"><title>日本 😀</title><text>x</text></a></svg>',
        ];

        yield 'Handles an empty document' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
        ];

        yield 'Handles a root with only text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
        ];

        yield 'Handles a 60 levels deep structure' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect xlink:href="#a" xml:base="b"/>' . str_repeat('</g>', 60) . '</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect href="#a"/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'Handles a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" xlink:href="#a" xml:base="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" href="#a"/></svg>',
        ];

        yield 'Handles right-to-left, emoji, combining and astral text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text xlink:href="#a" xml:base="b">שלום 😀 é 𝒜</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text href="#a">שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Handles values padded with tabs, newlines and no-break spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect xlink:href="#a" xml:base="b" fill="&#9; red&#10;&#xA0;"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"><rect fill=\"&#9; red&#10;\u{a0}\" href=\"#a\"/></svg>",
        ];

        yield 'Handles comments between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect xlink:href="#a" xml:base="b"/><!--4--></g><!--5--></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect href="#a"/><!--4--></g><!--5--></svg>',
        ];

        yield 'Leaves the document untouched with an external style sheet' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect xlink:href="#a" xml:base="b"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect xlink:href="#a" xml:base="b"/></svg>',
        ];

        yield 'Handles CDATA sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text xlink:href="#a" xml:base="b"><![CDATA[a<b&c]]></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text href="#a">a&lt;b&amp;c</text></svg>',
        ];

        yield 'Handles entities and character references in values and text' => [
            '<!DOCTYPE svg [<!ENTITY e "v&#x41;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text xlink:href="#a" xml:base="b" class="&e;">&e;&#x42;&#66;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text class="vA" href="#a">vABB</text></svg>',
        ];

        yield 'Handles repeated elements where only a later one matches' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect xlink:href="#a" xml:base="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect href="#a"/></svg>',
        ];

        yield 'Migrates elements after foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" zoomAndPan="magnify"/></foreignObject><rect zoomAndPan="magnify" xlink:title="t"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" zoomAndPan="magnify"></div></foreignObject><rect/></svg>',
        ];

        yield 'Handles mixed-case element names' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect xlink:href="#a" xml:base="b"/><RECT xlink:href="#a" xml:base="b"/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect href="#a"/><RECT href="#a"/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
        ];
    }
}
