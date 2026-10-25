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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\DangerousUrlTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\UnsafeAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
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
 *
 * @no-named-arguments
 */
#[CoversClass(RemoveScripts::class)]
#[CoversClass(Attribute::class)]
#[CoversClass(Element::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(CanonicalNameTrait::class)]
#[CoversTrait(DangerousUrlTrait::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(ElementNameTrait::class)]
#[CoversTrait(ElementRemovalTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[CoversTrait(UnsafeAttributeTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveScriptsTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expectedSvg): void
    {
        $pipeline = new Pipeline([new RemoveScripts()]);

        self::assertSame($expectedSvg, $pipeline->run($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Removes script elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes script elements in any case and with a prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><SCRIPT>a</SCRIPT><s:script>b</s:script><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes handler and listener elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><handler type="application/ecmascript">alert(1)</handler><ev:listener event="click"/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><rect/></svg>',
        ];

        yield 'Removes event handler attributes in any case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect onclick="a()" ONLOAD="b()" OnMouseOver="c()" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>',
        ];

        yield 'Removes event handler attributes with a prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:onclick="a()" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect width="1"/></svg>',
        ];

        yield 'Removes event handler attributes on the root element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes the name attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect name="body" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>',
        ];

        yield 'Removes an animation that sets an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="onclick" to="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an animation that sets an event handler written in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributename="ONCLICK" to="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an animation that sets a prefixed event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="x:onclick" to="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an animation that sets an event handler after a multibyte prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="é:onclick" to="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an animation that sets an escaped event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="o\6e click" to="alert(1)"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an animation that sets a link' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><animate attributeName="href" values="#a"/><set attributeName="xlink:href" to="#b"/><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><text>x</text></a></svg>',
        ];

        yield 'Removes an animation that sets the name attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="name" to="x"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a link element that animates its own link with its children' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a attributeName="href" href="#x"><text>x</text></a><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps an animation of a presentation attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="fill" to="red"/><animate attributeName="x" values="0;1"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><set attributeName="fill" to="red"/><animate attributeName="x" values="0;1"/></rect></svg>',
        ];

        yield 'Keeps an attributeName attribute on an element that is not an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect attributeName="onclick"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect attributeName="onclick"/></svg>',
        ];

        yield 'Keeps an attributeName attribute in a namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect><set x:attributeName="onclick" to="red"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect><set x:attributeName="onclick" to="red"/></rect></svg>',
        ];

        yield 'Keeps attributes that do not start with on' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><feComposite operator="in"/><text font-variation-settings="normal">x</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><feComposite operator="in"/><text font-variation-settings="normal">x</text></svg>',
        ];

        yield 'Removes script that comes from an entity' => [
            '<!DOCTYPE svg [<!ENTITY s "<script>alert(1)</script>">]><svg xmlns="http://www.w3.org/2000/svg">&s;<rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Leaves unsafe URLs, foreign content, style sheets and markup to the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><?pi x?><!-- <b> --><a href="javascript:alert(1)"><text>x</text></a><foreignObject/><style>@import url(a.css);</style><rect xmlns:ev="http://www.w3.org/2001/xml-events" ev:event="click"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><?pi x?><!-- <b> --><a href="javascript:alert(1)"><text>x</text></a><foreignObject/><style>@import url(a.css);</style><rect xmlns:ev="http://www.w3.org/2001/xml-events" ev:event="click"/></svg>',
        ];
    }
}
