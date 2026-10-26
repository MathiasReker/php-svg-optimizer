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
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveTextAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
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
#[CoversClass(RemoveIneffectiveTextAttributes::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(IneffectiveAttributeTrait::class)]
#[CoversTrait(InheritanceTrait::class)]
#[CoversTrait(NamedColorTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class RemoveIneffectiveTextAttributesTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRemovesIneffectiveTextAttributesCases')]
    #[Test]
    public function removesIneffectiveTextAttributes(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($input));
        self::assertSame($expected, $this->optimizeContent($expected));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideRemovesIneffectiveTextAttributesCases(): iterable
    {
        yield 'Keeps a text attribute that the inline style also sets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-family="a" font-weight="bold" style="font-family:b"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-family="a" style="font-family:b"><rect/></g></svg>',
        ];

        yield 'Keeps the direction and writing mode of the root, which apply to the viewport' => [
            '<svg xmlns="http://www.w3.org/2000/svg" direction="rtl" writing-mode="vertical-rl" unicode-bidi="embed" font-size="5"><rect direction="rtl"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" direction="rtl" writing-mode="vertical-rl" unicode-bidi="embed"><rect/></svg>',
        ];

        yield 'Removes every text attribute from an element without text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" font="10px a" font-family="a" font-size="10" font-size-adjust=".5" font-stretch="condensed" font-style="italic" font-variant="small-caps" font-weight="bold" letter-spacing="1" word-spacing="1" text-anchor="middle" text-decoration="underline" text-rendering="geometricPrecision" dominant-baseline="middle" alignment-baseline="middle" baseline-shift="super" direction="rtl" unicode-bidi="embed" writing-mode="tb" glyph-orientation-horizontal="0" glyph-orientation-vertical="0" kerning="1" white-space="pre"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1"/></svg>',
        ];

        yield 'Removes text attributes from the root and groups without text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a" font-size="12"><g font-weight="bold" fill="red"><g text-anchor="end"><rect width="1" height="1" font-style="italic"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><g><rect width="1" height="1"/></g></g></svg>',
        ];

        yield 'Keeps other attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="red" style="font-size:10px" class="a" textLength="5" lengthAdjust="spacing" xml:space="preserve"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="red" style="font-size:10px" class="a" textLength="5" lengthAdjust="spacing" xml:space="preserve"/></svg>',
        ];

        yield 'Keeps text attributes on text content elements and their ancestors' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><g font-size="10"><text font-weight="bold">a<tspan font-style="italic">b</tspan><textPath href="#p" letter-spacing="1">c</textPath></text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><g font-size="10"><text font-weight="bold">a<tspan font-style="italic">b</tspan><textPath href="#p" letter-spacing="1">c</textPath></text></g></svg>',
        ];

        yield 'Removes text attributes from siblings of text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><text>a</text><g font-size="10"><rect width="1" height="1"/></g><path d="M0 0h1" font-size="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><text>a</text><g><rect width="1" height="1"/></g><path d="M0 0h1"/></svg>',
        ];

        yield 'Keeps text attributes of empty text elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text font-size="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text font-size="10"/></svg>',
        ];

        yield 'Keeps text attributes of elements containing a deeply nested tref or altGlyph' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="10"><g><g><tref href="#t"/></g></g></g><g font-size="11"><altGlyph>a</altGlyph></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="10"><g><g><tref href="#t"/></g></g></g><g font-size="11"><altGlyph>a</altGlyph></g></svg>',
        ];

        yield 'Keeps text attributes of elements containing a use element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><text id="t">a</text></defs><g font-size="10"><use xlink:href="#t"/></g><use xlink:href="#t" font-size="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><text id="t">a</text></defs><g font-size="10"><use xlink:href="#t"/></g><use xlink:href="#t" font-size="10"/></svg>',
        ];

        yield 'Keeps text attributes of elements containing foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="10"><foreignObject width="1" height="1"><div xmlns="http://www.w3.org/1999/xhtml">a</div></foreignObject></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="10"><foreignObject width="1" height="1"><div xmlns="http://www.w3.org/1999/xhtml">a</div></foreignObject></g></svg>',
        ];

        yield 'Keeps text attributes of elements inside foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="1" height="1"><svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="1" height="1"/></svg></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="1" height="1"><svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="1" height="1"/></svg></foreignObject></svg>',
        ];

        yield 'Keeps text attributes of elements containing elements of other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><g font-size="10"><c:g/></g><c:rect font-size="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><g font-size="10"><c:g/></g><c:rect font-size="10"/></svg>',
        ];

        yield 'Keeps text attributes of elements containing unknown elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="10"><unknown/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="10"><unknown/></g></svg>',
        ];

        yield 'Keeps text attributes of font elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><font><font-face font-family="a" font-weight="bold"/><glyph unicode="a" d="M0 0h1"/></font></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><font><font-face font-family="a" font-weight="bold"/><glyph unicode="a" d="M0 0h1"/></font></defs></svg>',
        ];

        yield 'Removes text attributes from elements of every textless kind' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs font-size="1"><linearGradient id="l" font-size="1"><stop font-size="1"/></linearGradient><radialGradient id="r"/><filter id="f" font-size="1"><feFlood font-size="1"/><feMerge><feMergeNode/></feMerge><feBlend/><feColorMatrix/><feComponentTransfer><feFuncA/><feFuncB/><feFuncG/><feFuncR/></feComponentTransfer><feComposite/><feConvolveMatrix/><feDiffuseLighting><feDistantLight/></feDiffuseLighting><feSpecularLighting><fePointLight/></feSpecularLighting><feSpecularLighting><feSpotLight/></feSpecularLighting><feDisplacementMap/><feDropShadow/><feGaussianBlur/><feMorphology/><feOffset/><feTile/><feTurbulence/></filter><clipPath id="c"><circle r="1" font-size="1"/></clipPath><mask id="m"><ellipse rx="1" ry="1"/></mask><marker id="k"><line x2="1"/></marker><pattern id="p"><polygon points="0 0 1 1"/><polyline points="0 0 1 1"/></pattern><symbol id="s"><image href="a.png"/></symbol></defs><a href="#x" font-size="1"><switch><rect width="1" height="1"><animate attributeName="width" to="2"/><set attributeName="height" to="2"/><animateTransform attributeName="transform" type="scale" to="2"/><animateColor attributeName="fill" to="red"/><animateMotion path="M0 0h1"><mpath href="#q"/></animateMotion></rect></switch></a><title font-size="1">t</title><desc font-size="1">d</desc><metadata font-size="1"/><view id="v"/><svg font-size="1"><path id="q" d="M0 0h1"/></svg></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="l"><stop/></linearGradient><radialGradient id="r"/><filter id="f"><feFlood/><feMerge><feMergeNode/></feMerge><feBlend/><feColorMatrix/><feComponentTransfer><feFuncA/><feFuncB/><feFuncG/><feFuncR/></feComponentTransfer><feComposite/><feConvolveMatrix/><feDiffuseLighting><feDistantLight/></feDiffuseLighting><feSpecularLighting><fePointLight/></feSpecularLighting><feSpecularLighting><feSpotLight/></feSpecularLighting><feDisplacementMap/><feDropShadow/><feGaussianBlur/><feMorphology/><feOffset/><feTile/><feTurbulence/></filter><clipPath id="c"><circle r="1"/></clipPath><mask id="m"><ellipse rx="1" ry="1"/></mask><marker id="k"><line x2="1"/></marker><pattern id="p"><polygon points="0 0 1 1"/><polyline points="0 0 1 1"/></pattern><symbol id="s"><image href="a.png"/></symbol></defs><a href="#x"><switch><rect width="1" height="1"><animate attributeName="width" to="2"/><set attributeName="height" to="2"/><animateTransform attributeName="transform" type="scale" to="2"/><animateColor attributeName="fill" to="red"/><animateMotion path="M0 0h1"><mpath href="#q"/></animateMotion></rect></switch></a><title>t</title><desc>d</desc><metadata/><view id="v"/><svg><path id="q" d="M0 0h1"/></svg></svg>',
        ];

        yield 'Keeps text attributes of metadata with foreign children' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="1"><metadata font-size="1"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"/></metadata><rect width="1" height="1" font-size="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="1"><metadata font-size="1"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"/></metadata><rect width="1" height="1"/></svg>',
        ];

        yield 'Removes text attributes from prefixed elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" font-family="a"><svg:g font-size="10"><svg:rect width="1" height="1"/></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g><svg:rect width="1" height="1"/></svg:g></svg:svg>',
        ];

        yield 'Keeps text attributes of prefixed text elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" font-family="a"><svg:text>a</svg:text></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" font-family="a"><svg:text>a</svg:text></svg:svg>',
        ];

        yield 'Removes text attributes in documents without a namespace' => [
            '<svg font-family="a"><rect width="1" height="1"/></svg>',
            '<svg><rect width="1" height="1"/></svg>',
        ];

        yield 'Removes padded, uppercase and inherited values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" font-family=" A " font-weight="BOLD" font-size="inherit"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        ];

        yield 'Keeps namespaced attributes with the same local name' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><rect width="1" height="1" c:font-size="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><rect width="1" height="1" c:font-size="1"/></svg>',
        ];

        yield 'Keeps animated text attributes and removes the others' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" font-size="10" font-family="a"><set attributeName=" font-size " to="11"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" font-size="10"><set attributeName=" font-size " to="11"/></rect></svg>',
        ];

        yield 'Keeps everything when a length is relative to the font' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="2em" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="2em" height="1"/></svg>',
        ];

        yield 'Keeps everything when a length is relative to the font in a style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="1" height="1" style="stroke-width:.5EX"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="1" height="1" style="stroke-width:.5EX"/></svg>',
        ];

        yield 'Keeps everything when a length is relative to the root font' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="1rem" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="1rem" height="1"/></svg>',
        ];

        yield 'Keeps everything when a length is in ch units' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><rect width="3ch" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><rect width="3ch" height="1"/></svg>',
        ];

        yield 'Keeps everything when a length is in cap units' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><rect width="3cap" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><rect width="3cap" height="1"/></svg>',
        ];

        yield 'Keeps everything when a length is in ic units' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><rect width="3ic" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><rect width="3ic" height="1"/></svg>',
        ];

        yield 'Keeps everything when a length is in lh units' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><rect width="3lh" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" font-family="a"><rect width="3lh" height="1"/></svg>',
        ];

        yield 'Removes text attributes when values only resemble font units' => [
            '<svg xmlns="http://www.w3.org/2000/svg" font-size="10"><rect width="1e2" height="1" class="em a1emx"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1e2" height="1" class="em a1emx"/></svg>',
        ];

        yield 'Keeps everything when a style element is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect width="1" height="1" font-size="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect width="1" height="1" font-size="10"/></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" font-size="10"/></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" font-size="10"/></svg>',
        ];

        yield 'Removes text attributes when other processing instructions are present' => [
            '<?other data?><svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" font-size="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
        ];

        yield 'Keeps documents without text attributes untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    private function optimizeContent(string $content): string
    {
        $pipeline = new Pipeline([new RemoveIneffectiveTextAttributes()]);

        return $pipeline->run($content);
    }
}
