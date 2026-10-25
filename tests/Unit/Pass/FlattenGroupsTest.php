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
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
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
#[CoversClass(FlattenGroups::class)]
#[CoversClass(NamespaceUri::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(DeclarationTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdReferenceTrait::class)]
#[CoversTrait(InheritanceTrait::class)]
#[CoversTrait(NamedColorTrait::class)]
#[CoversTrait(NumberListTrait::class)]
#[CoversTrait(NumberLiteralTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[CoversTrait(TransformListTrait::class)]
#[UsesClass(Attribute::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class FlattenGroupsTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new FlattenGroups()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);

        $repeated = new Pipeline([new FlattenGroups()]);
        self::assertSame($actual, $repeated->run($actual));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Leaves the document untouched with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><g><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><g><rect width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps a group whose child unsets an inherited property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="fill:unset" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="fill:unset" width="5" height="5"/></g></svg>',
        ];
        yield 'Leaves out a group attribute that the style of a child sets to the same value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="fill:red" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red" width="5" height="5"/></svg>',
        ];

        yield 'Leaves out a group attribute that the style of a child sets to the same value in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="FILL: red" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="FILL: red" width="5" height="5"/></svg>',
        ];

        yield 'Sets a group attribute that the style of a child sets to another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="fill:blue" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:blue" width="5" height="5" fill="red"/></svg>',
        ];

        yield 'Sets a group attribute that the style of a child does not set' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="stroke:red" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="stroke:red" width="5" height="5" fill="red"/></svg>',
        ];

        yield 'Sets a group attribute when the style of a child cannot be split safely' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="fill:red;content:\'x" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;content:\'x" width="5" height="5" fill="red"/></svg>',
        ];

        yield 'Keeps a group whose child uses a custom property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="fill:var(--a)" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect style="fill:var(--a)" width="5" height="5"/></g></svg>',
        ];

        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps groups reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><g fill="red"><rect width="5" height="5"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><g fill="red"><rect width="5" height="5"/></g></g></svg>',
        ];

        yield 'keeps a group with a mixed-case attribute name that is not inherited' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g Fill="red"><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g Fill="red"><rect width="5" height="5"/></g></svg>',
        ];

        yield 'moves a padded inherited value unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="&#9; red&#10;"><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" fill="&#9; red&#10;"/></svg>',
        ];

        yield 'flattens a group whose child has entity and character data text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><text>&lt;&amp;<![CDATA[ a < b ]]></text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text fill="red">&lt;&amp; a &lt; b </text></svg>',
        ];

        yield 'flattens a group whose child has a non-breaking space in its text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><text>a&#xA0;b</text></g></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text fill=\"red\">a\u{A0}b</text></svg>",
        ];

        yield 'keeps a group inside a clip path, whose children are ignored' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"><g><rect width="5" height="5"/></g></clipPath><rect clip-path="url(#c)" width="10" height="10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"><g><rect width="5" height="5"/></g></clipPath><rect clip-path="url(#c)" width="10" height="10"/></svg>',
        ];

        yield 'flattens a group nested in a group inside a clip path' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"><g><g fill="red"><rect width="5" height="5"/></g></g></clipPath></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"><g><rect width="5" height="5" fill="red"/></g></clipPath></svg>',
        ];

        yield 'keeps groups when an external style sheet is linked' => [
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><g><rect width="5" height="5"/></g></svg>',
            '<?xml-stylesheet href="style.css"?><svg xmlns="http://www.w3.org/2000/svg"><g><rect width="5" height="5"/></g></svg>',
        ];

        yield 'flattens a prefixed svg group' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g fill="red"><svg:rect width="5" height="5"/></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="5" height="5" fill="red"/></svg:svg>',
        ];

        yield 'leaves a group in another namespace alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:g xmlns:x="urn:x"><rect width="5" height="5"/></x:g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:g xmlns:x="urn:x"><rect width="5" height="5"/></x:g></svg>',
        ];

        yield 'keeps a group whose child uses an uppercase inherit in its style' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect width="5" height="5" style="fill:INHERIT"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect width="5" height="5" style="fill:INHERIT"/></g></svg>',
        ];

        yield 'keeps a group whose child has an id referenced through xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g transform="translate(5)"><rect xml:id="r" width="5" height="5"/></g><use xlink:href="#r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g transform="translate(5)"><rect xml:id="r" width="5" height="5"/></g><use xlink:href="#r"/></svg>',
        ];

        yield 'flattens deeply nested groups and merges their transforms in order' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5)"><g transform="scale(2)"><g transform="rotate(45)"><rect width="5" height="5"/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" transform="translate(5) scale(2) rotate(45)"/></svg>',
        ];

        yield 'keeps the visibility of a child over that of the group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g visibility="hidden"><rect visibility="visible" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect visibility="visible" width="5" height="5"/></svg>',
        ];

        yield 'keeps a group when repeating its transform on each child makes the output larger' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5)"><a href="#x"><rect width="5" height="5"/></a><foreignObject width="5" height="5"/><text>t</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5)"><a href="#x"><rect width="5" height="5"/></a><foreignObject width="5" height="5"/><text>t</text></g></svg>',
        ];

        yield 'keeps a group whose child sets a relative font size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="20"><text font-size="larger">t</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="20"><text font-size="larger">t</text></g></svg>',
        ];

        yield 'keeps a group with a font relative letter spacing when the child sets its font size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g letter-spacing="1em"><text font-size="40">t</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g letter-spacing="1em"><text font-size="40">t</text></g></svg>',
        ];

        yield 'keeps a group whose child is animated through a set with a padded attribute name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5)"><rect width="5" height="5"><set attributeName=" transform " to="scale(2)"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5)"><rect width="5" height="5"><set attributeName=" transform " to="scale(2)"/></rect></g></svg>',
        ];

        yield 'keeps the multibyte text and entities of the children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><text>&#xE6;&#xF8;&#xE5; &amp; 日本</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text fill="red">æøå &amp; 日本</text></svg>',
        ];

        yield 'flattens a group whose first child is a comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><!-- c --><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- c --><rect width="5" height="5" fill="red"/></svg>',
        ];

        yield 'Keeps a transformed group whose child is referenced by a use element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 0)"><rect id="r" width="5" height="5"/></g><use href="#r" y="20"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 0)"><rect id="r" width="5" height="5"/></g><use href="#r" y="20"/></svg>',
        ];

        yield 'Keeps a filled group whose descendant is referenced by a use element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g fill="red"><g><rect xml:id="r" width="5" height="5"/></g></g><use xlink:href="#r" y="20"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g fill="red"><rect xml:id="r" width="5" height="5"/></g><use xlink:href="#r" y="20"/></svg>',
        ];

        yield 'Keeps a transformed group whose path is referenced by a textPath' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><path id="p" d="M0 10h50"/></g><text><textPath href="#p">a</textPath></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><path id="p" d="M0 10h50"/></g><text><textPath href="#p">a</textPath></text></svg>',
        ];

        yield 'Keeps a group whose child is referenced by a SMIL timing value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect id="r" width="5" height="5"/></g><use href="#q"/><set attributeName="x" to="1" begin="r.click"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect id="r" width="5" height="5"/></g><use href="#q"/><set attributeName="x" to="1" begin="r.click"/></svg>',
        ];

        yield 'Flattens a group without attributes whose child is referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect id="r" width="5" height="5"/></g><use href="#r" y="20"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" width="5" height="5"/><use href="#r" y="20"/></svg>',
        ];

        yield 'Keeps a group whose descendant animates by an inherited value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect width="5" height="5"><animate attributeName="fill" by="inherit"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect width="5" height="5"><animate attributeName="fill" by="inherit"/></rect></g></svg>',
        ];

        yield 'Keeps a group with a set child, which animates the group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="fill" to="red"/><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="fill" to="red"/><rect width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps every transformed group whose child is a nested svg' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><svg width="5" height="5"/></g><g transform="scale(3)"><svg width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><svg width="5" height="5"/></g><g transform="scale(3)"><svg width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps a group whose descendant animates an inherited property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect width="5" height="5"><set attributeName="fill" to="inherit"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect width="5" height="5"><set attributeName="fill" to="inherit"/></rect></g></svg>',
        ];

        yield 'Keeps a group whose grandchild animates to an inherited value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><text>a<tspan>b<animate attributeName="fill" values="blue;Inherit" dur="1s"/></tspan></text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><text>a<tspan>b<animate attributeName="fill" values="blue;Inherit" dur="1s"/></tspan></text></g></svg>',
        ];

        yield 'Keeps a group with a title' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title>Box</title><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><title>Box</title><rect width="5" height="5"/></g></svg>',
        ];

        yield 'Keeps a group with a desc' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><desc>Box</desc><rect width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><desc>Box</desc><rect width="5" height="5"/></g></svg>',
        ];

        yield 'Does not apply the group transform to a clipPath child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g transform="translate(10)"><clipPath id="c"><rect width="5" height="5"/></clipPath><rect clip-path="url(#c)" width="5" height="5"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath id="c"><rect width="5" height="5"/></clipPath><rect clip-path="url(#c)" width="5" height="5" transform="translate(10)"/></svg>',
        ];

        yield 'Keeps group with a font-relative value when the child sets its font size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g stroke-width="1em"><path d="M0 0h1" font-size="10"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-width="1em"><path d="M0 0h1" font-size="10"/></g></svg>',
        ];

        yield 'Keeps group with a font-relative value when the child style sets its font size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g stroke-width="1em"><path d="M0 0h1" style="font-size:10px"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-width="1em"><path d="M0 0h1" style="font-size:10px"/></g></svg>',
        ];

        yield 'Flattens a group with an absolute value when the child sets its font size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g stroke-width="2"><path d="M0 0h1" font-size="10"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" font-size="10" stroke-width="2"/></svg>',
        ];

        yield 'Flattens Groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML,
        ];

        yield 'No Groups to Flatten' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30"/>
                    <circle cx="50" cy="50" r="20"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML,
        ];

        yield 'Nested Groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <g>
                            <rect x="10" y="10" width="30" height="30"/>
                        </g>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML,
        ];

        yield 'Empty Group' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g></g>
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Group with Attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g fill="red">
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g fill="red"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Group with Mixed Content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g fill="blue">
                        <rect x="10" y="10" width="30" height="30"/>
                        <text x="20" y="20">Hello</text>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g fill="blue"><rect x="10" y="10" width="30" height="30"/><text x="20" y="20">Hello</text></g></svg>
                XML,
        ];

        yield 'Multiple Nested Groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <g>
                            <circle cx="30" cy="30" r="20"/>
                        </g>
                        <g>
                            <rect x="50" y="50" width="20" height="20"/>
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="30" cy="30" r="20"/><rect x="50" y="50" width="20" height="20"/></svg>
                XML,
        ];

        yield 'Deeply Nested Groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <g>
                            <g>
                                <g>
                                    <rect x="10" y="10" width="30" height="30"/>
                                </g>
                            </g>
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Group with Multiple Attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g stroke="black" stroke-width="2" fill="green">
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g stroke="black" stroke-width="2" fill="green"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Attributes Inherited from Parent Group' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g stroke="black">
                        <g stroke-width="2">
                            <rect x="10" y="10" width="30" height="30"/>
                        </g>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g stroke="black"><g stroke-width="2"><rect x="10" y="10" width="30" height="30"/></g><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Groups with Mixed Attributes on Children' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g fill="blue">
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20" fill="red"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" fill="blue"/><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Large SVG Document' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="10000" height="10000">
                    <g>
                        <rect x="100" y="100" width="3000" height="3000"/>
                        <circle cx="5000" cy="5000" r="2000"/>
                    </g>
                    <g>
                        <rect x="4000" y="4000" width="3000" height="3000"/>
                        <circle cx="8000" cy="8000" r="2000"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="10000" height="10000"><rect x="100" y="100" width="3000" height="3000"/><circle cx="5000" cy="5000" r="2000"/><rect x="4000" y="4000" width="3000" height="3000"/><circle cx="8000" cy="8000" r="2000"/></svg>
                XML,
        ];

        yield 'Group with Transformations' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10, 10)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                    <g transform="translate(20, 20)">
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" transform="translate(10, 10)"/><circle cx="50" cy="50" r="20" transform="translate(20, 20)"/></svg>
                XML,
        ];

        yield 'Combine Transformations' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10, 10)">
                        <g transform="translate(20, 20)">
                            <rect x="10" y="10" width="30" height="30"/>
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" transform="translate(10, 10) translate(20, 20)"/></svg>
                XML,
        ];

        yield 'Empty SVG with No Groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Nested groups with clip-path are not flattened' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="750" height="600">
                    <defs>
                        <clipPath id="d"><path d="M0,0H100V200H0Z" fill="none"/></clipPath>
                        <clipPath id="e"><rect x="-16" y="-28" width="782" height="657" fill="none"/></clipPath>
                        <clipPath id="f"><rect x="-21" y="-33" width="792" height="667" fill="none"/></clipPath>
                    </defs>
                    <g>
                        <g clip-path="url(#d)">
                            <g clip-path="url(#e)">
                                <g clip-path="url(#f)">
                                    <rect x="0" y="0" width="750" height="600"/>
                                </g>
                            </g>
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="750" height="600"><defs><clipPath id="d"><path d="M0,0H100V200H0Z" fill="none"/></clipPath><clipPath id="e"><rect x="-16" y="-28" width="782" height="657" fill="none"/></clipPath><clipPath id="f"><rect x="-21" y="-33" width="792" height="667" fill="none"/></clipPath></defs><g clip-path="url(#d)"><g clip-path="url(#e)"><g clip-path="url(#f)"><rect x="0" y="0" width="750" height="600"/></g></g></g></svg>
                XML,
        ];

        yield 'Nested groups with mask are not flattened' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <defs>
                        <mask id="m"><rect width="100" height="50" fill="white"/></mask>
                        <mask id="n"><rect width="80" height="40" fill="white"/></mask>
                    </defs>
                    <g>
                        <g mask="url(#m)">
                            <g mask="url(#n)">
                                <rect x="0" y="0" width="100" height="100"/>
                            </g>
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><defs><mask id="m"><rect width="100" height="50" fill="white"/></mask><mask id="n"><rect width="80" height="40" fill="white"/></mask></defs><g mask="url(#m)"><g mask="url(#n)"><rect x="0" y="0" width="100" height="100"/></g></g></svg>
                XML,
        ];

        yield 'Flattens a plain group following a skipped clip-path group' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#d)">
                        <rect width="10" height="10"/>
                    </g>
                    <g>
                        <circle r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#d)"><rect width="10" height="10"/></g><circle r="5"/></svg>
                XML,
        ];

        yield 'Flattens a plain group following a skipped mask group' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g mask="url(#m)">
                        <rect width="10" height="10"/>
                    </g>
                    <g>
                        <circle r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g mask="url(#m)"><rect width="10" height="10"/></g><circle r="5"/></svg>
                XML,
        ];

        yield 'Group with empty attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g stroke="" stroke-width="2" fill="">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" stroke="" stroke-width="2" fill=""/></svg>
                XML,
        ];

        yield 'Groups with defs elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs/></svg>',
        ];

        yield 'Keeps referenceable group with id inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><g id="shared"><rect width="10" height="10"/></g></defs><use xlink:href="#shared"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><g id="shared"><rect width="10" height="10"/></g></defs><use xlink:href="#shared"/></svg>',
        ];

        yield 'Keeps referenceable group with id outside defs instead of duplicating it onto children' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="g1" fill="blue"><rect width="10" height="10"/><circle cx="5" cy="5" r="5"/></g><use xlink:href="#g1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="g1" fill="blue"><rect width="10" height="10"/><circle cx="5" cy="5" r="5"/></g><use xlink:href="#g1"/></svg>',
        ];

        yield 'Flattens a plain group following a skipped group inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><g id="shared"><rect width="10" height="10"/></g></defs><use xlink:href="#shared"/><g><circle r="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><g id="shared"><rect width="10" height="10"/></g></defs><use xlink:href="#shared"/><circle r="5"/></svg>',
        ];

        yield 'Keeps group opacity instead of applying it to each overlapping child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5"><g><g><rect width="10" height="10"/></g></g><g><g><rect x="5" width="10" height="10"/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5"><rect width="10" height="10"/><rect x="5" width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps group opacity when the child has its own opacity' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g opacity=".5">    <rect opacity=".5" width="10" height="10"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5"><rect opacity=".5" width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps group with filter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g filter="url(#f)"><g><g><rect width="10" height="10"/></g></g><g><g><rect x="5" width="10" height="10"/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g filter="url(#f)"><rect width="10" height="10"/><rect x="5" width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps group with display none when a child sets display' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g display="none">    <rect display="inline" width="10" height="10"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g display="none"><rect display="inline" width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps group class when the child has its own class' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g class="a">    <rect class="b" width="10" height="10"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g class="a"><rect class="b" width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps group style when the child has its own style' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g style="fill:red">    <rect style="stroke:blue" width="10" height="10"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:red"><rect style="stroke:blue" width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps group that is a direct child of switch' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <switch>    <g>    <rect width="10" height="10"/>    <circle r="5"/>    </g>    </switch>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g><rect width="10" height="10"/><circle r="5"/></g></switch></svg>',
        ];

        yield 'Keeps group whose children include an animation element targeting it' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g>    <animate attributeName="opacity" from="1" to="0" dur="1s"/>    <rect width="10" height="10"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><animate attributeName="opacity" from="1" to="0" dur="1s"/><rect width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps groups when a style element may target them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{fill:red}</style>    <g>    <rect width="10" height="10"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{fill:red}</style><g><rect width="10" height="10"/></g></svg>',
        ];

        yield 'Composes identical group and child transforms' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g transform="scale(2)">    <path transform="scale(2)" d="M0 0h1"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path transform="scale(2) scale(2)" d="M0 0h1"/></svg>',
        ];

        yield 'Applies group transform before the child transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g transform="rotate(45)">    <path transform="translate(10)" d="M0 0h1"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path transform="rotate(45) translate(10)" d="M0 0h1"/></svg>',
        ];

        yield 'Keeps a group whose transform the browser rejects' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="foo"><rect transform="scale(2)"/></g><g transform="translate(1.)"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="foo"><rect transform="scale(2)"/></g><g transform="translate(1.)"><rect/></g></svg>',
        ];

        yield 'Keeps a transformed group whose later child has a transform the browser rejects' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect transform="scale(3)"/><rect transform="translate(1.e2)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect transform="scale(3)"/><rect transform="translate(1.e2)"/></g></svg>',
        ];

        yield 'Flattens a transformed group around a definition with a transform the browser rejects' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><clipPath transform="foo"/><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><clipPath transform="foo"/><rect transform="scale(2)"/></svg>',
        ];

        yield 'Flattens a group with a whitespace-only transform around a child with a rejected transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform=" " fill="red"><rect transform="foo"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect transform="foo" fill="red"/></svg>',
        ];

        yield 'Keeps a group whose later child overrides a group value with a value the browser may reject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect fill="blue"/><rect fill="foo"/></g><g color="red"><rect color="currentColor"/></g><g stroke-width="2"><rect stroke-width="-1"/></g><g fill-opacity=".5"><rect fill-opacity="1px"/></g><g stroke="red"><rect stroke="url(#a) x"/></g><g fill-rule="evenodd"><rect fill-rule="EVENODD"/></g><g font-family="a"><text font-family="b">t</text></g><g fill="red"><rect fill="#12345"/></g><g fill="red"><rect fill=" blue"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect fill="blue"/><rect fill="foo"/></g><g color="red"><rect color="currentColor"/></g><g stroke-width="2"><rect stroke-width="-1"/></g><g fill-opacity=".5"><rect fill-opacity="1px"/></g><g stroke="red"><rect stroke="url(#a) x"/></g><g fill-rule="evenodd"><rect fill-rule="EVENODD"/></g><g font-family="a"><text font-family="b">t</text></g><g fill="red"><rect fill="#12345"/></g><g fill="red"><rect fill=" blue"/></g></svg>',
        ];

        yield 'Flattens groups whose child overrides the group value with a value the browser accepts' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect fill="none"/></g><g fill="red"><rect fill="#ABC"/></g><g fill="red"><rect fill="#abcd"/></g><g fill="red"><rect fill="#00ff00"/></g><g fill="red"><rect fill="#00ff00ff"/></g><g fill="red"><rect fill="Blue"/></g><g fill="red"><rect fill="CurrentColor"/></g><g fill="red"><rect fill="url(#a)"/></g><g stroke="red"><rect stroke="none"/></g><g stroke="red"><rect stroke="currentColor"/></g><g stroke="red"><rect stroke="url(#a-b.c_1)"/></g><g color="red"><rect color="BLUE"/></g><g color="red"><rect color="#0f0"/></g><g stroke-width="2"><rect stroke-width="+1.5e1"/></g><g stroke-width="2"><rect stroke-width="0"/></g><g stroke-width="2"><rect stroke-width=".5"/></g><g fill-opacity=".5"><rect fill-opacity="0"/></g><g fill-opacity=".5"><rect fill-opacity="1"/></g><g stroke-opacity=".5"><rect stroke-opacity="0"/></g><g fill-rule="evenodd"><rect fill-rule="nonzero"/></g><g clip-rule="nonzero"><rect clip-rule="evenodd"/></g><g stroke-linecap="butt"><rect stroke-linecap="round"/></g><g stroke-linecap="butt"><rect stroke-linecap="square"/></g><g stroke-linejoin="miter"><rect stroke-linejoin="bevel"/></g><g stroke-linejoin="miter"><rect stroke-linejoin="round"/></g><g text-anchor="start"><rect text-anchor="middle"/></g><g text-anchor="start"><rect text-anchor="end"/></g><g visibility="hidden"><rect visibility="visible"/></g><g visibility="hidden"><rect visibility="collapse"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="none"/><rect fill="#ABC"/><rect fill="#abcd"/><rect fill="#00ff00"/><rect fill="#00ff00ff"/><rect fill="Blue"/><rect fill="CurrentColor"/><rect fill="url(#a)"/><rect stroke="none"/><rect stroke="currentColor"/><rect stroke="url(#a-b.c_1)"/><rect color="BLUE"/><rect color="#0f0"/><rect stroke-width="+1.5e1"/><rect stroke-width="0"/><rect stroke-width=".5"/><rect fill-opacity="0"/><rect fill-opacity="1"/><rect stroke-opacity="0"/><rect fill-rule="nonzero"/><rect clip-rule="evenodd"/><rect stroke-linecap="round"/><rect stroke-linecap="square"/><rect stroke-linejoin="bevel"/><rect stroke-linejoin="round"/><rect text-anchor="middle"/><rect text-anchor="end"/><rect visibility="visible"/><rect visibility="collapse"/></svg>',
        ];

        yield 'Flattens a group whose child repeats a group value the browser may reject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="foo"><rect fill="foo"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="foo"/></svg>',
        ];

        yield 'Ignores an empty group transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform=""><path transform="scale(2)" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path transform="scale(2)" d="M0 0h1"/></svg>',
        ];

        yield 'Pushes inherited presentation attributes to a child of a flattened group' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g fill="red" stroke="blue">    <path fill="green" d="M0 0h1"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="green" d="M0 0h1" stroke="blue"/></svg>',
        ];

        yield 'Replaces an explicit inherit value with the group value' => [
            '<svg xmlns="http://www.w3.org/2000/svg">    <g fill="red">    <path fill="inherit" d="M0 0h1"/>    </g>    </svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="red" d="M0 0h1"/></svg>',
        ];

        yield 'Keeps group when a child font-size may be relative to the group font-size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="20"><g><text font-size="2em">a</text></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="20"><text font-size="2em">a</text></g></svg>',
        ];

        yield 'Keeps group when a child style inherits from it' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><g><path style="fill:inherit" d="M0 0h1"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><path style="fill:inherit" d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps transformed group around a nested svg element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><g><svg width="10" height="10"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><svg width="10" height="10"/></g></svg>',
        ];

        yield 'Keeps transformed group around a child with an animateTransform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect width="20" height="20"><animateTransform attributeName="transform" type="rotate" values="0;90" dur="1s"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect width="20" height="20"><animateTransform attributeName="transform" type="rotate" values="0;90" dur="1s"/></rect></g></svg>',
        ];

        yield 'Keeps transformed group around a child with an animateMotion' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect width="20" height="20"><animateMotion path="M0 0h10" dur="1s"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect width="20" height="20"><animateMotion path="M0 0h10" dur="1s"/></rect></g></svg>',
        ];

        yield 'Keeps transformed group around a child whose transform is set by an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect width="20" height="20"><set attributeName=" transform " to="scale(2)"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect width="20" height="20"><set attributeName=" transform " to="scale(2)"/></rect></g></svg>',
        ];

        yield 'Keeps transformed group around a child targeted by an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect id="r" width="20" height="20"/></g><animateTransform href="#r" attributeName="transform" type="rotate" values="0;90" dur="1s"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect id="r" width="20" height="20"/></g><animateTransform href="#r" attributeName="transform" type="rotate" values="0;90" dur="1s"/></svg>',
        ];

        yield 'Keeps transformed group around a child targeted by an animation through xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect xml:id="r" width="20" height="20"/></g><animateMotion href="#r" path="M0 0h10" dur="1s"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect xml:id="r" width="20" height="20"/></g><animateMotion href="#r" path="M0 0h10" dur="1s"/></svg>',
        ];

        yield 'Keeps transformed group around a child with id 0 targeted by an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect id="0" width="20" height="20"/></g><animateMotion href="#0" path="M0 0h10" dur="1s"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect id="0" width="20" height="20"/></g><animateMotion href="#0" path="M0 0h10" dur="1s"/></svg>',
        ];

        yield 'Flattens transformed group around a child without an id when an animation has an empty target' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect width="20" height="20"/></g><animateMotion href="#" path="M0 0h10" dur="1s"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="20" height="20" transform="translate(50 50)"/><animateMotion href="#" path="M0 0h10" dur="1s"/></svg>',
        ];

        yield 'Pushes attributes that follow the transform to the children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)" fill="red"><path d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1" transform="scale(2)" fill="red"/></svg>',
        ];

        yield 'Flattens transformed group around a child with an id that no animation targets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect id="r" width="20" height="20"/></g><animateTransform href="#s" attributeName="transform" type="rotate" values="0;90" dur="1s"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" width="20" height="20" transform="translate(50 50)"/><animateTransform href="#s" attributeName="transform" type="rotate" values="0;90" dur="1s"/></svg>',
        ];

        yield 'Flattens transformed group around a child that animates another attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><rect width="20" height="20"><animate attributeName="fill" values="red;blue" dur="1s"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="20" height="20" transform="translate(50 50)"><animate attributeName="fill" values="red;blue" dur="1s"/></rect></svg>',
        ];

        yield 'Flattens transformed group around text that animates another attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(50 50)"><text>a<animate attributeName="fill" values="red;blue" dur="1s"/></text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text transform="translate(50 50)">a<animate attributeName="fill" values="red;blue" dur="1s"/></text></svg>',
        ];

        yield 'Flattens group without a transform around a child with an animateTransform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect width="20" height="20"><animateTransform attributeName="transform" type="rotate" values="0;90" dur="1s"/></rect></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="20" height="20" fill="red"><animateTransform attributeName="transform" type="rotate" values="0;90" dur="1s"/></rect></svg>',
        ];

        yield 'Keeps group with a font-size when the child sets its own font size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="20"><text font-size="2em">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="20"><text font-size="2em">a</text></g></svg>',
        ];

        yield 'Flattens group with a font-relative value when the child sets no font size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g letter-spacing="1em"><text>a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text letter-spacing="1em">a</text></svg>',
        ];

        yield 'Replaces an explicit inherit value with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><path fill=" inherit " d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="red" d="M0 0h1"/></svg>',
        ];

        yield 'Ignores a blank group transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform=" "><path d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1"/></svg>',
        ];

        yield 'Ignores a blank child transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><path transform=" " d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path transform="scale(2)" d="M0 0h1"/></svg>',
        ];

        yield 'Keeps transformed group when the child style sets a transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10 0)"><rect width="5" height="5" style="transform:rotate(45deg)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10 0)"><rect width="5" height="5" style="transform:rotate(45deg)"/></g></svg>',
        ];

        yield 'Keeps transformed group when the child style sets a prefixed transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10 0)"><rect width="5" height="5" style="fill:red; -webkit-transform : rotate(45deg)"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10 0)"><rect width="5" height="5" style="fill:red; -webkit-transform : rotate(45deg)"/></g></svg>',
        ];

        yield 'Keeps transformed group when the child style sets an individual transform property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10 0)"><rect width="5" height="5" style="rotate:45deg"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10 0)"><rect width="5" height="5" style="rotate:45deg"/></g></svg>',
        ];

        yield 'Keeps transformed group when the child has a transform-origin' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="5" height="5" transform="rotate(45)" transform-origin="50 50"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="5" height="5" transform="rotate(45)" transform-origin="50 50"/></g></svg>',
        ];

        yield 'Keeps transformed group when the child has a transform-box' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="5" height="5" transform-box="fill-box"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="5" height="5" transform-box="fill-box"/></g></svg>',
        ];

        yield 'Keeps transformed group when the child style sets a transform-origin' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="5" height="5" style="transform-origin:50px 50px"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="5" height="5" style="transform-origin:50px 50px"/></g></svg>',
        ];

        yield 'Flattens group without a transform when the child has a transform-origin' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="red"><rect width="5" height="5" transform-origin="50 50"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" transform-origin="50 50" fill="red"/></svg>',
        ];

        yield 'Flattens transformed group when the child style sets an unrelated property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"><rect width="5" height="5" style="stroke-transform:x"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" style="stroke-transform:x" transform="scale(2)"/></svg>',
        ];

        yield 'Keeps group with a relative font-weight when the child style sets its font-weight' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-weight="bolder"><text style="font-weight:bolder">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-weight="bolder"><text style="font-weight:bolder">a</text></g></svg>',
        ];

        yield 'Keeps group with a percentage font-size when the child style sets its font-size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="200%"><text style="font-size:50%">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="200%"><text style="font-size:50%">a</text></g></svg>',
        ];

        yield 'Keeps group with a larger font-size when the child style sets a smaller font-size' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="larger"><text style="font-size:smaller">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-size="larger"><text style="font-size:smaller">a</text></g></svg>',
        ];

        yield 'Keeps group with a font-stretch when the child style sets the font shorthand' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-stretch="wider"><text style="font:condensed 10px serif">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g font-stretch="wider"><text style="font:condensed 10px serif">a</text></g></svg>',
        ];

        yield 'Keeps group with a font-relative value when the child style sets the font shorthand' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-width="1em"><text style="font:40px serif">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-width="1em"><text style="font:40px serif">a</text></g></svg>',
        ];

        yield 'Flattens group with a font-relative value when the child style sets another font property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-width="1em"><text style="font-family:serif">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:serif" stroke-width="1em">a</text></svg>',
        ];

        yield 'Keeps group with a namespaced attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><g a:fill="red"><g><rect width="10" height="10"/></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><g a:fill="red"><rect width="10" height="10"/></g></svg>',
        ];
    }
}
