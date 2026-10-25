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
use MathiasReker\PhpSvgOptimizer\Service\Rule\SanitizeStyleSheets;
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
#[CoversClass(SanitizeStyleSheets::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgTag::class)]
final class SanitizeStyleSheetsTest extends TestCase
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
        $svgOptimizer->addRule(new SanitizeStyleSheets());

        self::assertSame($expectedSvg, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes an @import statement and keeps the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);rect{fill:red}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect/></svg>',
        ];

        yield 'Removes a style element that only imports' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import "a.css";</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a declaration with an unsafe URL and keeps the others' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red;background:url(javascript:x)}circle{fill:blue}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red;}circle{fill:blue}</style><rect/></svg>',
        ];

        yield 'Removes a rule with an unsafe selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a[x="javascript:1"]{fill:red}rect{fill:blue}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:blue}</style><rect/></svg>',
        ];

        yield 'Removes a style element with markup' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}&lt;/style&gt;&lt;script&gt;alert(1)&lt;/script&gt;</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect/></svg>',
        ];

        yield 'Removes a style element that cannot be sanitized' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red;background:url(javascript:x)</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes a dangerous style element that is not CSS' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/x">rect{fill:red}@import url(a.css);</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Sanitizes every style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);a{fill:red}</style><style>@import url(b.css);b{fill:blue}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}</style><style>b{fill:blue}</style><rect/></svg>',
        ];

        yield 'Keeps safe style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red;background:url(#g)}</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red;background:url(#g)}</style><rect/></svg>',
        ];

        yield 'Removes unsafe style attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="width:expression(alert(1))"/><rect style="behavior:url(a.htc)"/><rect style="-moz-binding:url(a.xml#x)"/><rect STYLE="background:url(https://evil.example/a)"/><rect style="background:image-set(\'https://evil.example/a\' 1x)"/><rect style="fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/><rect/><rect/><rect/><rect/><rect style="fill:red"/></svg>',
        ];

        yield 'Removes an escaped unsafe style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:url(j\61vascript:x)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an unsafe style sheet that comes from an entity' => [
            '<!DOCTYPE svg [<!ENTITY i "@import url(a.css);">]><svg xmlns="http://www.w3.org/2000/svg"><style>&i;</style><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Leaves scripts, unsafe URLs, foreign content and markup to the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><a href="javascript:x"><rect onclick="x()" fill="url(javascript:x)"/></a><foreignObject/><!-- <b> --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><a href="javascript:x"><rect onclick="x()" fill="url(javascript:x)"/></a><foreignObject/><!-- <b> --></svg>',
        ];
    }

    #[Test]
    public function ruleIsNotRisky(): void
    {
        self::assertFalse(SanitizeStyleSheets::isRisky());
    }

    #[Test]
    public function ruleSkipsTheSizeCheck(): void
    {
        self::assertFalse(SanitizeStyleSheets::shouldCheckSize());
    }
}
