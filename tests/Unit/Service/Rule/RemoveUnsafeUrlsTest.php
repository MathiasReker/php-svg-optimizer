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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeUrls;
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
#[CoversClass(RemoveUnsafeUrls::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgTag::class)]
final class RemoveUnsafeUrlsTest extends TestCase
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
        $svgOptimizer->addPass(new RemoveUnsafeUrls());

        self::assertSame($expectedSvg, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes a javascript: link and keeps the element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><text>x</text></a></svg>',
        ];

        yield 'Removes javascript: and external links in XLink' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="javascript:x"/><image xlink:href="https://evil.example/a.png"/><use href="//evil.example/a.svg#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use/><image/><use/></svg>',
        ];

        yield 'Removes a link split by a backslash network path' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="\\\evil.example"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><text>x</text></a></svg>',
        ];

        yield 'Removes dangerous URLs in URL attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image src="javascript:a" action="javascript:b" formaction="vbscript:c" poster="javascript:d" data="javascript:e" ping="//x" background="javascript:f" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image width="1"/></svg>',
        ];

        yield 'Removes dangerous URLs in uppercase URL attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image SRC="javascript:a" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image width="1"/></svg>',
        ];

        yield 'Removes data URIs from links on elements that are not images' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="data:image/png;base64,AAAA"><text>x</text></a><use href="data:image/svg+xml;base64,AAAA"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><text>x</text></a><use/></svg>',
        ];

        yield 'Removes data URIs from both links of an element that is not an image' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="data:image/png;base64,AAAA" href="data:image/png;base64,BBBB"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a><text>x</text></a></svg>',
        ];

        yield 'Keeps data image URIs on images' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,AAAA"/><feImage href="data:image/svg+xml;base64,AAAA"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,AAAA"/><feImage href="data:image/svg+xml;base64,AAAA"/></svg>',
        ];

        yield 'Removes data URIs that are not images on images' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:text/html,x"/><image href="data:image/png,AAAA"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image/><image/></svg>',
        ];

        yield 'Decides data URIs per element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,AAAA"/><a href="data:image/png;base64,AAAA"><text>x</text></a><image href="data:image/png;base64,AAAA"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:image/png;base64,AAAA"/><a><text>x</text></a><image href="data:image/png;base64,AAAA"/></svg>',
        ];

        yield 'Removes url() values with a dangerous protocol' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(javascript:alert(1))" stroke="url(\'https://evil.example/a\')" filter="url(#f)" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect filter="url(#f)" width="1"/></svg>',
        ];

        yield 'Removes a dangerous URL in a style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(javascript:x)" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>',
        ];

        yield 'Removes animation values that set a dangerous URL' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a><animate attributeName="x" values="#a;javascript:alert(1)"/><set attributeName="x" to="javascript:alert(1)"/><animate attributeName="x" from="javascript:1" by="javascript:2"/><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><animate attributeName="x"/><set attributeName="x"/><animate attributeName="x"/><text>x</text></a></svg>',
        ];

        yield 'Keeps animation values with data image URIs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName="x" values="data:image/png;base64,AA;0;1"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName="x" values="data:image/png;base64,AA;0;1"/></rect></svg>',
        ];

        yield 'Removes a srcset with a dangerous candidate' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image srcset="a.png 1x, javascript:alert(1) 2x" width="1"/><image srcset="a.png 1x, b.png 2x" width="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image width="1"/><image srcset="a.png 1x, b.png 2x" width="2"/></svg>',
        ];

        yield 'Removes obfuscated javascript: URLs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#x09;script:1"><text>a</text></a><a href="j&#x61;vascript:2"><text>b</text></a><a href=" JAVASCRIPT:3"><text>c</text></a><a href="java&#x200B;script:4"><text>d</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><text>a</text></a><a><text>b</text></a><a><text>c</text></a><a><text>d</text></a></svg>',
        ];

        yield 'Keeps safe URLs' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a href="#x"><text>x</text></a><a href="page.html"><text>y</text></a><use xlink:href="#r"/><rect id="r" fill="url(#g)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a href="#x"><text>x</text></a><a href="page.html"><text>y</text></a><use xlink:href="#r"/><rect id="r" fill="url(#g)"/></svg>',
        ];

        yield 'Removes unsafe URLs that come from an entity' => [
            '<!DOCTYPE svg [<!ENTITY j "javascript:">]><svg xmlns="http://www.w3.org/2000/svg"><a href="&j;alert(1)"><text>x</text></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><text>x</text></a></svg>',
        ];

        yield 'Leaves scripts, foreign content, style sheets and markup to the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><rect onclick="x()" style="behavior:url(a.htc)"/><foreignObject/><style>@import url(a.css);</style><!-- <b> --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><rect onclick="x()" style="behavior:url(a.htc)"/><foreignObject/><style>@import url(a.css);</style><!-- <b> --></svg>',
        ];
    }
}
