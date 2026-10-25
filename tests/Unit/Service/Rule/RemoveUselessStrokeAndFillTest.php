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
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\InheritanceBoundaryTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PresentationAttributeCleanupTrait;
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
#[CoversClass(RemoveUselessStrokeAndFill::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(InheritanceBoundaryTrait::class)]
#[CoversTrait(PresentationAttributeCleanupTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
final class RemoveUselessStrokeAndFillTest extends TestCase
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
        self::assertSame($expected, $this->optimizeContent($expected));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Leaves documents with a style element untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><style>path{stroke:red}</style><path d="M0 0h1" stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><style>path{stroke:red}</style><path d="M0 0h1" stroke-width="2"/></svg>',
        ];

        yield 'Leaves documents with markers or explicit hit testing untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" stroke-width="2" marker-end="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" stroke-width="2" marker-end="url(#m)"/></svg>',
        ];

        yield 'Leaves documents with a marker in an inline style untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" stroke-width="2" style="marker-start:url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" stroke-width="2" style="marker-start:url(#m)"/></svg>',
        ];

        yield 'Removes stroke details when an attribute value mentions markers' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" stroke-width="2" class="marker"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" class="marker"/></svg>',
        ];

        yield 'Keeps the stroke details of a use element and of the ancestors of one' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><defs><path id="p" d="M0 0h1" stroke="red"/></defs><use href="#p" stroke-width="2"/><g stroke-width="2"><g><use href="#p"/></g></g><path d="M0 0h1" stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><defs><path id="p" d="M0 0h1" stroke="red"/></defs><use href="#p" stroke-width="2"/><g stroke-width="2"><g><use href="#p"/></g></g><path d="M0 0h1"/></svg>',
        ];

        yield 'Keeps every stroke detail when one of them is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" stroke-width="2"><animate attributeName="stroke-opacity" to="0" dur="1s"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" stroke-width="2"><animate attributeName="stroke-opacity" to="0" dur="1s"/></path></svg>',
        ];

        yield 'Keeps stroke details below an element with an id, which a use element may give another stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g id="g"><path d="M0 0h1" stroke-width="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g id="g"><path d="M0 0h1" stroke-width="2"/></g></svg>',
        ];
        yield 'Keeps stroke details that the page or a use element may enable, as no element of the document sets the stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" stroke-linejoin="bevel"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" stroke-linejoin="bevel"/></svg>',
        ];

        yield 'Removes stroke details below a root element without stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1" stroke-width="2" stroke-linejoin="bevel"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><path d="M0 0h1"/></svg>',
        ];

        yield 'Removes fill details below a root element without fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none"><path d="M0 0h1" fill-rule="evenodd"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none"><path d="M0 0h1"/></svg>',
        ];

        yield 'Keeps fill details that the page may enable, as no element of the document sets the fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" fill-rule="evenodd"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" fill-rule="evenodd"/></svg>',
        ];

        yield 'Keeps stroke details below an element whose stroke depends on a custom property' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g stroke="var(--s)"><path d="M0 0h1" stroke-width="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="none"><g stroke="var(--s)"><path d="M0 0h1" stroke-width="2"/></g></svg>',
        ];

        yield 'Removes stroke details from an element without stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="5" stroke-dasharray="1 2" stroke-dashoffset="1" stroke-opacity=".5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="none"/></svg>',
        ];

        yield 'Keeps a stroke detail that the inline style also sets, so the style is not turned into an attribute later' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="0" stroke-miterlimit="10" stroke-linecap="round" style="stroke-miterlimit:10.4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="0" stroke-miterlimit="10" style="stroke-miterlimit:10.4"/></svg>',
        ];

        yield 'Removes stroke details from an element that inherits no stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none"><g><path d="M0 0h1" stroke-width="2" stroke-linecap="round"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none"><g><path d="M0 0h1"/></g></g></svg>',
        ];

        yield 'Removes stroke details from an element that inherits the initial stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="3"><path d="M0 0h1" stroke-width="2" stroke-linejoin="bevel"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="3"><path d="M0 0h1" stroke-width="2" stroke-linejoin="bevel"/></svg>',
        ];

        yield 'Removes stroke details below a padded none' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke=" none " stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke=" none "/></svg>',
        ];

        yield 'Keeps stroke details below an uppercase none' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="NONE" stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="NONE" stroke-width="2"/></svg>',
        ];

        yield 'Keeps stroke details of a stroked element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width="2" stroke-linecap="round"/><path d="M0 0h1" stroke="currentColor" stroke-width="2"/><path d="M0 0h1" stroke="red" stroke-linecap="round"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width="2" stroke-linecap="round"/><path d="M0 0h1" stroke="currentColor" stroke-width="2"/><path d="M0 0h1" stroke="red" stroke-linecap="round"/></svg>',
        ];

        yield 'Keeps stroke details of an element that inherits a stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="red"><g><path d="M0 0h1" stroke-width="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="red"><g><path d="M0 0h1" stroke-width="2"/></g></svg>',
        ];

        yield 'Keeps stroke details of a group whose descendant sets a stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="2"><path d="M0 0h1"/><path d="M0 0h1" stroke="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="2"><path d="M0 0h1"/><path d="M0 0h1" stroke="red"/></g></svg>',
        ];

        yield 'Keeps stroke details of a group whose deep descendant sets a stroke in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="2"><g><g><path d="M0 0h1" style="STROKE: red"/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="2"><g><g><path d="M0 0h1" style="STROKE: red"/></g></g></g></svg>',
        ];

        yield 'Keeps stroke details of a group whose descendant resets all properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="2"><path d="M0 0h1" style="all:initial"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="2"><path d="M0 0h1" style="all:initial"/></g></svg>',
        ];

        yield 'Removes stroke details when a descendant only sets a property ending in stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none" stroke-width="2"><text style="-webkit-text-stroke:red">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="none"><text style="-webkit-text-stroke:red">a</text></g></svg>',
        ];

        yield 'Keeps stroke details when the element sets the stroke in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="none" stroke-width="2" style="stroke:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="none" stroke-width="2" style="stroke:red"/></svg>',
        ];

        yield 'Keeps stroke details when an ancestor sets the stroke in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke:red"><path d="M0 0h1" stroke-width="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke:red"><path d="M0 0h1" stroke-width="2"/></g></svg>',
        ];

        yield 'Removes stroke details when another property is set in a style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke-width:3"><path d="M0 0h1" stroke-width="2" style="stroke-linecap:round"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke-width:3"><path d="M0 0h1" stroke-width="2" style="stroke-linecap:round"/></g></svg>',
        ];

        yield 'Keeps stroke details of an element with an id that does not set the stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1" stroke-width="2"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1" stroke-width="2"/></defs></svg>',
        ];

        yield 'Keeps stroke details below an ancestor with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol id="s"><g><path d="M0 0h1" stroke-width="2"/></g></symbol><g xml:id="t"><path d="M0 0h1" stroke-width="3"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol id="s"><g><path d="M0 0h1" stroke-width="2"/></g></symbol><g xml:id="t"><path d="M0 0h1" stroke-width="3"/></g></svg>',
        ];

        yield 'Removes stroke details of an element with an id that sets no stroke itself' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1" stroke="none" stroke-width="2"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1" stroke="none"/></defs></svg>',
        ];

        yield 'Removes stroke details inside an ancestor with an id that sets no stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="g" stroke="none"><path d="M0 0h1" stroke-width="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="g" stroke="none"><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes stroke details except the width when the width is zero' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width="0" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="5" stroke-dasharray="1 2" stroke-dashoffset="1" stroke-opacity=".5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width="0"/></svg>',
        ];

        yield 'Removes stroke details below every zero width spelling' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width=" 0.0 " stroke-linecap="round"/><path d="M0 0h1" stroke="red" stroke-width="-.0e5" stroke-linecap="round"/><path d="M0 0h1" stroke="red" stroke-width="+00." stroke-linecap="round"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width=" 0.0 "/><path d="M0 0h1" stroke="red" stroke-width="-.0e5"/><path d="M0 0h1" stroke="red" stroke-width="+00."/></svg>',
        ];

        yield 'Removes stroke details below an inherited zero width' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="0"><path d="M0 0h1" stroke="red" stroke-dasharray="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="0"><path d="M0 0h1" stroke="red"/></svg>',
        ];

        yield 'Keeps stroke details below a non-zero or unitized width' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width="0.01" stroke-linecap="round"/><path d="M0 0h1" stroke="red" stroke-width="0px" stroke-linecap="round"/><path d="M0 0h1" stroke="red" stroke-width="10" stroke-linecap="round"/><path d="M0 0h1" stroke="red" stroke-width="0 0" stroke-linecap="round"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width="0.01" stroke-linecap="round"/><path d="M0 0h1" stroke="red" stroke-width="0px" stroke-linecap="round"/><path d="M0 0h1" stroke="red" stroke-width="10" stroke-linecap="round"/><path d="M0 0h1" stroke="red" stroke-width="0 0" stroke-linecap="round"/></svg>',
        ];

        yield 'Keeps stroke details of a zero width group whose descendant sets a width' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red" stroke-width="0" stroke-linecap="round"><path d="M0 0h1" stroke-width="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red" stroke-width="0" stroke-linecap="round"><path d="M0 0h1" stroke-width="2"/></g></svg>',
        ];

        yield 'Keeps stroke details below an invisible stroke opacity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-opacity="0" stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-opacity="0" stroke-width="2"/></svg>',
        ];

        yield 'Removes fill details from an element without fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" fill="none" fill-opacity=".5" fill-rule="evenodd" clip-rule="evenodd"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" fill="none" clip-rule="evenodd"/></svg>',
        ];

        yield 'Removes fill details from an element that inherits no fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none"><g><path d="M0 0h1" fill-rule="evenodd"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none"><g><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps fill details of an element that inherits the initial fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" fill-rule="evenodd" fill-opacity=".5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" fill-rule="evenodd" fill-opacity=".5"/></svg>',
        ];

        yield 'Keeps fill details of a group whose descendant sets a fill' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="M0 0h1"/><path d="M0 0h1" fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="M0 0h1"/><path d="M0 0h1" fill="red"/></g></svg>',
        ];

        yield 'Removes stroke details but keeps fill details when the fill is set below' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd" stroke-width="2"><path d="M0 0h1" fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd" stroke-width="2"><path d="M0 0h1" fill="red"/></g></svg>',
        ];

        yield 'Removes fill details but keeps stroke details when the stroke is set below' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd" stroke-width="2"><path d="M0 0h1" stroke="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none" stroke-width="2"><path d="M0 0h1" stroke="red"/></g></svg>',
        ];

        yield 'Keeps details of an element that uses inherit' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="none" stroke-width="inherit" fill="none" fill-rule="evenodd"/><path d="M0 0h1" fill="none" fill-rule="evenodd" style="color:INHERIT"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="none" stroke-width="inherit" fill="none" fill-rule="evenodd"/><path d="M0 0h1" fill="none" fill-rule="evenodd" style="color:INHERIT"/></svg>',
        ];

        yield 'Removes details of a later element when an earlier one uses inherit' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="inherit"/><path d="M0 0h1" stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="inherit"/><path d="M0 0h1" stroke-width="2"/></svg>',
        ];

        yield 'Keeps details of a use element and its ancestors' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><path id="p" d="M0 0h1" stroke="red"/></defs><g stroke-width="2"><g><use xlink:href="#p"/></g></g><use xlink:href="#p" stroke-width="2"/><path d="M0 0h1" stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><path id="p" d="M0 0h1" stroke="red"/></defs><g stroke-width="2"><g><use xlink:href="#p"/></g></g><use xlink:href="#p" stroke-width="2"/><path d="M0 0h1" stroke-width="2"/></svg>',
        ];

        yield 'Keeps everything when a marker is used' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><marker id="m"><path d="M0 0h1"/></marker><path d="M0 0h1" stroke-width="2" marker-end="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><marker id="m"><path d="M0 0h1"/></marker><path d="M0 0h1" stroke-width="2" marker-end="url(#m)"/></svg>',
        ];

        yield 'Keeps everything when a marker is used in a style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" style="MARKER: url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" style="MARKER: url(#m)"/></svg>',
        ];

        yield 'Keeps everything when pointer events are set' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2"/><path d="M0 0h1" pointer-events="stroke"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2"/><path d="M0 0h1" pointer-events="stroke"/></svg>',
        ];

        yield 'Keeps everything when pointer events are set in a style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" style="fill:red;pointer-events:all"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" style="fill:red;pointer-events:all"/></svg>',
        ];

        yield 'Removes details when other attributes mention markers or pointer events in their value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" class="marker pointer-events"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" class="marker pointer-events"/></svg>',
        ];

        yield 'Keeps stroke details when a stroke property is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" fill="none" fill-rule="evenodd"><set attributeName=" stroke " to="red"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" fill="none"><set attributeName=" stroke " to="red"/></path></svg>',
        ];

        yield 'Keeps stroke details when the stroke width is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width="0" stroke-linecap="round"><animate attributeName="stroke-width" to="2" dur="1s"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke="red" stroke-width="0" stroke-linecap="round"><animate attributeName="stroke-width" to="2" dur="1s"/></path></svg>',
        ];

        yield 'Keeps fill details when the fill rule is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" fill="none" fill-opacity=".5"><set attributeName="fill-rule" to="evenodd"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" fill="none" fill-opacity=".5"><set attributeName="fill-rule" to="evenodd"/></path></svg>',
        ];

        yield 'Keeps stroke details when a stroke detail is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" stroke-linecap="round"><animate attributeName="stroke-dashoffset" to="2" dur="1s"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" stroke-linecap="round"><animate attributeName="stroke-dashoffset" to="2" dur="1s"/></path></svg>',
        ];

        yield 'Keeps fill details when a fill property is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" fill="none" fill-rule="evenodd"><animate attributeName="fill-opacity" to="0" dur="1s"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2" fill="none" fill-rule="evenodd"><animate attributeName="fill-opacity" to="0" dur="1s"/></path></svg>',
        ];

        yield 'Keeps fill details when the fill is animated by reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="p" d="M0 0h1" fill="none" fill-rule="evenodd"/><set href="#p" attributeName="fill" to="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="p" d="M0 0h1" fill="none" fill-rule="evenodd"/><set href="#p" attributeName="fill" to="red"/></svg>',
        ];

        yield 'Removes details from prefixed elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" fill="none"><svg:g stroke-width="2"><svg:path d="M0 0h1" fill-rule="evenodd"/></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" fill="none"><svg:g stroke-width="2"><svg:path d="M0 0h1"/></svg:g></svg:svg>',
        ];

        yield 'Keeps details of prefixed use elements and their ancestors' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><svg:g stroke-width="2"><svg:use xlink:href="#p"/></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><svg:g stroke-width="2"><svg:use xlink:href="#p"/></svg:g></svg:svg>',
        ];

        yield 'Removes details in documents without a namespace' => [
            '<svg><path d="M0 0h1" stroke-width="2"/></svg>',
            '<svg><path d="M0 0h1" stroke-width="2"/></svg>',
        ];

        yield 'Keeps details of elements in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="1" height="1"><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2"/></svg></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="1" height="1"><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2"/></svg></foreignObject></svg>',
        ];

        yield 'Keeps details of elements in other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:path stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:path stroke-width="2"/></svg>',
        ];

        yield 'Keeps everything when a style element is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path{stroke:red}</style><path d="M0 0h1" stroke-width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path{stroke:red}</style><path d="M0 0h1" stroke-width="2"/></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2"/></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" stroke-width="2"/></svg>',
        ];

        yield 'Keeps documents without details untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    private function optimizeContent(string $content): string
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addPass(new RemoveUselessStrokeAndFill());

        return $svgOptimizer->optimize()->getContent();
    }
}
