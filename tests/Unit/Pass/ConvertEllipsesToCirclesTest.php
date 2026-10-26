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
use MathiasReker\PhpSvgOptimizer\Pass\ConvertEllipsesToCircles;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
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
#[CoversClass(ConvertEllipsesToCircles::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeNodeTrait::class)]
#[CoversTrait(ElementRenameTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class ConvertEllipsesToCirclesTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideConvertsEllipsesToCirclesCases')]
    #[Test]
    public function convertsEllipsesToCircles(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new ConvertEllipsesToCircles()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideConvertsEllipsesToCirclesCases(): iterable
    {
        yield 'Leaves the document untouched with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5" onclick="f()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5" onclick="f()"/></svg>',
        ];

        yield 'Leaves the document untouched with a linked style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><ellipse rx="5" ry="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><ellipse rx="5" ry="5"/></svg>',
        ];

        yield 'Keeps ellipses targeted by an animation through href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"/><animate href="#e" attributeName="rx" to="5"/><ellipse rx="3" ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"/><animate href="#e" attributeName="rx" to="5"/><circle r="3"/></svg>',
        ];

        yield 'Converts ellipse with equal radii' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse cx="5" cy="5" rx="3" ry="3" fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" fill="red" r="3"/></svg>',
        ];

        yield 'Converts equal radii written differently' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="3"/></svg>',
        ];

        yield 'Keeps ellipse with different radii' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="4"/></svg>',
        ];

        yield 'Keeps ellipse with auto radius' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="auto"/><ellipse rx="3"/><ellipse ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="auto"/><ellipse rx="3"/><ellipse ry="3"/></svg>',
        ];

        yield 'Keeps ellipse with style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3" style="rx:4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3" style="rx:4"/></svg>',
        ];

        yield 'Keeps ellipse with child elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3"><set attributeName="rx" to="5"/></ellipse></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3"><set attributeName="rx" to="5"/></ellipse></svg>',
        ];

        yield 'Keeps ellipses when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>ellipse{fill:red}</style><ellipse rx="3" ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>ellipse{fill:red}</style><ellipse rx="3" ry="3"/></svg>',
        ];

        yield 'Keeps the id and children text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"><!--c--></ellipse></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle id="e" r="3"><!--c--></circle></svg>',
        ];

        yield 'Converts equal radii with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx=" 3" ry="3 "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r=" 3"/></svg>',
        ];

        yield 'Converts every matching ellipse' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="1" ry="1"/><ellipse rx="1" ry="2"/><g><ellipse rx="2" ry="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="1"/><ellipse rx="1" ry="2"/><g><circle r="2"/></g></svg>',
        ];

        yield 'Keeps ellipses in other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:ellipse xmlns:x="urn:x" rx="1" ry="1"/><ellipse rx="1" ry="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:ellipse xmlns:x="urn:x" rx="1" ry="1"/><circle r="1"/></svg>',
        ];

        yield 'Keeps ellipses targeted through xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse xml:id="a" rx="5" ry="5"/><set href="#a" attributeName="rx" to="9"/><ellipse rx="5" ry="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse xml:id="a" rx="5" ry="5"/><set href="#a" attributeName="rx" to="9"/><circle r="5"/></svg>',
        ];

        yield 'Keeps ellipses when a stylesheet instruction exists' => [
            '<?xml-stylesheet type="text/css" href="s.css"?><svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5"/></svg>',
            '<?xml-stylesheet type="text/css" href="s.css"?><svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5"/></svg>',
        ];

        yield 'Converts ellipses in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:ellipse cx="1" rx="5" ry="5.0"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:circle cx="1" r="5"/></svg:svg>',
        ];

        yield 'Keeps ellipses with radii in units or percentages' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5px" ry="5px"/><ellipse rx="5%" ry="5%"/><ellipse rx="auto" ry="auto"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5px" ry="5px"/><ellipse rx="5%" ry="5%"/><ellipse rx="auto" ry="auto"/></svg>',
        ];

        yield 'Keeps ellipses with only one radius' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5"/><ellipse ry="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5"/><ellipse ry="5"/></svg>',
        ];

        yield 'Keeps ellipses targeted by an animation through a custom xlink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><ellipse id="e" rx="5" ry="5"/><set l:href="#e" attributeName="rx" to="9"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><ellipse id="e" rx="5" ry="5"/><set l:href="#e" attributeName="rx" to="9"/></svg>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideConvertsEllipsesToCirclesIdempotentlyCases')]
    #[Test]
    public function convertsEllipsesToCirclesIdempotently(string $optimized): void
    {
        $pipeline = new Pipeline([new ConvertEllipsesToCircles()]);

        self::assertSame($optimized, $pipeline->run($optimized));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideConvertsEllipsesToCirclesIdempotentlyCases(): iterable
    {
        foreach (self::provideConvertsEllipsesToCirclesCases() as $name => [, $expected]) {
            yield $name => [$expected];
        }
    }
}
