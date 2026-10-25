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
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgElementAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgElementTrait;
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
#[CoversClass(FixAttributeNames::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(SvgAttribute::class)]
#[UsesClass(SvgElementAttributes::class)]
final class FixAttributeNamesTest extends TestCase
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
        $svgOptimizer->addPass(new FixAttributeNames());

        $actual = $svgOptimizer->allowRisky()->optimize()->getContent();

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps event handler attributes in another case, which are not event handlers' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onLoad="f()"><rect ONCLICK="f()" Fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onLoad="f()"><rect ONCLICK="f()" fill="red"/></svg>',
        ];

        yield 'Keeps a misspelled attribute when the canonical attribute exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill-opacity="1" fillOpacity="0.1"/><rect fillOpacity="0.1" fill-opacity="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill-opacity="1" fillOpacity="0.1"/><rect fillOpacity="0.1" fill-opacity="1"/></svg>',
        ];

        yield 'Keeps a misspelled namespaced attribute when the canonical attribute exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:fill-opacity="1" x:fillOpacity="0.1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:fill-opacity="1" x:fillOpacity="0.1"/></svg>',
        ];

        yield 'Normalizes clippath variations' => [
            '<svg><rect clipPath="rect"/></svg>',
            '<svg><rect clip-path="rect"/></svg>',
        ];

        yield 'Normalizes stroke-width and fill' => [
            '<svg><circle fill="blue" stRokE-width="1"/></svg>',
            '<svg><circle fill="blue" stroke-width="1"/></svg>',
        ];

        yield 'Keeps referrerpolicy lowercase as the specifications spell it' => [
            '<svg><a referrerPolicy="origin"/></svg>',
            '<svg><a referrerpolicy="origin"/></svg>',
        ];

        yield 'Normalizes attributes added for SVG fonts and masking' => [
            '<svg><mask MASK-TYPE="alpha"/><glyph Vert-Adv-Y="1" orientation="h"/></svg>',
            '<svg><mask mask-type="alpha"/><glyph orientation="h" vert-adv-y="1"/></svg>',
        ];

        yield 'Ignores attributes not in SvgAttribute enum' => [
            '<svg><rect anotherAttr="1"/></svg>',
            '<svg><rect anotherAttr="1"/></svg>',
        ];

        yield 'Clip-path lowercase' => [
            '<svg><rect clipPath="b"/></svg>',
            '<svg><rect clip-path="b"/></svg>',
        ];

        yield 'Clip-path mixed case' => [
            '<svg><rect ClIpPaTh="b"/></svg>',
            '<svg><rect clip-path="b"/></svg>',
        ];

        yield 'Normalizes root svg attribute' => [
            '<svg ClIpPaTh="b"></svg>',
            '<svg clip-path="b"/>',
        ];

        yield 'Normalizes multiple root svg attributes' => [
            '<svg ClIpPaTh="b" stRokE-width="2"></svg>',
            '<svg clip-path="b" stroke-width="2"/>',
        ];

        yield 'Normalizes both root and child attributes' => [
            '<svg ClIpPaTh="a"><rect stRokE-width="1"/></svg>',
            '<svg clip-path="a"><rect stroke-width="1"/></svg>',
        ];

        yield 'Keeps already normalized attributes unchanged' => [
            '<svg clip-path="a"><rect stroke-width="1"/></svg>',
            '<svg clip-path="a"><rect stroke-width="1"/></svg>',
        ];

        yield 'Handles attributes without hyphen but matching enum' => [
            '<svg><rect strokewidth="3"/></svg>',
            '<svg><rect stroke-width="3"/></svg>',
        ];

        yield 'Preserves attribute values exactly' => [
            '<svg><rect clipPath="url(#myClip)"/></svg>',
            '<svg><rect clip-path="url(#myClip)"/></svg>',
        ];

        yield 'Multiple attributes on same element' => [
            '<svg><rect ClIpPaTh="a" fIll="red" stRokE-width="4"/></svg>',
            '<svg><rect clip-path="a" fill="red" stroke-width="4"/></svg>',
        ];

        yield 'Root svg with multiple children and mixed attributes' => [
            '<svg ClIpPaTh="a"><g><rect stRokE-width="1"/></g><circle FILL="red"/></svg>',
            '<svg clip-path="a"><g><rect stroke-width="1"/></g><circle fill="red"/></svg>',
        ];

        yield 'Deeply nested elements' => [
            '<svg><g><g><g><rect ClIpPaTh="x"/></g></g></g></svg>',
            '<svg><g><g><g><rect clip-path="x"/></g></g></g></svg>',
        ];

        yield 'Attributes differing only by case already normalized' => [
            '<svg><rect clip-path="a" fill="blue"/></svg>',
            '<svg><rect clip-path="a" fill="blue"/></svg>',
        ];

        yield 'Attributes with numbers preserved' => [
            '<svg><rect stRokE-width="0.5"/></svg>',
            '<svg><rect stroke-width="0.5"/></svg>',
        ];

        yield 'Attributes with percentage values preserved' => [
            '<svg><rect ClIpPaTh="10%"/></svg>',
            '<svg><rect clip-path="10%"/></svg>',
        ];

        yield 'Multiple unknown attributes mixed with known ones' => [
            '<svg><rect foo="1" ClIpPaTh="a" bar="2"/></svg>',
            '<svg><rect foo="1" bar="2" clip-path="a"/></svg>',
        ];

        yield 'Root svg with unknown attribute only' => [
            '<svg foo="bar"></svg>',
            '<svg foo="bar"/>',
        ];

        yield 'Root svg with known and unknown attributes' => [
            '<svg foo="bar" ClIpPaTh="x"></svg>',
            '<svg foo="bar" clip-path="x"/>',
        ];

        yield 'Handles attributes on self-closing child nodes' => [
            '<svg><rect ClIpPaTh="a"/></svg>',
            '<svg><rect clip-path="a"/></svg>',
        ];

        yield 'Handles attributes on non-graphical elements' => [
            '<svg><defs><clipPath id="c"><rect stRokE-width="1"/></clipPath></defs></svg>',
            '<svg><defs><clipPath id="c"><rect stroke-width="1"/></clipPath></defs></svg>',
        ];

        yield 'Does not overwrite the canonical attribute when normalizing' => [
            '<svg><rect clip-path="a" ClIpPaTh="b"/></svg>',
            '<svg><rect clip-path="a" ClIpPaTh="b"/></svg>',
        ];

        yield 'Normalization is deterministic regardless of attribute order' => [
            '<svg><rect stRokE-width="1" ClIpPaTh="a"/></svg>',
            '<svg><rect stroke-width="1" clip-path="a"/></svg>',
        ];

        yield 'Whitespace around attributes is ignored by normalization' => [
            '<svg><rect   ClIpPaTh = "a"   /></svg>',
            '<svg><rect clip-path="a"/></svg>',
        ];

        yield 'Attributes on multiple sibling elements' => [
            '<svg><rect ClIpPaTh="a"/><rect ClIpPaTh="b"/></svg>',
            '<svg><rect clip-path="a"/><rect clip-path="b"/></svg>',
        ];

        yield 'Mixed correct and incorrect attributes on same element' => [
            '<svg><rect clip-path="a" stRokE-width="1"/></svg>',
            '<svg><rect clip-path="a" stroke-width="1"/></svg>',
        ];

        yield 'Handles empty attribute values' => [
            '<svg><rect ClIpPaTh=""/></svg>',
            '<svg><rect clip-path=""/></svg>',
        ];

        yield 'Handles boolean-like attributes as strings' => [
            '<svg><rect ClIpPaTh="true"/></svg>',
            '<svg><rect clip-path="true"/></svg>',
        ];

        yield 'Attributes on text elements' => [
            '<svg><text stRokE-width="1">Hello</text></svg>',
            '<svg><text stroke-width="1">Hello</text></svg>',
        ];

        yield 'Attributes on path elements' => [
            '<svg><path ClIpPaTh="a" d="M0 0"/></svg>',
            '<svg><path d="M0 0" clip-path="a"/></svg>',
        ];

        yield 'Attributes on use elements' => [
            '<svg><use href="#x" ClIpPaTh="a"/></svg>',
            '<svg><use href="#x" clip-path="a"/></svg>',
        ];

        yield 'Does not affect XML namespace attributes' => [
            '<svg xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#x"/></svg>',
            '<svg xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#x"/></svg>',
        ];

        yield 'Does not normalize namespaced attributes' => [
            '<svg><use xlink:href="#x" ClIpPaTh="a"/></svg>',
            '<svg><use xlink:href="#x" clip-path="a"/></svg>',
        ];

        yield 'Handles large number of attributes' => [
            '<svg><rect ClIpPaTh="a" stRokE-width="1" fIll="red" foo="1" bar="2"/></svg>',
            '<svg><rect foo="1" bar="2" clip-path="a" stroke-width="1" fill="red"/></svg>',
        ];

        yield 'Root svg attributes mixed with namespace declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg" ClIpPaTh="a"></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" clip-path="a"/>',
        ];

        yield 'Normalization does not create duplicate namespace attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>',
        ];

        yield 'Preserves xml:space attribute' => [
            '<svg xml:space="preserve"><rect/></svg>',
            '<svg xml:space="preserve"><rect/></svg>',
        ];

        yield 'Handles attributes with invalid characters (DOM handles this, but ensures no crash)' => [
            '<svg><rect invalid-char="value"/></svg>',
            '<svg><rect invalid-char="value"/></svg>',
        ];

        yield 'Preserves viewBox attribute casing' => [
            '<svg viewBox="0 0 100 100"><rect/></svg>',
            '<svg viewBox="0 0 100 100"><rect/></svg>',
        ];

        yield 'Normalizes namespaced attribute with wrong case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:Href="foo.svg"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="foo.svg"/></svg>',
        ];

        yield 'Fixes an XLink attribute bound to a custom prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><use f:HREF="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><use f:href="#a"/></svg>',
        ];

        yield 'Fixes an XLink attribute beside the same name without a namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><use f:HREF="#a" href="#b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><use href="#b" f:href="#a"/></svg>',
        ];

        yield 'Fixes an attribute that shares its local name with a foreign attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:o="urn:o"><rect fillOpacity="1" o:fillOpacity="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:o="urn:o"><rect o:fillOpacity="2" fill-opacity="1"/></svg>',
        ];

        yield 'Fixes an attribute with an unbound upper-case prefix without failing' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a XLINK:HREF="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a xlink:href="#a"/></svg>',
        ];

        yield 'Folds non-ASCII letters that lowercase to ASCII letters' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect stro\u{212A}e=\"red\"/></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="red"/></svg>',
        ];

        yield 'Keeps both attributes when the canonical one exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red" FILL="blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red" FILL="blue"/></svg>',
        ];

        yield 'Keeps data attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect data-strokeWidth="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect data-strokeWidth="2"/></svg>',
        ];

        yield 'Keeps the value of animated attribute names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName="FILL" to="red"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName="FILL" to="red"/></rect></svg>',
        ];

        yield 'Fixes attributes in a document with a prefixed SVG namespace' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" viewbox="0 0 1 1"><svg:rect strokeWidth="2"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><svg:rect stroke-width="2"/></svg:svg>',
        ];

        yield 'Fixes a later attribute when the first one is canonical' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red" STROKE="blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red" stroke="blue"/></svg>',
        ];

        yield 'Keeps multibyte attribute values' => [
            '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg"><text FONT-FAMILY="日本 😀">x</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text font-family="日本 😀">x</text></svg>',
        ];

        yield 'Handles an empty document' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
        ];

        yield 'Handles a root with only text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
        ];

        yield 'Handles a 60 levels deep structure' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect STROKE-WIDTH="2"/>' . str_repeat('</g>', 60) . '</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect stroke-width="2"/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'Handles a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" STROKE-WIDTH="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" stroke-width="2"/></svg>',
        ];

        yield 'Handles right-to-left, emoji, combining and astral text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text STROKE-WIDTH="2">שלום 😀 é 𝒜</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text stroke-width="2">שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Handles values padded with tabs, newlines and no-break spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect STROKE-WIDTH="2" fill="&#9; red&#10;&#xA0;"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"><rect fill=\"&#9; red&#10;\u{a0}\" stroke-width=\"2\"/></svg>",
        ];

        yield 'Handles comments between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect STROKE-WIDTH="2"/><!--4--></g><!--5--></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect stroke-width="2"/><!--4--></g><!--5--></svg>',
        ];

        yield 'Handles processing instructions' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect STROKE-WIDTH="2"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect stroke-width="2"/></svg>',
        ];

        yield 'Handles CDATA sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text STROKE-WIDTH="2"><![CDATA[a<b&c]]></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text stroke-width="2">a&lt;b&amp;c</text></svg>',
        ];

        yield 'Handles a child that resets the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect STROKE-WIDTH="2"/></x></g><rect STROKE-WIDTH="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect STROKE-WIDTH="2"/></x></g><rect stroke-width="2"/></svg>',
        ];

        yield 'Handles a prefix redeclared to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect STROKE-WIDTH="2"/></s:g><g xmlns:s="urn:other"><s:rect STROKE-WIDTH="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect stroke-width="2"/></s:g><g xmlns:s="urn:other"><s:rect STROKE-WIDTH="2"/></g></svg>',
        ];

        yield 'Leaves foreign content and attributes in other namespaces untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><metadata><x:item x:Title="a"/></metadata><rect x:Width="1" WIDTH="2"/><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" Style="color:red"/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><metadata><x:item x:Title="a"/></metadata><rect x:Width="1" width="2"/><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" Style="color:red"></div></foreignObject></svg>',
        ];

        yield 'Fixes XLink attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:HREF="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#a"/></svg>',
        ];

        yield 'Keeps the SVG 2 timeline attributes beside their SVG Tiny forms' => [
            '<svg xmlns="http://www.w3.org/2000/svg" playbackorder="forwardonly" timelinebegin="loadbegin"><g playbackorder="all"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" playbackorder="forwardonly" timelinebegin="loadbegin"><g playbackOrder="all"/></svg>',
        ];

        yield 'Handles entities and character references in values and text' => [
            '<!DOCTYPE svg [<!ENTITY e "v&#x41;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text STROKE-WIDTH="2" class="&e;">&e;&#x42;&#66;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text class="vA" stroke-width="2">vABB</text></svg>',
        ];

        yield 'Handles an attribute in no namespace next to the same local name in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c" STROKE-WIDTH="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c" stroke-width="2"/></svg>',
        ];

        yield 'Handles repeated elements where only a later one matches' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect STROKE-WIDTH="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect stroke-width="2"/></svg>',
        ];

        yield 'Handles mixed-case element names' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect STROKE-WIDTH="2"/><RECT STROKE-WIDTH="2"/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect stroke-width="2"/><RECT stroke-width="2"/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
        ];
    }

    #[Test]
    public function fixesAnElementWithManyAttributesInLinearTime(): void
    {
        $small = $this->secondsToOptimize(5_000);
        $large = $this->secondsToOptimize(20_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect ' . implode(' ', array_map(static fn (int $i): string => 'A' . $i . '="' . $i . '"', range(1, $count))) . '/></svg>');

        $start = hrtime(true);
        (new FixAttributeNames())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
