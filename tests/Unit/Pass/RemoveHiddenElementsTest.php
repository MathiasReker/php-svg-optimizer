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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritedValueTrait;
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
#[CoversClass(RemoveHiddenElements::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdReferenceTrait::class)]
#[CoversTrait(InheritanceTrait::class)]
#[CoversTrait(InheritedValueTrait::class)]
#[CoversTrait(NamedColorTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveHiddenElementsTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($input));
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function applyIsIdempotent(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($expected));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Keeps an unpainted shape below an element with an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g xml:id="g"><rect width="1" height="1" fill="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g xml:id="g"><rect width="1" height="1" fill="none"/></g></svg>',
        ];

        yield 'Keeps a hidden group whose last child has an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><rect width="1" height="1"/><rect id="r" width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><rect width="1" height="1"/><rect id="r" width="1" height="1"/></g></svg>',
        ];
        yield 'Keeps invisible shapes inside a group whose transform box they help size, set in a style' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g style="transform-box:fill-box;transform:rotate(45deg)"><rect width="5" height="5" fill="red"/><rect x="20" width="5" height="5" fill="none"/><rect x="40" width="5" height="5" opacity="0"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g style="transform-box:fill-box;transform:rotate(45deg)"><rect width="5" height="5" fill="red"/><rect x="20" width="5" height="5" fill="none"/><rect x="40" width="5" height="5" opacity="0"/></g></svg>',
        ];

        yield 'Keeps invisible shapes inside a group with a transform box or origin attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g transform-box="fill-box"><rect width="5" height="5" fill="none"/></g><g transform-origin="50% 50%"><rect width="5" height="5" opacity="0"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g transform-box="fill-box"><rect width="5" height="5" fill="none"/></g><g transform-origin="50% 50%"><rect width="5" height="5" opacity="0"/></g></svg>',
        ];

        yield 'Keeps a transparent element with a descendant title, which shows a tooltip on hover' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0"><rect width="100" height="100"><title>tip</title></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0"><rect width="100" height="100"><title>tip</title></rect></g></svg>',
        ];

        yield 'Keeps a transparent element with a descendant that sets a cursor or pointer events' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0"><rect width="9" height="9" cursor="pointer"/></g><g opacity="0"><rect width="9" height="9" pointer-events="all"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0"><rect width="9" height="9" cursor="pointer"/></g><g opacity="0"><rect width="9" height="9" pointer-events="all"/></g></svg>',
        ];

        yield 'Removes a transparent element next to a group with a title' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0"><rect width="9" height="9"/></g><g><title>t</title></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title>t</title></g></svg>',
        ];
        yield 'Keeps hidden elements in a document with a script, which may show or query them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><rect display="none"/><g stroke="none"><rect fill="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><rect display="none"/><g stroke="none"><rect fill="none"/></g></svg>',
        ];

        yield 'Keeps hidden elements in a document with an event handler on an unrelated element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="none"/><circle r="5" ONCLICK="a()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="none"/><circle r="5" ONCLICK="a()"/></svg>',
        ];
        yield 'Keeps a shape without fill whose stroke the page or a use element may set' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="none"/></svg>',
        ];

        yield 'Removes a shape without fill below a root element without stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><rect width="1" height="1" fill="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"/>',
        ];

        yield 'Removes a shape without stroke below a root element without fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none"><g stroke="inherit"><rect width="1" height="1" stroke="NONE"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none"><g stroke="inherit"/></svg>',
        ];

        yield 'Keeps hidden elements inside a group with a filter in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="filter:url(#f)"><rect width="5" height="5"/><rect opacity="0" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="filter:url(#f)"><rect width="5" height="5"/><rect opacity="0" width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps hidden elements inside a group with a mask in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="mask:url(#m)"><rect width="5" height="5"/><rect opacity="0" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="mask:url(#m)"><rect width="5" height="5"/><rect opacity="0" width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps hidden elements inside a group with a clip path in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="clip-path:url(#c)"><rect width="5" height="5"/><rect opacity="0" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="clip-path:url(#c)"><rect width="5" height="5"/><rect opacity="0" width="5" height="5"/></g></svg>',
        ];

        yield 'Removes an element with display none' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" display="none"/><rect width="2" height="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="2" height="2"/></svg>',
        ];

        yield 'Removes display none with an uppercase and padded value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" display=" NONE "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes a hidden group with its content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><g><g><path d="M0 0h1"/><text>a</text></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes hidden elements of every rendered kind' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a display="none"/><circle display="none"/><ellipse display="none"/><image display="none"/><line display="none"/><polygon display="none"/><polyline display="none"/><svg display="none"/><text display="none">a</text><use display="none" href="#x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes a later hidden element after keeping an earlier one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" display="none"/><rect display="none"/><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" display="none"/><use href="#a"/></svg>',
        ];

        yield 'Removes hidden elements in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect display="none"/><svg:rect width="1"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="1"/></svg:svg>',
        ];

        yield 'Removes hidden elements in a document without a namespace' => [
            '<svg><rect display="none"/></svg>',
            '<svg/>',
        ];

        yield 'Keeps the root element with display none' => [
            '<svg xmlns="http://www.w3.org/2000/svg" display="none"><rect width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" display="none"><rect width="1"/></svg>',
        ];

        yield 'Keeps other display values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="inline"/><rect display="inherit"/><rect display="nonee"/><rect display=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="inline"/><rect display="inherit"/><rect display="nonee"/><rect display=""/></svg>',
        ];

        yield 'Keeps elements that are not rendered elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject display="none"/><switch display="none"/><linearGradient display="none"/><tspan display="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject display="none"/><switch display="none"/><linearGradient display="none"/><tspan display="none"/></svg>',
        ];

        yield 'Keeps hidden elements inside definition containers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect display="none"/></defs><clipPath><rect display="none"/></clipPath><mask><rect display="none"/></mask><pattern><rect display="none"/></pattern><marker><rect display="none"/></marker><symbol><rect display="none"/></symbol></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect display="none"/></defs><clipPath><rect display="none"/></clipPath><mask><rect display="none"/></mask><pattern><rect display="none"/></pattern><marker><rect display="none"/></marker><symbol><rect display="none"/></symbol></svg>',
        ];

        yield 'Keeps hidden elements deep inside a definition container' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath><g><g><rect display="none"/></g></g></clipPath></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath><g><g><rect display="none"/></g></g></clipPath></svg>',
        ];

        yield 'Keeps hidden elements inside other containers that are not rendered directly' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><tspan><rect display="none"/></tspan><textPath><rect display="none"/></textPath><linearGradient><rect display="none"/></linearGradient><radialGradient><rect display="none"/></radialGradient><filter><rect display="none"/></filter><font><rect display="none"/></font><glyph><rect display="none"/></glyph><missing-glyph><rect display="none"/></missing-glyph></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><tspan><rect display="none"/></tspan><textPath><rect display="none"/></textPath><linearGradient><rect display="none"/></linearGradient><radialGradient><rect display="none"/></radialGradient><filter><rect display="none"/></filter><font><rect display="none"/></font><glyph><rect display="none"/></glyph><missing-glyph><rect display="none"/></missing-glyph></svg>',
        ];

        yield 'Keeps hidden elements inside a switch' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g><rect display="none"/></g><rect display="none"/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g><rect display="none"/></g><rect display="none"/></switch></svg>',
        ];

        yield 'Keeps hidden elements inside text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<a display="none">b</a>c</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<a display="none">b</a>c</text></svg>',
        ];

        yield 'Keeps hidden elements in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><rect display="none"/></svg></foreignObject><c:rect xmlns:c="http://example.com/c" display="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><rect display="none"/></svg></foreignObject><c:rect xmlns:c="http://example.com/c" display="none"/></svg>',
        ];

        yield 'Keeps hidden elements containing foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><foreignObject/></g><g display="none"><c:x xmlns:c="http://example.com/c"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><foreignObject/></g><g display="none"><c:x xmlns:c="http://example.com/c"/></g></svg>',
        ];

        yield 'Keeps hidden elements with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" display="none"/><rect xml:id="b" display="none"/><g display="none"><rect id="c"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" display="none"/><rect xml:id="b" display="none"/><g display="none"><rect id="c"/></g></svg>',
        ];

        yield 'Keeps hidden elements containing scripts' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><script>a()</script></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><script>a()</script></g></svg>',
        ];

        yield 'Keeps hidden elements with event attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="none" onload="a()"/><g display="none"><rect OnClick="a()"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="none" onload="a()"/><g display="none"><rect OnClick="a()"/></g></svg>',
        ];

        yield 'Keeps hidden elements containing animations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="none"><set attributeName="display" to="inline"/></rect><g display="none"><animate href="#x"/></g><g display="none"><animateTransform/></g><g display="none"><animateMotion/></g><g display="none"><animateColor/></g><g display="none"><discard/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="none"><set attributeName="display" to="inline"/></rect><g display="none"><animate href="#x"/></g><g display="none"><animateTransform/></g><g display="none"><animateMotion/></g><g display="none"><animateColor/></g><g display="none"><discard/></g></svg>',
        ];

        yield 'Keeps hidden elements whose ancestor is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="fill" to="red"/><g><rect display="none"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="fill" to="red"/><g><rect display="none"/></g></g></svg>',
        ];

        yield 'Removes hidden elements beside an animated sibling' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect><set attributeName="fill" to="red"/></rect><rect display="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect><set attributeName="fill" to="red"/></rect></g></svg>',
        ];

        yield 'Keeps hidden elements whose ancestor is referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><g><rect display="none"/></g></g><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><g><rect display="none"/></g></g><use href="#a"/></svg>',
        ];

        yield 'Removes hidden elements whose ancestor has an unreferenced id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><rect display="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"/></svg>',
        ];

        yield 'Keeps hidden elements whose inline style may change rendering' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="none" style="DISPLAY: inline"/><g style="fill:red"><rect display="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect display="none" style="DISPLAY: inline"/><g style="fill:red"><rect display="none"/></g></svg>',
        ];

        yield 'Removes hidden elements whose inline style sets other properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="font-size:2px"><rect display="none" style="stroke-width:2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="font-size:2px"/></svg>',
        ];

        yield 'Keeps everything when a style element is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect display="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect display="none"/></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><rect display="none"/></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><rect display="none"/></svg>',
        ];

        yield 'Removes elements with opacity zero' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" opacity="0"/><g opacity=" 0.0 "><path d="M0 0h1"/></g><circle r="1" opacity="-0"/><image opacity="0e5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps elements with other opacity values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect opacity="0.01"/><rect opacity="0%"/><rect opacity="inherit"/><rect opacity=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect opacity="0.01"/><rect opacity="0%"/><rect opacity="inherit"/><rect opacity=""/></svg>',
        ];

        yield 'Keeps elements whose opacity has a trailing decimal point, which is invalid and ignored' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" opacity="0."/><rect width="5" height="5" opacity="0.e1"/><rect width="5" height="5" opacity="0.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" opacity="0."/><rect width="5" height="5" opacity="0.e1"/></svg>',
        ];

        yield 'Keeps transparent elements that may be interactive' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#x"><rect opacity="0"/></a><g cursor="pointer"><rect opacity="0"/></g><rect opacity="0" pointer-events="all"/><g opacity="0"><a href="#x"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#x"><rect opacity="0"/></a><g cursor="pointer"><rect opacity="0"/></g><rect opacity="0" pointer-events="all"/><g opacity="0"><a href="#x"/></g></svg>',
        ];

        yield 'Keeps transparent elements that affect a bounding box' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g filter="url(#f)"><rect opacity="0"/></g><g mask="url(#m)"><rect opacity="0"/></g><g clip-path="url(#c)"><rect opacity="0"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g filter="url(#f)"><rect opacity="0"/></g><g mask="url(#m)"><rect opacity="0"/></g><g clip-path="url(#c)"><rect opacity="0"/></g></svg>',
        ];

        yield 'Keeps transparent elements that are accessible' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect opacity="0" role="img"/><rect opacity="0" tabindex="0"/><g opacity="0"><rect aria-label="a"/></g><text opacity="0">a</text><g opacity="0"><desc>a</desc></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect opacity="0" role="img"/><rect opacity="0" tabindex="0"/><g opacity="0"><rect aria-label="a"/></g><text opacity="0">a</text><g opacity="0"><desc>a</desc></g></svg>',
        ];

        yield 'Keeps transparent elements containing text content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0"><tspan>a</tspan></g><g opacity="0"><textPath>b</textPath></g><g opacity="0"><g><text>c</text></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0"><tspan>a</tspan></g><g opacity="0"><textPath>b</textPath></g><g opacity="0"><g><text>c</text></g></g></svg>',
        ];

        yield 'Keeps transparent elements that show a tooltip' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title>a</title><g><rect opacity="0"/></g></g><rect opacity="0"><title>b</title></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title>a</title><g><rect opacity="0"/></g></g><rect opacity="0"><title>b</title></rect></svg>',
        ];

        yield 'Keeps transparent elements in documents with scripts' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><rect opacity="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><rect opacity="0"/></svg>',
        ];

        yield 'Keeps transparent elements in documents with event attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onclick="a()"><rect opacity="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onclick="a()"><rect opacity="0"/></svg>',
        ];

        yield 'Removes display none despite interactivity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#x"><rect display="none" filter="url(#f)" role="img"><title>a</title></rect></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="#x"/></svg>',
        ];

        yield 'Removes shapes without fill and stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" fill="none"/><path d="M0 0h1" fill=" None " stroke="NONE"/><circle r="1" fill="none"/><ellipse fill="none"/><line fill="none"/><polyline fill="none"/><polygon fill="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" fill="none"/><circle r="1" fill="none"/><ellipse fill="none"/><line fill="none"/><polyline fill="none"/><polygon fill="none"/></svg>',
        ];

        yield 'Removes shapes that inherit no fill and no stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none" stroke="none"><g><rect width="1"/><rect width="1" fill="inherit" stroke=" Inherit "/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none" stroke="none"><g/></g></svg>',
        ];

        yield 'Keeps shapes with a fill or a stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/><rect fill="red"/><rect fill="none" stroke="red"/><g fill="none"><rect fill="currentColor"/></g><g stroke="red"><rect fill="none"/></g><rect fill="url(#a) none"/><rect fill=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/><rect fill="red"/><rect fill="none" stroke="red"/><g fill="none"><rect fill="currentColor"/></g><g stroke="red"><rect fill="none"/></g><rect fill="url(#a) none"/><rect fill=""/></svg>',
        ];

        yield 'Keeps shapes whose paint inherits a visible value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect fill="inherit" stroke="none"/></g><g stroke="red"><rect fill="none" stroke="inherit"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect fill="inherit" stroke="none"/></g><g stroke="red"><rect fill="none" stroke="inherit"/></g></svg>',
        ];

        yield 'Keeps groups and text without fill and stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none"/><text fill="none">a</text><image fill="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none"/><text fill="none">a</text><image fill="none"/></svg>',
        ];

        yield 'Keeps unpainted shapes with markers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="none" marker-start="url(#m)"/><path fill="none" marker-mid="url(#m)"/><path fill="none" marker-end="url(#m)"/><g marker="url(#m)"><path fill="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="none" marker-start="url(#m)"/><path fill="none" marker-mid="url(#m)"/><path fill="none" marker-end="url(#m)"/><g marker="url(#m)"><path fill="none"/></g></svg>',
        ];

        yield 'Keeps unpainted shapes inside an element with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><path fill="none"/></g><g xml:id="b"><path fill="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><path fill="none"/></g><g xml:id="b"><path fill="none"/></g></svg>',
        ];

        yield 'Keeps unpainted shapes that may be interactive or affect a bounding box' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="none" pointer-events="all"/><a href="#x"><path fill="none"/></a><g filter="url(#f)"><path fill="none"/></g><path fill="none" aria-label="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="none" pointer-events="all"/><a href="#x"><path fill="none"/></a><g filter="url(#f)"><path fill="none"/></g><path fill="none" aria-label="a"/></svg>',
        ];

        yield 'Removes unpainted shapes in documents with scripts or tooltips' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><path fill="none"/><g><title>a</title><path fill="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>a()</script><path fill="none"/><g><title>a</title><path fill="none"/></g></svg>',
        ];

        yield 'Keeps unpainted shapes whose inline style may paint them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="none" style="stroke:red"/><g style="marker-end:url(#m)"><path fill="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="none" style="stroke:red"/><g style="marker-end:url(#m)"><path fill="none"/></g></svg>',
        ];

        yield 'Keeps unpainted shapes inside a clip path' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"><rect width="1" fill="none"/></clipPath><rect width="2" clip-path="url(#c)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"><rect width="1" fill="none"/></clipPath><rect width="2" clip-path="url(#c)"/></svg>',
        ];

        yield 'Keeps unpainted shapes that are referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="p" d="M0 0h9" fill="none"/><text><textPath href="#p">a</textPath></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="p" d="M0 0h9" fill="none"/><text><textPath href="#p">a</textPath></text></svg>',
        ];

        yield 'Removes hidden elements with entities and deep nesting' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text display="none">a &amp; b &#60;</text><g><g><g><g><g><g><rect display="none"/><rect fill="none"/><rect opacity="0"/><rect width="1"/></g></g></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><g><g><rect fill="none"/><rect width="1"/></g></g></g></g></g></g></svg>',
        ];

        yield 'Removes hidden layers from an Inkscape document' => [
            '<?xml version="1.0"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" viewBox="0 0 10 10"><g inkscape:groupmode="layer" inkscape:label="Hidden" style="display:none"><path d="M0 0h1"/></g><g inkscape:groupmode="layer" display="none" inkscape:label="Off"><path d="M0 0h1"/></g><g inkscape:label="On"><path d="M0 0h1" fill="#000"/><path d="M0 0h1" style="fill:none;stroke:none"/><path d="M0 0h1" fill="none" stroke="none"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" viewBox="0 0 10 10"><g inkscape:groupmode="layer" inkscape:label="Hidden" style="display:none"><path d="M0 0h1"/></g><g inkscape:label="On"><path d="M0 0h1" fill="#000"/><path d="M0 0h1" style="fill:none;stroke:none"/></g></svg>',
        ];

        yield 'Keeps everything when a CDATA style element is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[rect{display:inline}]]></style><rect display="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{display:inline}</style><rect display="none"/></svg>',
        ];

        yield 'Keeps documents without hidden elements untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>',
        ];

        yield 'Keeps empty documents untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    #[DataProvider('provideHandlesManyNamespaceDeclarationsInLinearTimeCases')]
    #[Test]
    public function handlesManyNamespaceDeclarationsInLinearTime(string $element): void
    {
        $small = $this->secondsToOptimize(200, $element);
        $large = $this->secondsToOptimize(800, $element);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideHandlesManyNamespaceDeclarationsInLinearTimeCases(): iterable
    {
        yield 'transparent elements' => ['<rect width="1" height="1" opacity="0"/>'];

        yield 'unpainted elements' => ['<rect width="1" height="1" fill="none" stroke="none"/>'];
    }

    private function secondsToOptimize(int $count, string $element): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '>' . str_repeat($element, $count) . '</svg>');

        $start = hrtime(true);
        (new RemoveHiddenElements())->apply($domDocument);

        self::assertNull($domDocument->documentElement?->firstElementChild);

        return (hrtime(true) - $start) / 1e9;
    }

    /**
     * @throws InvalidSvgException
     */
    private function optimizeContent(string $content): string
    {
        $pipeline = new Pipeline([new RemoveHiddenElements()]);

        return $pipeline->run($content);
    }

    #[Test]
    public function handlesADeepTreeInLinearTime(): void
    {
        $small = $this->secondsForDeepTree(60);
        $large = $this->secondsForDeepTree(240);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsForDeepTree(int $depth): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" stroke="none">' . str_repeat('<g x="1">' . str_repeat('<rect width="1" height="1" fill="none"/>', 20), $depth) . str_repeat('</g>', $depth) . '</svg>');

        $start = hrtime(true);
        (new RemoveHiddenElements())->apply($domDocument);

        self::assertSame(0, $domDocument->getElementsByTagName('rect')->length);

        return (hrtime(true) - $start) / 1e9;
    }
}
