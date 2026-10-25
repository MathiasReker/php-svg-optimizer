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
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveCommonAttributesToGroup;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MoveCommonAttributesToGroup::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MoveCommonAttributesToGroupTest extends TestCase
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
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimizeIsIdempotent(string $content, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($this->optimizeContent($content)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Leaves the document untouched with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><g><rect fill="red" width="5" height="5"/><circle fill="red" r="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><g><rect fill="red" width="5" height="5"/><circle fill="red" r="5"/></g></svg>',
        ];

        yield 'Moves a shared fill from two children to the group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><circle fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect/><circle/></g></svg>',
        ];

        yield 'Moves shared attributes and keeps differing ones' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red" stroke="blue" stroke-width="2"/><rect fill="red" stroke="green" stroke-width="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red" stroke-width="2"><rect stroke="blue"/><rect stroke="green"/></g></svg>',
        ];

        yield 'Moves from more than two children and appends after existing group attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5"><rect fill="red"/><rect fill="red"/><path fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5" fill="red"><rect/><rect/><path/></g></svg>',
        ];

        yield 'Keeps the attribute of a single child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/></g></svg>',
        ];

        yield 'Keeps attributes when a later child has another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red"/><rect fill="blue"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red"/><rect fill="blue"/></g></svg>',
        ];

        yield 'Keeps attributes when a later child lacks the attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect/></g></svg>',
        ];

        yield 'Keeps attributes when only a later child has the attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/><rect fill="red"/></g></svg>',
        ];

        yield 'Removes child attributes that equal the group attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect/><rect/></g></svg>',
        ];

        yield 'Keeps child attributes when the group has another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="blue"><rect fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="blue"><rect fill="red"/><rect fill="red"/></g></svg>',
        ];

        yield 'Keeps parent relative values that the group already sets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="50%" font-weight="bolder" font-stretch="wider"><text font-size="50%" font-weight="bolder" font-stretch="wider">a</text><text font-size="50%" font-weight="bolder" font-stretch="wider">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="50%" font-weight="bolder" font-stretch="wider"><text font-size="50%" font-weight="bolder" font-stretch="wider">a</text><text font-size="50%" font-weight="bolder" font-stretch="wider">b</text></g></svg>',
        ];

        yield 'Moves parent relative values to a group without them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><text font-size="50%" font-weight="bolder">a</text><text font-size="50%" font-weight="bolder">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="50%" font-weight="bolder"><text>a</text><text>b</text></g></svg>',
        ];

        yield 'Keeps font attributes when the group has a font relative value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red" letter-spacing="1em"><text font-size="12" font-family="serif" fill="blue">a</text><text font-size="12" font-family="serif" fill="blue">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red" letter-spacing="1em"><text font-size="12" font-family="serif" fill="blue">a</text><text font-size="12" font-family="serif" fill="blue">b</text></g></svg>',
        ];

        yield 'Moves other attributes when the group has a font relative value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g letter-spacing="1em"><text stroke="red">a</text><text stroke="red">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g letter-spacing="1em" stroke="red"><text>a</text><text>b</text></g></svg>',
        ];

        yield 'Moves font attributes when the group has no font relative value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g letter-spacing="2"><text font-size="12">a</text><text font-size="12">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g letter-spacing="2" font-size="12"><text>a</text><text>b</text></g></svg>',
        ];

        yield 'Keeps font relative values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect stroke-width="1em"/><rect stroke-width="1em"/><text word-spacing=".5EX">a</text></g><g><text letter-spacing="2ch">a</text><text letter-spacing="2ch">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect stroke-width="1em"/><rect stroke-width="1em"/><text word-spacing=".5EX">a</text></g><g><text letter-spacing="2ch">a</text><text letter-spacing="2ch">b</text></g></svg>',
        ];

        yield 'Keeps inherit values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="inherit"/><rect fill="inherit"/></g><g><rect fill=" INHERIT "/><rect fill=" INHERIT "/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="inherit"/><rect fill="inherit"/></g><g><rect fill=" INHERIT "/><rect fill=" INHERIT "/></g></svg>',
        ];

        yield 'Keeps empty values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill=""/><rect fill=""/></g><g><rect fill=" "/><rect fill=" "/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill=""/><rect fill=""/></g><g><rect fill=" "/><rect fill=" "/></g></svg>',
        ];

        yield 'Keeps values that differ in case or whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill=" red"/></g><g><rect fill="red"/><rect fill="RED"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill=" red"/></g><g><rect fill="red"/><rect fill="RED"/></g></svg>',
        ];

        yield 'Moves identical padded and uppercase values unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill=" RED "/><rect fill=" RED "/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill=" RED "><rect/><rect/></g></svg>',
        ];

        yield 'Moves currentColor when no child sets a color' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g color="red"><rect fill="currentColor"/><rect fill="currentColor" style="stop-color:blue"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g color="red" fill="currentColor"><rect/><rect style="stop-color:blue"/></g></svg>',
        ];

        yield 'Keeps currentColor when a later child sets a color attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="CurrentColor"/><rect fill="CurrentColor" color="blue"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="CurrentColor"/><rect fill="CurrentColor" color="blue"/></g></svg>',
        ];

        yield 'Keeps currentColor when a child sets a color in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="currentColor" style="COLOR: blue"/><rect fill="currentColor"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="currentColor" style="COLOR: blue"/><rect fill="currentColor"/></g></svg>',
        ];

        yield 'Moves other values when a child sets a color' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red" color="blue"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect color="blue"/><rect/></g></svg>',
        ];

        yield 'Keeps everything when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{fill:blue}</style><g><rect fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{fill:blue}</style><g><rect fill="red"/><rect fill="red"/></g></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red"/></g></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red"/></g></svg>',
        ];

        yield 'Moves attributes when other processing instructions are present' => [
            '<?other data?><svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect/><rect/></g></svg>',
        ];

        yield 'Keeps attributes that a later child declares in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red" style="Fill: blue"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red" style="Fill: blue"/></g></svg>',
        ];

        yield 'Keeps attributes that a child resets through all' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red" style="all: initial"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red" style="all: initial"/><rect fill="red"/></g></svg>',
        ];

        yield 'Keeps attributes that a child declares through a shorthand' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><text font-size="12" style="font: 10px serif">a</text><text font-size="12">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><text font-size="12" style="font: 10px serif">a</text><text font-size="12">b</text></g></svg>',
        ];

        yield 'Moves attributes that a child style does not declare' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red" style="opacity:.5"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="opacity:.5"/><rect/></g></svg>',
        ];

        yield 'Keeps attributes that the group declares in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:blue"><rect fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:blue"><rect fill="red"/><rect fill="red"/></g></svg>',
        ];

        yield 'Keeps attributes that the group declares through a shorthand' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="font:10px serif"><text font-family="sans-serif">a</text><text font-family="sans-serif">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="font:10px serif"><text font-family="sans-serif">a</text><text font-family="sans-serif">b</text></g></svg>',
        ];

        yield 'Keeps hyphenated attributes that the group declares in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke-width:5"><rect stroke-width="2"/><rect stroke-width="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke-width:5"><rect stroke-width="2"/><rect stroke-width="2"/></g></svg>',
        ];

        yield 'Moves attributes that the group style does not declare' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="opacity:.5"><rect fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="opacity:.5" fill="red"><rect/><rect/></g></svg>',
        ];

        yield 'Keeps animated attributes and moves the others' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red" stroke="blue"/><rect fill="red" stroke="blue"/></g><animate href="#x" attributeName="fill" to="blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="blue"><rect fill="red"/><rect fill="red"/></g><animate href="#x" attributeName="fill" to="blue"/></svg>',
        ];

        yield 'Keeps attributes animated by a set with padded attribute name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"><set attributeName=" fill " to="blue"/></rect><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"><set attributeName=" fill " to="blue"/></rect><rect fill="red"/></g></svg>',
        ];

        yield 'Keeps groups with animation children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><animate attributeName="x" fill="freeze"/><animate attributeName="y" fill="freeze"/></g><g><animateMotion fill="freeze" path="M0 0"/><animateMotion fill="freeze" path="M0 0"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><animate attributeName="x" fill="freeze"/><animate attributeName="y" fill="freeze"/></g><g><animateMotion fill="freeze" path="M0 0"/><animateMotion fill="freeze" path="M0 0"/></g></svg>',
        ];

        yield 'Keeps groups with a referenced child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect id="a" fill="red"/><rect fill="red"/></g><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect id="a" fill="red"/><rect fill="red"/></g><use href="#a"/></svg>',
        ];

        yield 'Moves attributes from unreferenced children with ids' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect id="a" fill="red"/><rect id="b" fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect id="a"/><rect id="b"/></g></svg>',
        ];

        yield 'Moves attributes into a referenced group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><rect fill="red"/><rect fill="red"/></g><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a" fill="red"><rect/><rect/></g><use href="#a"/></svg>',
        ];

        yield 'Moves attributes from use children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="s" d="M0 0h1"/></defs><g><use href="#s" fill="red"/><use href="#s" fill="red" x="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="s" d="M0 0h1"/></defs><g fill="red"><use href="#s"/><use href="#s" x="2"/></g></svg>',
        ];

        yield 'Keeps groups with a foreign child' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://example.com/x"><g><rect fill="red"/><x:a fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://example.com/x"><g><rect fill="red"/><x:a fill="red"/><rect fill="red"/></g></svg>',
        ];

        yield 'Keeps groups inside foreign objects' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><g><rect fill="red"/><rect fill="red"/></g></svg></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><g><rect fill="red"/><rect fill="red"/></g></svg></foreignObject></svg>',
        ];

        yield 'Ignores descriptive children and text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title>t</title>x<rect fill="red"/><desc>d</desc><rect fill="red"/><metadata/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><title>t</title>x<rect/><desc>d</desc><rect/><metadata/></g></svg>',
        ];

        yield 'Does not count descriptive children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title fill="red">t</title><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title fill="red">t</title><rect fill="red"/></g></svg>',
        ];

        yield 'Keeps attributes that are not inherited' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect opacity=".5" transform="scale(2)" display="inline" clip-path="url(#c)" color="red"/><rect opacity=".5" transform="scale(2)" display="inline" clip-path="url(#c)" color="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect opacity=".5" transform="scale(2)" display="inline" clip-path="url(#c)" color="red"/><rect opacity=".5" transform="scale(2)" display="inline" clip-path="url(#c)" color="red"/></g></svg>',
        ];

        yield 'Moves attributes through nested groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><rect fill="red"/><rect fill="red"/></g><g><rect fill="red"/><rect fill="red"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><g><rect/><rect/></g><g><rect/><rect/></g></g></svg>',
        ];

        yield 'Moves attributes through deeply nested groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><rect stroke="red"/><rect stroke="red"/></g><g><rect stroke="red"/><circle stroke="red"/></g></g><path stroke="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red"><g><g><rect/><rect/></g><g><rect/><circle/></g></g><path/></g></svg>',
        ];

        yield 'Skips a group and processes a later one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="blue"/></g><g><rect fill="red"/><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="blue"/></g><g fill="red"><rect/><rect/></g></svg>',
        ];

        yield 'Moves attributes in prefixed SVG documents' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g><svg:rect fill="red"/><svg:rect fill="red"/></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g fill="red"><svg:rect/><svg:rect/></svg:g></svg:svg>',
        ];

        yield 'Keeps documents without groups untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps empty groups untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"/></svg>',
        ];

        yield 'Keeps groups with only text and comments untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g>a<!--c-->b</g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g>a<!--c-->b</g></svg>',
        ];

        yield 'Moves attributes past comments between children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><!--a--><rect fill="red"/><!--b--><rect fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><!--a--><rect/><!--b--><rect/></g></svg>',
        ];

        yield 'Moves attributes from mixed children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="s" d="M0 0h1"/></defs><g><rect fill="red" stroke-linecap="round"/><text fill="red" stroke-linecap="round">a<tspan fill="blue">b</tspan></text><use href="#s" fill="red" stroke-linecap="round"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="s" d="M0 0h1"/></defs><g fill="red" stroke-linecap="round"><rect/><text>a<tspan fill="blue">b</tspan></text><use href="#s"/></g></svg>',
        ];

        yield 'Moves attributes through levels until a level differs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><rect fill="red"/><rect fill="red"/></g><rect fill="red"/></g><g><g><rect fill="blue"/><rect fill="blue"/></g><rect fill="blue"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g fill="red"><g><rect/><rect/></g><rect/></g><g fill="blue"><g><rect/><rect/></g><rect/></g></g></svg>',
        ];

        yield 'Keeps attributes when an animateColor targets them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red"><animateColor attributeName="fill" to="blue"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect fill="red"/><rect fill="red"><animateColor attributeName="fill" to="blue"/></rect></g></svg>',
        ];

        yield 'Keeps attributes when an element referenced through xlink is a child' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><rect fill="red"/><rect id="a" fill="red"/></g><use xlink:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><rect fill="red"/><rect id="a" fill="red"/></g><use xlink:href="#a"/></svg>',
        ];

        yield 'Leaves group transforms alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect transform="scale(2)" fill="red"/><rect transform="scale(2)" fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect transform="scale(2)"/><rect transform="scale(2)"/></g></svg>',
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
        $svgOptimizer->addPass(new MoveCommonAttributesToGroup());

        return $svgOptimizer->optimize()->getContent();
    }
}
