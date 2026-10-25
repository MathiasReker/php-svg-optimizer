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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(RemoveScripts::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgTag::class)]
final class RemoveScriptsTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expectedSvg): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveScripts());

        self::assertSame($expectedSvg, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
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

    #[Test]
    public function ruleIsNotRisky(): void
    {
        self::assertFalse(RemoveScripts::isRisky());
    }

    #[Test]
    public function ruleSkipsTheSizeCheck(): void
    {
        self::assertFalse(RemoveScripts::shouldCheckSize());
    }
}
