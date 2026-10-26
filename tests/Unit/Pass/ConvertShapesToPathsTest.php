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
use MathiasReker\PhpSvgOptimizer\Pass\ConvertShapesToPaths;
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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataSerializerTrait;
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
#[CoversClass(ConvertShapesToPaths::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeNodeTrait::class)]
#[CoversTrait(ElementRenameTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(NumberListTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(PathDataSerializerTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class ConvertShapesToPathsTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideConvertsShapesToPathsCases')]
    #[Test]
    public function convertsShapesToPaths(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new ConvertShapesToPaths()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideConvertsShapesToPathsCases(): iterable
    {
        yield 'Keeps attributes whose names hold characters outside the basic multilingual plane' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x\u{1F600}=\"urn:x\"><rect width=\"5\" height=\"5\" x\u{1F600}:a\u{1F600}=\"1\" b\u{1F600}=\"2\"/></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x\u{1F600}=\"urn:x\"><path x\u{1F600}:a\u{1F600}=\"1\" b\u{1F600}=\"2\" d=\"M0 0h5v5h-5z\"/></svg>",
        ];

        yield 'Keeps every attribute in a namespace when one shares its local name with an attribute without a namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a" xmlns:b="urn:b"><rect width="5" height="5" a:z="1" b:y="3" z="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a" xmlns:b="urn:b"><path z="2" d="M0 0h5v5h-5z" a:z="1" b:y="3"/></svg>',
        ];

        yield 'Keeps an attribute in a namespace that shares its local name with one without a namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect width="5" height="5" a:z="1" z="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><path z="2" d="M0 0h5v5h-5z" a:z="1"/></svg>',
        ];
        yield 'Keeps a rect with a trailing decimal point' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="200." height="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="200." height="10"/></svg>',
        ];

        yield 'Leaves the document untouched with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><rect width="5" height="5"/></svg>',
        ];

        yield 'Leaves the document untouched with a script' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>f()</script><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>f()</script><rect width="5" height="5"/></svg>',
        ];

        yield 'Leaves the document untouched with a linked style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps shapes targeted by an animation through href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" x="10" y="20" width="30" height="40"/><animate href="#r" attributeName="width" to="5"/><rect x="10" y="20" width="30" height="40"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" x="10" y="20" width="30" height="40"/><animate href="#r" attributeName="width" to="5"/><path d="M10 20h30v40h-30z"/></svg>',
        ];

        yield 'Keeps shapes targeted by an animation through xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r" x="10" y="20" width="30" height="40"/><set xlink:href="#r" attributeName="width" to="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r" x="10" y="20" width="30" height="40"/><set xlink:href="#r" attributeName="width" to="5"/></svg>',
        ];

        yield 'Keeps shapes targeted by an animation through an href with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" x="10" y="20" width="30" height="40"/><animate href=" #r " attributeName="width" to="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" x="10" y="20" width="30" height="40"/><animate href=" #r " attributeName="width" to="5"/></svg>',
        ];

        yield 'Keeps every shape targeted by an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" x="10" y="20" width="30" height="40"/><rect id="b" x="10" y="20" width="30" height="40"/><set href="#a" attributeName="width" to="5"/><set href="#b" attributeName="width" to="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" x="10" y="20" width="30" height="40"/><rect id="b" x="10" y="20" width="30" height="40"/><set href="#a" attributeName="width" to="5"/><set href="#b" attributeName="width" to="5"/></svg>',
        ];

        yield 'Keeps attributes sharing a local name across namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect fill="red" x:fill="blue" x="10" y="20" width="30" height="40"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><path fill="red" d="M10 20h30v40h-30z" x:fill="blue"/></svg>',
        ];

        yield 'Converts rect' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y="20" width="30" height="40" fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="red" d="M10 20h30v40h-30z"/></svg>',
        ];

        yield 'Converts rect without position' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h5v5h-5z"/></svg>',
        ];

        yield 'Converts line' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><line x1="1" y1="2" x2="3" y2="4" stroke="#000"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="#000" d="M1 2 3 4"/></svg>',
        ];

        yield 'Converts polyline and polygon' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0,0 10,10 20,0"/><polygon points="0 0 10 10 20 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 10 10 20 0"/><path d="M0 0 10 10 20 0z"/></svg>',
        ];

        yield 'Keeps id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" width="10" height="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path id="a" d="M0 0h10v10h-10z"/></svg>',
        ];

        yield 'Drops a prefix bound to the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><s:rect width="10" height="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><path d="M0 0h10v10h-10z"/></svg>',
        ];

        yield 'Keeps a prefix bound to a non-default namespace' => [
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><s:rect x="10" y="20" width="30" height="40"/></s:svg>',
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><s:path d="M10 20h30v40h-30z"/></s:svg>',
        ];

        yield 'Never rounds' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="0.100000000000000000001" width="10" height="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M.100000000000000000001 0h10v10h-10z"/></svg>',
        ];

        yield 'Keeps the document when converting makes it larger' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><line x2="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><line x2="1"/></svg>',
        ];

        yield 'Keeps rounded rect' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" rx="2"/><rect width="10" height="10" ry="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" rx="2"/><rect width="10" height="10" ry="2"/></svg>',
        ];

        yield 'Keeps rect with units' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10%" height="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10%" height="10"/></svg>',
        ];

        yield 'Keeps rect without positive size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="10"/><rect width="-1" height="10"/><rect height="10"/><rect width="10" height="0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="0" height="10"/><rect width="-1" height="10"/><rect height="10"/><rect width="10" height="0"/></svg>',
        ];

        yield 'Keeps polyline with an odd number of coordinates' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 10 10 20"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 10 10 20"/></svg>',
        ];

        yield 'Keeps polyline with invalid or no points' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 a b"/><polygon points=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 a b"/><polygon points=""/></svg>',
        ];

        yield 'Keeps polygon with an empty item between commas' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0,0,,10,0 10,10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0,0,,10,0 10,10"/></svg>',
        ];

        yield 'Keeps a polyline with a leading comma and converts a polygon with a trailing comma, which browsers accept' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points=",0 0 10 10"/><polygon points="0 0 10 10 20 0,"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points=",0 0 10 10"/><path d="M0 0 10 10 20 0z"/></svg>',
        ];

        yield 'Converts points without separators, as minified points are written' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 10-10 .5.5" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="red" d="M0 0 10-10 .5.5"/></svg>',
        ];

        yield 'Keeps polyline with a comma surrounded by spaces twice' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 , , 10 10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0 , , 10 10"/></svg>',
        ];

        yield 'Converts polygon with commas surrounded by whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points=" 0 , 0 10 ,0,10 10 "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 10 0 10 10z"/></svg>',
        ];

        yield 'Keeps line with units' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><line x1="1" y1="2" x2="30%" y2="40" stroke="#000"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><line x1="1" y1="2" x2="30%" y2="40" stroke="#000"/></svg>',
        ];

        yield 'Keeps shape with style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" style="fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" style="fill:red"/></svg>',
        ];

        yield 'Keeps shape with child elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"><set attributeName="width" to="5"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"><set attributeName="width" to="5"/></rect></svg>',
        ];

        yield 'Keeps shapes when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect width="10" height="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect width="10" height="10"/></svg>',
        ];

        yield 'Keeps shapes when a marker exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><marker id="m"/><line x1="0" y1="0" x2="10" y2="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><marker id="m"/><line x1="0" y1="0" x2="10" y2="10"/></svg>',
        ];

        yield 'Keeps comments inside a converted shape' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y="20" width="30" height="40"><!--c--></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M10 20h30v40h-30z"><!--c--></path></svg>',
        ];

        yield 'Keeps attributes in other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:a="1" x="10" y="20" width="30" height="40"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><path x:a="1" d="M10 20h30v40h-30z"/></svg>',
        ];

        yield 'Converts negative positions and minifies numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="-10.50" y="+0.0" width="30" height="40"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M-10.5 0h30v40h-30z"/></svg>',
        ];

        yield 'Converts values with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x=" 10 " y="20" width="30" height="40"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M10 20h30v40h-30z"/></svg>',
        ];

        yield 'Converts polyline with four coordinates and surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points=" 0 0 10 10 " stroke="#000"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="#000" d="M0 0 10 10"/></svg>',
        ];

        yield 'Converts every shape in the document' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect x="1" y="2" width="30" height="40"/></g><rect x="5" y="6" width="70" height="80"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><path d="M1 2h30v40h-30z"/></g><path d="M5 6h70v80h-70z"/></svg>',
        ];

        yield 'Keeps shapes referenced by a text path or a motion path' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r" x="10" y="10" width="100" height="100"/><text><textPath href="#r">a</textPath></text><line id="l" x2="10"/><animateMotion dur="1s"><mpath xlink:href=" #l "/></animateMotion><rect x="10" y="10" width="100" height="100"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r" x="10" y="10" width="100" height="100"/><text><textPath href="#r">a</textPath></text><line id="l" x2="10"/><animateMotion dur="1s"><mpath xlink:href=" #l "/></animateMotion><path d="M10 10h100v100h-100z"/></svg>',
        ];

        yield 'Keeps shapes targeted through xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect xml:id="a" width="50" height="50"/><set href="#a" attributeName="width" to="9"/><rect width="50" height="50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect xml:id="a" width="50" height="50"/><set href="#a" attributeName="width" to="9"/><path d="M0 0h50v50h-50z"/></svg>',
        ];

        yield 'Keeps the shapes that both references of a text path point to' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="a" width="5" height="5"/><rect id="b" width="5" height="5"/><text><textPath href="#a" xlink:href="#b">t</textPath><textPath href="#">u</textPath></text><rect width="5" height="5"/><rect id="c" xml:id="d" width="5" height="5"/><rect id="e" xml:id="f" width="5" height="5"/><mpath href="#f"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="a" width="5" height="5"/><rect id="b" width="5" height="5"/><text><textPath href="#a" xlink:href="#b">t</textPath><textPath href="#">u</textPath></text><path d="M0 0h5v5h-5z"/><path id="c" d="M0 0h5v5h-5z" xml:id="d"/><rect id="e" xml:id="f" width="5" height="5"/><mpath href="#f"/></svg>',
        ];

        yield 'Keeps shapes in other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:rect xmlns:x="urn:x" width="10" height="10"/><rect x="10" y="10" width="100" height="100"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:rect xmlns:x="urn:x" width="10" height="10"/><path d="M10 10h100v100h-100z"/></svg>',
        ];

        yield 'Keeps shapes when a stylesheet instruction exists' => [
            '<?xml-stylesheet type="text/css" href="s.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y="10" width="100" height="100"/></svg>',
            '<?xml-stylesheet type="text/css" href="s.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y="10" width="100" height="100"/></svg>',
        ];

        yield 'Converts shapes in a document without a namespace' => [
            '<svg><rect x="10" y="10" width="100" height="100"/></svg>',
            '<svg><path d="M10 10h100v100h-100z"/></svg>',
        ];

        yield 'Converts shapes with a path length and a round line cap' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><line x1="5" y1="5" x2="5" y2="5" stroke-linecap="round" pathLength="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" pathLength="10" d="M5 5 5 5"/></svg>',
        ];

        yield 'Keeps shapes with percentage or auto sizes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="50%" height="10"/><rect width="auto" height="10"/><line x2="1em"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="50%" height="10"/><rect width="auto" height="10"/><line x2="1em"/></svg>',
        ];

        yield 'Converts shapes holding only text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0,0 100,0 100,100">&#x1F600;</polygon></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 100 0 100 100z">😀</path></svg>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideConvertsShapesToPathsIdempotentlyCases')]
    #[Test]
    public function convertsShapesToPathsIdempotently(string $optimized): void
    {
        $pipeline = new Pipeline([new ConvertShapesToPaths()]);

        self::assertSame($optimized, $pipeline->run($optimized));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideConvertsShapesToPathsIdempotentlyCases(): iterable
    {
        foreach (self::provideConvertsShapesToPathsCases() as $name => [, $expected]) {
            yield $name => [$expected];
        }
    }
}
