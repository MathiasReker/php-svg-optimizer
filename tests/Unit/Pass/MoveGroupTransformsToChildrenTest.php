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
use MathiasReker\PhpSvgOptimizer\Pass\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MoveCommonAttributesToGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MoveGroupTransformsToChildren;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Transform\TransformListTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
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
#[CoversClass(MoveGroupTransformsToChildren::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdReferenceTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(InheritanceTrait::class)]
#[CoversTrait(NamedColorTrait::class)]
#[CoversTrait(NumberListTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[CoversTrait(TransformListTrait::class)]
#[UsesClass(Attribute::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(FlattenGroups::class)]
#[UsesClass(MoveCommonAttributesToGroups::class)]
#[UsesClass(NamespaceUri::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class MoveGroupTransformsToChildrenTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideMovesGroupTransformsToChildrenCases')]
    #[Test]
    public function movesGroupTransformsToChildren(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new MoveGroupTransformsToChildren()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideMovesGroupTransformsToChildrenCases(): iterable
    {
        yield 'Leaves the document untouched with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><g transform="scale(2)"><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><g transform="scale(2)"><rect width="5" height="5"/></g></svg>',
        ];

        yield 'Moves the transform of a group with a single child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="1" height="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1" height="1" transform="scale(2)"/></g></svg>',
        ];

        yield 'Moves a padded transform when the output does not grow' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="                         scale(2)                         "><rect/><circle/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect transform="scale(2)"/><circle transform="scale(2)"/></g></svg>',
        ];

        yield 'Reverts the move when the output grows' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><circle/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><circle/></g></svg>',
        ];

        yield 'Keeps everything when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><g transform="scale(2)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><g transform="scale(2)"><rect/></g></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/></g></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/></g></svg>',
        ];

        yield 'Keeps documents without groups untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    #[DataProvider('provideMovesGroupTransformsToChildrenInTheDocumentCases')]
    #[Test]
    public function movesGroupTransformsToChildrenInTheDocument(string $input, string $expected): void
    {
        self::assertSame($expected, $this->applyPass($input));
    }

    #[DataProvider('provideMovesGroupTransformsToChildrenInTheDocumentCases')]
    #[Test]
    public function movesGroupTransformsToChildrenInTheDocumentIdempotently(string $input, string $expected): void
    {
        self::assertSame($expected, $this->applyPass($this->applyPass($input)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideMovesGroupTransformsToChildrenInTheDocumentCases(): iterable
    {
        yield 'Prepends the group transform to every child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1 2)" fill="red"><path d="M0 0h1"/><rect transform="rotate(45)"/><circle transform=" scale(2) "/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><path d="M0 0h1" transform="translate(1 2)"/><rect transform="translate(1 2) rotate(45)"/><circle transform="translate(1 2) scale(2)"/></g></svg>',
        ];

        yield 'Moves transforms onto every supported element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><circle/><ellipse/><line/><path/><polygon/><polyline/><rect/><text>a</text><use href="#u"/></g><path id="u"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><circle transform="scale(2)"/><ellipse transform="scale(2)"/><line transform="scale(2)"/><path transform="scale(2)"/><polygon transform="scale(2)"/><polyline transform="scale(2)"/><rect transform="scale(2)"/><text transform="scale(2)">a</text><use href="#u" transform="scale(2)"/></g><path id="u"/></svg>',
        ];

        yield 'Moves every transform function' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1,0,0,1,2,3)translate(-1.5e2) scale(.5,2) rotate(10 1 1),skewX(5) skewY(-5)"><rect transform="translate(1)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect transform="matrix(1,0,0,1,2,3)translate(-1.5e2) scale(.5,2) rotate(10 1 1),skewX(5) skewY(-5) translate(1)"/></g></svg>',
        ];

        yield 'Moves a transform with whitespace before the parenthesis' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate (1)"><rect/></g><g transform="matrix (1 0 0 1 0 0)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect transform="translate (1)"/></g><g><rect transform="matrix (1 0 0 1 0 0)"/></g></svg>',
        ];

        yield 'Keeps a group whose transform has a number ending in a decimal point' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1.)"><rect transform="scale(2)"/><rect/></g><g transform="translate(1.e2)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1.)"><rect transform="scale(2)"/><rect/></g><g transform="translate(1.e2)"><rect/></g></svg>',
        ];

        yield 'Keeps a group whose child transform has a number ending in a decimal point' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect transform="translate(1.)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect transform="translate(1.)"/></g></svg>',
        ];

        yield 'Moves transforms onto children with their own clip, mask or filter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect clip-path="url(#c)" mask="url(#m)" filter="url(#f)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect clip-path="url(#c)" mask="url(#m)" filter="url(#f)" transform="scale(2)"/></g></svg>',
        ];

        yield 'Ignores descriptive children and text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><title>t</title>x<rect/><desc>d</desc><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title>t</title>x<rect transform="scale(2)"/><desc>d</desc><rect transform="scale(2)"/></g></svg>',
        ];

        yield 'Keeps groups with only descriptive children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><title>t</title></g><g transform="scale(2)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><title>t</title></g><g transform="scale(2)"/></svg>',
        ];

        yield 'Keeps groups with another child element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><g/></g><g transform="scale(2)"><rect/><image/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><g/></g><g transform="scale(2)"><rect/><image/></g></svg>',
        ];

        yield 'Keeps groups with a foreign child' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://example.com/x"><g transform="scale(2)"><rect/><x:rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://example.com/x"><g transform="scale(2)"><rect/><x:rect/></g></svg>',
        ];

        yield 'Keeps groups with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a" transform="scale(2)"><rect/></g><g xml:id="b" transform="scale(2)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a" transform="scale(2)"><rect/></g><g xml:id="b" transform="scale(2)"><rect/></g></svg>',
        ];

        yield 'Keeps groups with a clip path, mask or filter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" clip-path="url(#c)"><rect/></g><g transform="scale(2)" mask="url(#m)"><rect/></g><g transform="scale(2)" filter="url(#f)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" clip-path="url(#c)"><rect/></g><g transform="scale(2)" mask="url(#m)"><rect/></g><g transform="scale(2)" filter="url(#f)"><rect/></g></svg>',
        ];

        yield 'Keeps groups with a transform origin or box' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" transform-origin="center"><rect/></g><g transform="scale(2)" transform-box="fill-box"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" transform-origin="center"><rect/></g><g transform="scale(2)" transform-box="fill-box"><rect/></g></svg>',
        ];

        yield 'Keeps groups whose style declares a transform, clip, mask or filter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" style="Transform:none"><rect/></g><g transform="scale(2)" style="clip-path:url(#c)"><rect/></g><g transform="scale(2)" style="-webkit-mask-image:url(#m)"><rect/></g><g transform="scale(2)" style="filter:blur(1px)"><rect/></g><g transform="scale(2)" style="clip:rect(0 0 1 1)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" style="Transform:none"><rect/></g><g transform="scale(2)" style="clip-path:url(#c)"><rect/></g><g transform="scale(2)" style="-webkit-mask-image:url(#m)"><rect/></g><g transform="scale(2)" style="filter:blur(1px)"><rect/></g><g transform="scale(2)" style="clip:rect(0 0 1 1)"><rect/></g></svg>',
        ];

        yield 'Moves transforms of groups whose style declares other properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" style="opacity:.5"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="opacity:.5"><rect transform="scale(2)"/></g></svg>',
        ];

        yield 'Keeps groups with an invalid transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(a)"><rect/></g><g transform="scale(1,2,3)"><rect/></g><g transform="none"><rect/></g><g transform=""><rect/></g><g transform="scale(2) x"><rect/></g><g transform="x scale(2)"><rect/></g><g transform="rotate(1 2)"><rect/></g><g transform="matrix(1 2 3 4 5)"><rect/></g><g transform="Scale(2)"><rect/></g><g transform="scale(2px)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(a)"><rect/></g><g transform="scale(1,2,3)"><rect/></g><g transform="none"><rect/></g><g transform=""><rect/></g><g transform="scale(2) x"><rect/></g><g transform="x scale(2)"><rect/></g><g transform="rotate(1 2)"><rect/></g><g transform="matrix(1 2 3 4 5)"><rect/></g><g transform="Scale(2)"><rect/></g><g transform="scale(2px)"><rect/></g></svg>',
        ];

        yield 'Keeps groups with a child that has an invalid transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><rect transform="skewZ(1)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><rect transform="skewZ(1)"/></g></svg>',
        ];

        yield 'Moves transforms onto children with an empty transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect transform=""/><rect transform=" "/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect transform="scale(2)"/><rect transform="scale(2)"/></g></svg>',
        ];

        yield 'Keeps groups with a referenced child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><rect id="a"/><rect/></g><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><rect id="a"/><rect/></g><use href="#a"/></svg>',
        ];

        yield 'Moves transforms onto unreferenced children with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect id="a"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect id="a" transform="scale(2)"/></g></svg>',
        ];

        yield 'Keeps groups with a child that has a transform origin or box' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><rect transform-origin="center"/></g><g transform="scale(2)"><rect transform-box="fill-box"/></g><g transform="scale(2)"><rect style="transform-origin:center"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><rect transform-origin="center"/></g><g transform="scale(2)"><rect transform-box="fill-box"/></g><g transform="scale(2)"><rect style="transform-origin:center"/></g></svg>',
        ];

        yield 'Keeps groups with a child whose style declares a transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect style="transform:rotate(1deg)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect style="transform:rotate(1deg)"/></g></svg>',
        ];

        yield 'Moves transforms onto children whose style declares other properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect style="fill:red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect style="fill:red" transform="scale(2)"/></g></svg>',
        ];

        yield 'Keeps groups with an animated child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><rect><title>t</title><animateTransform attributeName="transform" type="rotate" to="45"/></rect></g><g transform="scale(2)"><rect><set attributeName="x" to="1"/></rect></g><g transform="scale(2)"><text>a<animateMotion path="M0 0h1"/></text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><rect><title>t</title><animateTransform attributeName="transform" type="rotate" to="45"/></rect></g><g transform="scale(2)"><rect><set attributeName="x" to="1"/></rect></g><g transform="scale(2)"><text>a<animateMotion path="M0 0h1"/></text></g></svg>',
        ];

        yield 'Moves transforms onto children with other child elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><text>a<tspan>b</tspan></text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><text transform="scale(2)">a<tspan>b</tspan></text></g></svg>',
        ];

        yield 'Keeps groups with animation children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><animateTransform attributeName="transform" type="rotate" to="45"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><animateTransform attributeName="transform" type="rotate" to="45"/></g></svg>',
        ];

        yield 'Keeps groups inside foreign objects' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><g transform="scale(2)"><rect/></g></svg></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><g transform="scale(2)"><rect/></g></svg></foreignObject></svg>',
        ];

        yield 'Skips a group and processes a later one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" filter="url(#f)"><rect/></g><g transform="scale(3)"><rect/></g><g transform="scale(4)"><g/></g><g transform="scale(5)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" filter="url(#f)"><rect/></g><g><rect transform="scale(3)"/></g><g transform="scale(4)"><g/></g><g><rect transform="scale(5)"/></g></svg>',
        ];

        yield 'Moves transforms in nested groups from the innermost group only' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><g transform="scale(3)"><g transform="scale(4)"><rect/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><g transform="scale(3)"><g><rect transform="scale(4)"/></g></g></g></svg>',
        ];

        yield 'Moves transforms inside definitions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"><g transform="scale(2)"><rect/></g></mask><rect mask="url(#m)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><mask id="m"><g><rect transform="scale(2)"/></g></mask><rect mask="url(#m)"/></svg>',
        ];

        yield 'Moves transforms in prefixed SVG documents' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g transform="scale(2)"><svg:rect/></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g><svg:rect transform="scale(2)"/></svg:g></svg:svg>',
        ];

        yield 'Keeps groups with only text and comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)">a<!--c--></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)">a<!--c--></g></svg>',
        ];

        yield 'Moves transforms past comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><!--c--><rect/><!--d--></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><!--c--><rect transform="scale(2)"/><!--d--></g></svg>',
        ];

        yield 'Moves transforms with odd whitespace, commas and newlines' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="&#10;&#9;translate( 1 ,2 ) ,&#10;scale( 2 )rotate(1,2,3)&#10;"><rect transform=" skewX( 1 ) , skewY(2)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect transform="translate( 1 ,2 ) ,&#10;scale( 2 )rotate(1,2,3) skewX( 1 ) , skewY(2)"/></g></svg>',
        ];

        yield 'Keeps groups whose transform has a dangling comma' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2),"><rect/></g><g transform=",scale(2)"><rect/></g><g transform="scale(2),,scale(3)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2),"><rect/></g><g transform=",scale(2)"><rect/></g><g transform="scale(2),,scale(3)"><rect/></g></svg>',
        ];

        yield 'Keeps groups whose child is the target of a transform animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect id="a"/></g><animateTransform href="#a" attributeName="transform" type="rotate" to="45"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect id="a"/></g><animateTransform href="#a" attributeName="transform" type="rotate" to="45"/></svg>',
        ];

        yield 'Keeps groups with nested groups at several levels' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><g transform="scale(3)"><circle/><g transform="rotate(4)"><path/><text>a</text></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect/><g transform="scale(3)"><circle/><g><path transform="rotate(4)"/><text transform="rotate(4)">a</text></g></g></g></svg>',
        ];

        yield 'Keeps groups without a transform untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect/></g></svg>',
        ];

        yield 'Keeps everything when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><g transform="scale(2)"><rect/><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><g transform="scale(2)"><rect/><rect/></g></svg>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideCombinedRulesConvergeCases')]
    #[Test]
    public function combinedRulesConverge(string $content, string $expected, string $expectedSecondPass): void
    {
        $firstPass = $this->optimizeCombined($content);
        $secondPass = $this->optimizeCombined($firstPass);

        self::assertSame($expected, $firstPass);
        self::assertSame($expectedSecondPass, $secondPass);
        self::assertSame($secondPass, $this->optimizeCombined($secondPass));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideCombinedRulesConvergeCases(): iterable
    {
        yield 'Lifts shared attributes and keeps the transform on a group with several children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" fill="red"><rect/><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" fill="red"><rect/><rect/></g></svg>',
        ];

        yield 'Moves the transform onto a single child and flattens the group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1 2)"><path fill="red" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="red" d="M0 0h1" transform="translate(1 2)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="red" d="M0 0h1" transform="translate(1 2)"/></svg>',
        ];

        yield 'Moves the transform out of a group that cannot be flattened' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5" transform="scale(2)"><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5"><rect fill="red" transform="scale(2)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5"><rect fill="red" transform="scale(2)"/></g></svg>',
        ];

        yield 'Handles transforms at several nesting levels' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><g transform="scale(3)"><rect fill="red"/><circle fill="red"/></g><g><rect fill="red" transform="rotate(5)"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><g transform="scale(3)" fill="red"><rect/><circle/></g><g><rect fill="red" transform="rotate(5)"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><g transform="scale(3)" fill="red"><rect/><circle/></g><g><rect fill="red" transform="rotate(5)"/></g></g></svg>',
        ];

        yield 'Flattens the groups when the children override the outer value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><g transform="scale(2)"><rect fill="blue"/><rect fill="blue"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="blue" transform="scale(2)"/><rect fill="blue" transform="scale(2)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="blue" transform="scale(2)"/><rect fill="blue" transform="scale(2)"/></svg>',
        ];

        yield 'Normalizes odd whitespace and merges transforms of flattened groups' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><g>\n<g transform=\"\n translate( 1 , 2 ),\n\tscale(2)\n\"><!--c--><rect fill=\"red\" transform=\"rotate(5)\"/></g><g transform=\"scale(3)\"><text fill=\"red\">a</text></g></g></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><!--c--><rect fill="red" transform="translate( 1 , 2 ),  scale(2) rotate(5)"/><text fill="red" transform="scale(3)">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!--c--><rect fill="red" transform="translate( 1 , 2 ),  scale(2) rotate(5)"/><text fill="red" transform="scale(3)">a</text></svg>',
        ];

        yield 'Lifts the attributes that flattening exposes in the same run' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5"><g transform="scale(2)"><rect stroke="red"/></g><g transform="scale(3)"><rect stroke="red"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5" stroke="red"><rect transform="scale(2)"/><rect transform="scale(3)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5" stroke="red"><rect transform="scale(2)"/><rect transform="scale(3)"/></g></svg>',
        ];

        yield 'Keeps referenced and animated content in place' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect id="a" fill="red"/></g><g transform="scale(2)"><rect fill="red"><animateTransform attributeName="transform" type="rotate" to="45"/></rect></g><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect id="a" fill="red"/></g><g transform="scale(2)"><rect fill="red"><animateTransform attributeName="transform" type="rotate" to="45"/></rect></g><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect id="a" fill="red"/></g><g transform="scale(2)"><rect fill="red"><animateTransform attributeName="transform" type="rotate" to="45"/></rect></g><use href="#a"/></svg>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    private function optimizeCombined(string $content): string
    {
        $pipeline = new Pipeline([new MoveCommonAttributesToGroups(), new MoveGroupTransformsToChildren(), new FlattenGroups()]);

        return $pipeline->run($content);
    }

    private function applyPass(string $content): string
    {
        self::assertNotSame('', $content);

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($content);

        (new MoveGroupTransformsToChildren())->apply($domDocument);

        return (string) $domDocument->saveXML($domDocument->documentElement);
    }
}
