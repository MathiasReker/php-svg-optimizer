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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveForeignContent;
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
#[CoversClass(RemoveForeignContent::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgTag::class)]
final class RemoveForeignContentTest extends TestCase
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
        $svgOptimizer->addPass(new RemoveForeignContent());

        self::assertSame($expectedSvg, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes foreignObject, iframe, object, embed and link elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><p>x</p></foreignObject><iframe/><object/><embed/><link/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes elements in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" xmlns:h="http://www.w3.org/1999/xhtml"><x:a/><h:div>x</h:div><g xmlns="urn:y"><rect/></g><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" xmlns:h="http://www.w3.org/1999/xhtml"><rect/></svg>',
        ];

        yield 'Removes HTML breakout elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><p>x</p><div/><img/><span/><table/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a font element with a breakout attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><font color="red"/><font face="a"/><font size="1"/><font id="f"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><font id="f"/></svg>',
        ];

        yield 'Removes elements with a non-canonical name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignobject/><A href="#x"><text>x</text></A><lineargradient id="g"/><myWidget/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><myWidget/><rect/></svg>',
        ];

        yield 'Unwraps a link with a javascript: URL' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Unwraps conditionally dangerous elements with an external URL' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="https://evil.example/a.svg#a"/><image href="http://evil.example/a.png"/><pattern href="//evil.example/p"><rect/></pattern><tref xlink:href="javascript:x">t</tref></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/>t</svg>',
        ];

        yield 'Unwraps nested links with unsafe URLs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:1"><a href="javascript:2"><text>x</text></a></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>x</text></svg>',
        ];

        yield 'Keeps links with safe URLs' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a href="#x"><text>x</text></a><use xlink:href="#r"/><image href="a.png"/><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a href="#x"><text>x</text></a><use xlink:href="#r"/><image href="a.png"/><rect id="r"/></svg>',
        ];

        yield 'Removes XML Events attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><rect ev:event="click" ev:handler="#h" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ev="http://www.w3.org/2001/xml-events"><rect width="1"/></svg>',
        ];

        yield 'Removes attributes with a non-canonical name' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect HREF="#a" Width="1" height="1"/><use XLINK:HREF="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect height="1"/><use/></svg>',
        ];

        yield 'Removes an XLink attribute in the wrong namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="urn:x"><use xlink:href="#a"/><rect id="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="urn:x"><use/><rect id="a"/></svg>',
        ];

        yield 'Unwraps a link whose XLink prefix is bound to XML Events' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/2001/xml-events"><a xlink:href="javascript:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/2001/xml-events"><text>x</text></svg>',
        ];

        yield 'Removes foreign content that comes from an entity' => [
            '<!DOCTYPE svg [<!ENTITY f "<iframe/>">]><svg xmlns="http://www.w3.org/2000/svg">&f;<rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Leaves scripts, unsafe URLs in other attributes, style sheets and markup to the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><rect onclick="x()" fill="url(javascript:x)"/><style>@import url(a.css);</style><!-- <b> --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><rect onclick="x()" fill="url(javascript:x)"/><style>@import url(a.css);</style><!-- <b> --></svg>',
        ];
    }
}
