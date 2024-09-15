<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Rules;

use MathiasReker\PhpSvgOptimizer\Models\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Services\Providers\StringProvider;
use MathiasReker\PhpSvgOptimizer\Services\Rules\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MinifyTransformations::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MinifyTransformationsTest extends TestCase
{
    public static function svgTransformationsProvider(): \Iterator
    {
        yield 'Removes Identity Transforms' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0) scale(1,1) rotate(0) skewX(0) skewY(0)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML
        ];

        yield 'Retains Non-Identity Transforms' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10,20) scale(2,2)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,20) scale(2,2)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML
        ];

        yield 'Handles Mixed Identity and Non-Identity Transforms' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0) scale(1,1) translate(10,20)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,20)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML
        ];

        yield 'Removes Empty Transform Attribute' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML
        ];

        yield 'Removes Multiple Identity Transform Steps' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0) scale(1,1) rotate(0) skewX(0)">
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="50" cy="50" r="20"/></g></svg>
                XML
        ];

        yield 'Handles Complex Transformations' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10,10) rotate(45) scale(0.5)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,10) rotate(45) scale(0.5)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML
        ];

        yield 'Retains Nested Transformations' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10,10)">
                        <g transform="rotate(30)">
                            <rect x="10" y="10" width="30" height="30"/>
                        </g>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,10)"><g transform="rotate(30)"><rect x="10" y="10" width="30" height="30"/></g></g></svg>
                XML
        ];

        yield 'Handles Transformations with Percentages' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="scale(50%,50%)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="scale(0.5,0.5)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML
        ];
    }

    #[DataProvider('svgTransformationsProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new MinifyTransformations());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
