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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEnableBackground;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\AttributeMigrationTrait;
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
#[CoversClass(RemoveEnableBackground::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeMigrationTrait::class)]
#[CoversTrait(DeclarationTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveEnableBackgroundTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveEnableBackground()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Removes enable-background for svg with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background from an svg with different dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background for mask with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <mask xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                        <rect x="10" y="10" width="30" height="30"/>
                    </mask>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><mask xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></mask></svg>
                XML,
        ];

        yield 'Removes enable-background from a pattern' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                        <rect x="10" y="10" width="30" height="30"/>
                    </pattern>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></pattern></svg>
                XML,
        ];

        yield 'Removes enable-background for pattern with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                        <rect x="10" y="10" width="30" height="30"/>
                    </pattern>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></pattern></svg>
                XML,
        ];

        yield 'Removes enable-background for svg with no filter' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background whose dimensions differ from the element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes an invalid enable-background' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="invalid value">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background from style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="enable-background:new 0 0 100 50;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background from mixed style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="fill:red; enable-background:new 0 0 100 50;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="fill:red"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background in style if dimensions do not match' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="enable-background:new 0 0 200 100;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background in style if invalid value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="enable-background:invalid value;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background from style if it matches dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="enable-background:new 0 0 100 50;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background=" new 0 0 100 50 "/>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"/>',
        ];

        yield 'Removes a last enable-background declaration without a semicolon' => [
            '<svg xmlns="http://www.w3.org/2000/svg" style="fill:red;ENABLE-BACKGROUND : new 0 0 10 10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" style="fill:red"/>',
        ];

        yield 'Keeps custom properties whose name ends in enable-background' => [
            '<svg xmlns="http://www.w3.org/2000/svg" style="--x-enable-background:1;fill:var(--x-enable-background)"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" style="--x-enable-background:1;fill:var(--x-enable-background)"/>',
        ];

        yield 'Keeps enable-background inside a string' => [
            '<svg xmlns="http://www.w3.org/2000/svg" style="font-family:\'a;enable-background:x;\';fill:red"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" style="font-family:\'a;enable-background:x;\';fill:red"/>',
        ];

        yield 'Keeps a style with an unclosed string untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg" style="enable-background:new;font-family:\'a"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" style="enable-background:new;font-family:\'a"/>',
        ];

        yield 'Removes enable-background from a later element after skipping an earlier style' => [
            '<svg xmlns="http://www.w3.org/2000/svg" style="fill:red"><g style="enable-background:new"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" style="fill:red"><g/></svg>',
        ];

        yield 'Removes enable-background in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" width="10" height="5" enable-background="new 0 0 10 5"><svg:g style="enable-background:new;fill:red"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" width="10" height="5"><svg:g style="fill:red"/></svg:svg>',
        ];

        yield 'Keeps a namespaced enable-background attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" width="10" height="5" x:enable-background="new 0 0 10 5"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" width="10" height="5" x:enable-background="new 0 0 10 5"/>',
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps an enable-background attribute that a style sheet selects' => [
            '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new" style="enable-background:new"><style>[enable-background]{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new"><style>[enable-background]{fill:red}</style></svg>',
        ];

        yield 'Keeps a style attribute that a style sheet selects' => [
            '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new" style="enable-background:new"><style>[style]{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" style="enable-background:new"><style>[style]{fill:red}</style></svg>',
        ];

        yield 'Leaves the document untouched with an external style sheet' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" enable-background="new" style="enable-background:new"/>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" enable-background="new" style="enable-background:new"/>',
        ];

        yield 'Keeps enable-background in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" enable-background="new" style="enable-background:new">a</div></foreignObject><rect enable-background="new"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" enable-background="new" style="enable-background:new">a</div></foreignObject><rect/></svg>',
        ];

        yield 'Removes the accumulate value of enable-background' => [
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background=" accumulate "/>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"/>',
        ];
    }
}
