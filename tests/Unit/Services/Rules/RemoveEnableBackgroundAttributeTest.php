<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Rules;

use MathiasReker\PhpSvgOptimizer\Models\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Services\Providers\StringProvider;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveEnableBackgroundAttribute::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveEnableBackgroundAttributeTest extends TestCase
{
    public static function svgRemoveEnableBackgroundProvider(): \Iterator
    {
        yield 'Removes enable-background for svg with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Keeps enable-background for svg with different dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background for mask with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <mask xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                        <rect x="10" y="10" width="30" height="30"/>
                    </mask>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><mask xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></mask></svg>
                XML,
        ];

        yield 'Keeps enable-background for pattern with different dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                        <rect x="10" y="10" width="30" height="30"/>
                    </pattern>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100"><rect x="10" y="10" width="30" height="30"/></pattern></svg>
                XML,
        ];

        yield 'Removes enable-background for pattern with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                        <rect x="10" y="10" width="30" height="30"/>
                    </pattern>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></pattern></svg>
                XML,
        ];

        yield 'Removes enable-background for svg with no filter' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Keeps enable-background if no filter and dimensions don’t match' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];
    }

    #[DataProvider('svgRemoveEnableBackgroundProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new RemoveEnableBackgroundAttribute());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
