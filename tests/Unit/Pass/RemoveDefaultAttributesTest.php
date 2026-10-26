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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
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
#[CoversClass(RemoveDefaultAttributes::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(InheritanceTrait::class)]
#[CoversTrait(InheritedValueTrait::class)]
#[CoversTrait(NamedColorTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class RemoveDefaultAttributesTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRemovesDefaultAttributesCases')]
    #[Test]
    public function removesDefaultAttributes(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveDefaultAttributes()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);

        $repeated = new Pipeline([new RemoveDefaultAttributes()]);
        self::assertSame($actual, $repeated->run($actual));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideRemovesDefaultAttributesCases(): iterable
    {
        yield 'Leaves documents with a style element untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><style>rect{stroke:red}</style><rect stroke="none" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><style>rect{stroke:red}</style><rect stroke="none" width="5" height="5"/></svg>',
        ];

        yield 'Keeps a default that the inline style of the element overrides' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><rect stroke="none" style="stroke:red" width="5" height="5"/><rect stroke="none" style="all:initial" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><rect stroke="none" style="stroke:red" width="5" height="5"/><rect stroke="none" style="all:initial" width="5" height="5"/></svg>',
        ];

        yield 'Keeps a default below an ancestor whose inline style sets the property' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g style="stroke:red"><rect stroke="none" width="5" height="5"/></g><g style="all:initial"><rect stroke="none" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g style="stroke:red"><rect stroke="none" width="5" height="5"/></g><g style="all:initial"><rect stroke="none" width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps a default on or below an element that can be referenced by id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g id="g"><rect stroke="none" width="5" height="5"/></g><rect id="r" stroke="none" width="5" height="5"/><g><rect stroke="none" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g id="g"><rect stroke="none" width="5" height="5"/></g><rect id="r" stroke="none" width="5" height="5"/><g><rect width="5" height="5"/></g></svg>',
        ];
        yield 'Keeps a default attribute that the inline style overrides' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="none" style="stroke:inherit"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="none" style="stroke:inherit"/></svg>',
        ];

        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps a default with a mixed-case attribute name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect Stroke="none" STROKE-WIDTH="1" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect Stroke="none" STROKE-WIDTH="1" width="5" height="5"/></svg>',
        ];

        yield 'keeps a default padded with a non-breaking space or a tab' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="none&#xA0;" stroke-width="&#9;1" width="5" height="5"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect stroke=\"none\u{A0}\" stroke-width=\"&#9;1\" width=\"5\" height=\"5\"/></svg>",
        ];

        yield 'removes defaults inside a group reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect stroke="none" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect stroke="none" width="5" height="5"/></g></svg>',
        ];

        yield 'removes defaults deep inside groups without a value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><g><rect stroke-linejoin="miter" width="5" height="5"/></g></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><g><rect stroke-linejoin="miter" width="5" height="5"/></g></g></g></g></g></svg>',
        ];

        yield 'keeps a default when the parent sets the property after a comment in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="/* c */stroke:red"><rect stroke="none" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="/* c */stroke:red"><rect stroke="none" width="5" height="5"/></g></svg>',
        ];

        yield 'keeps a default when the element resets all properties in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="none" style="all:inherit" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="none" style="all:inherit" width="5" height="5"/></svg>',
        ];

        yield 'keeps a default when the parent resets all properties in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="all:inherit"><rect stroke="none" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="all:inherit"><rect stroke="none" width="5" height="5"/></g></svg>',
        ];

        yield 'keeps a default when the parent sets a vendor prefixed property in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="-webkit-stroke-width:3"><rect stroke-width="1" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="-webkit-stroke-width:3"><rect stroke-width="1" width="5" height="5"/></g></svg>',
        ];

        yield 'removes a default when the parent only sets a custom property with the same name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="--stroke:red"><rect stroke="none" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="--stroke:red"><rect stroke="none" width="5" height="5"/></g></svg>',
        ];

        yield 'keeps a default inside an element referenced through xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g xml:id="a"><rect stroke="none" width="5" height="5"/></g><use xlink:href="#a" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g xml:id="a"><rect stroke="none" width="5" height="5"/></g><use xlink:href="#a" stroke="red"/></svg>',
        ];

        yield 'keeps defaults when an external style sheet is linked' => [
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><g><rect stroke="none" width="5" height="5"/></g></svg>',
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><g><rect stroke="none" width="5" height="5"/></g></svg>',
        ];

        yield 'keeps a default with an uppercase or padded value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="NONE" stroke-width=" 1" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="NONE" stroke-width=" 1" width="5" height="5"/></svg>',
        ];

        yield 'removes defaults on prefixed svg elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect stroke="none" stroke-width="1" width="5" height="5"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect stroke="none" stroke-width="1" width="5" height="5"/></svg:svg>',
        ];

        yield 'keeps a namespaced attribute with a default value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:stroke="none" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:stroke="none" width="5" height="5"/></svg>',
        ];

        yield 'keeps a default deep inside a parent that sets another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-linecap="round"><g><g><path d="M0 0h5" stroke-linecap="butt"/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-linecap="round"><g><g><path d="M0 0h5" stroke-linecap="butt"/></g></g></g></svg>',
        ];

        yield 'removes the default of a later element when an earlier one is kept' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red"><rect stroke="none" width="5" height="5"/></g><rect stroke="none" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red"><rect stroke="none" width="5" height="5"/></g><rect stroke="none" width="5" height="5"/></svg>',
        ];

        yield 'keeps the default of an element inside a symbol with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><symbol id="s"><g><rect stroke="none" width="5" height="5"/></g></symbol><use xlink:href="#s" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><symbol id="s"><g><rect stroke="none" width="5" height="5"/></g></symbol><use xlink:href="#s" stroke="red"/></svg>',
        ];

        yield 'keeps a default whose attribute is animated with a padded name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke-width="1" width="5" height="5"><animate attributeName=" stroke-width " to="3" dur="1s"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke-width="1" width="5" height="5"><animate attributeName=" stroke-width " to="3" dur="1s"/></rect></svg>',
        ];

        yield 'Keeps defaults that no element of the document sets above, as the page or a use element may set them' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30" stroke="none"/>
                    <circle cx="50" cy="50" r="20" stroke="none"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" stroke="none"/><circle cx="50" cy="50" r="20" stroke="none"/></svg>
                XML,
        ];

        yield 'Removes defaults below a root element that sets the same value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none" stroke-width="1"><rect stroke="none" stroke-width="1" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none" stroke-width="1"><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps a default below a root element that sets another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="red"><rect stroke="none" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="red"><rect stroke="none" width="5" height="5"/></svg>',
        ];

        yield 'Removes a default below an element that inherits the same value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g stroke="inherit"><rect stroke="none" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g stroke="inherit"><rect width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps a default below an element whose value depends on a custom property' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g stroke="var(--s)"><rect stroke="none" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g stroke="var(--s)"><rect stroke="none" width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps every animated default attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="none" stroke-width="1" d="M0 0h1"><set attributeName="stroke" to="red"/><set attributeName=" stroke-width " to="2"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="none" stroke-width="1" d="M0 0h1"><set attributeName="stroke" to="red"/><set attributeName=" stroke-width " to="2"/></path></svg>',
        ];

        yield 'Keeps Non-Default Stroke Attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30" stroke="black"/>
                    <circle cx="50" cy="50" r="20" stroke="green"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" stroke="black"/><circle cx="50" cy="50" r="20" stroke="green"/></svg>
                XML,
        ];

        yield 'Keeps the default of a group that no element of the document sets above' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g stroke="none">
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g stroke="none"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Non-Standard Default Attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30"/>
                    <circle cx="50" cy="50" r="20"/>
                    <customElement customAttr="none"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/><customElement customAttr="none"/></svg>
                XML,
        ];

        yield 'Keeps Attributes with Different Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30" stroke="red"/>
                    <circle cx="50" cy="50" r="20" stroke="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" stroke="red"/><circle cx="50" cy="50" r="20" stroke="blue"/></svg>
                XML,
        ];

        yield 'Removes defaults below a group that sets the same value, and keeps that group value, which the page may override' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g stroke="none">
                        <rect x="10" y="10" width="30" height="30" stroke="none"/>
                        <circle cx="50" cy="50" r="20" stroke="none"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g stroke="none"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Empty SVG' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'SVG with no default attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="black"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="black"/></svg>',
        ];

        yield 'SVG with all default attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="4"/></svg>',
        ];

        yield 'Keeps default stroke when an ancestor sets a different stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red"><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps default stroke-width when a distant ancestor sets a different width' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="3"><g><path stroke-width="1" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="3"><g><path stroke-width="1" d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default when the nearest ancestor declaration is also default' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="red"><g stroke="none"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="red"><g stroke="none"><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps default stroke when an ancestor style sets the property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke:red"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke:red"><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default stroke when an ancestor style only sets other properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:red;stroke-width:2"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:red;stroke-width:2"><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default stroke when an ancestor style only sets a custom property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="--stroke:red"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="--stroke:red"><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default stroke below a root element with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" id="Layer_1"><path stroke="none" d="M0 0h1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" id="Layer_1"><path stroke="none" d="M0 0h1"/></svg>',
        ];

        yield 'Keeps default attributes when a style element is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{stroke:red}</style><g><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{stroke:red}</style><g><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps default stroke on an element that may be reused by use' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path id="p" stroke="none" d="M0 0h1"/><use xlink:href="#p" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path id="p" stroke="none" d="M0 0h1"/><use xlink:href="#p" stroke="red"/></svg>',
        ];

        yield 'Keeps default stroke inside an ancestor that may be reused by use' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="g"><path stroke="none" d="M0 0h1"/></g><use xlink:href="#g" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="g"><path stroke="none" d="M0 0h1"/></g><use xlink:href="#g" stroke="red"/></svg>',
        ];

        yield 'Keeps default stroke when an ancestor stroke is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="stroke" to="red"/><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="stroke" to="red"/><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default stroke-linecap while keeping stroke animated elsewhere' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="stroke" to="red"/><path stroke-linecap="butt" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="stroke" to="red"/><path stroke-linecap="butt" d="M0 0h1"/></g></svg>',
        ];
    }
}
