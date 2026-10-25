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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUseBombs;
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
#[CoversClass(RemoveUseBombs::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(SvgAttribute::class)]
#[CoversClass(SvgTag::class)]
final class RemoveUseBombsTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveUseBombs());

        self::assertSame($expectedSvg, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes a use element that references its own ancestor' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><use href="#a"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"/></svg>',
        ];

        yield 'Removes use elements that reference each other' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="a"><use href="#b"/></g><g id="b"><use href="#a"/></g></defs><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="a"><use href="#b"/></g><g id="b"/></defs><use href="#a"/></svg>',
        ];

        yield 'Keeps use elements that reference other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><rect id="r"/><g id="g"><use href="#r"/><use xlink:href="#r"/></g></defs><use href="#g"/><use href="other.svg#r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><rect id="r"/><g id="g"><use href="#r"/><use xlink:href="#r"/></g></defs><use href="#g"/><use href="other.svg#r"/></svg>',
        ];

        yield 'Leaves scripts, unsafe URLs, foreign content, style sheets and markup to the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><a href="javascript:x"><rect onclick="x()"/></a><foreignObject/><style>@import url(a.css);</style><!-- <b> --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><a href="javascript:x"><rect onclick="x()"/></a><foreignObject/><style>@import url(a.css);</style><!-- <b> --></svg>',
        ];
    }

    #[Test]
    public function ruleIsNotRisky(): void
    {
        self::assertFalse(RemoveUseBombs::isRisky());
    }

    #[Test]
    public function ruleSkipsTheSizeCheck(): void
    {
        self::assertFalse(RemoveUseBombs::shouldCheckSize());
    }
}
