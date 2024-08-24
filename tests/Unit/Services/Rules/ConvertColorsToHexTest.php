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
use MathiasReker\PhpSvgOptimizer\Services\Rules\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConvertColorsToHex::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
final class ConvertColorsToHexTest extends TestCase
{
    public static function svgContentProvider(): \Iterator
    {
        yield 'Converts RGB colors to Hex' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(255, 0, 0)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 255, 0)"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#FF0000"/><circle cx="50" cy="50" r="40" stroke="#00FF00"/></svg>
                XML
        ];

        yield 'Keeps existing Hex colors unchanged' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="#FF0000"/>
                    <circle cx="50" cy="50" r="40" stroke="#00FF00"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#FF0000"/><circle cx="50" cy="50" r="40" stroke="#00FF00"/></svg>
                XML
        ];

        yield 'Converts RGB colors with additional attributes' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(255, 0, 0)" id="rect1"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 255, 0)" class="circle"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#FF0000" id="rect1"/><circle cx="50" cy="50" r="40" stroke="#00FF00" class="circle"/></svg>
                XML
        ];

        yield 'Handles Hex colors with short notation' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="#F00"/>
                    <circle cx="50" cy="50" r="40" stroke="#0F0"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#F00"/><circle cx="50" cy="50" r="40" stroke="#0F0"/></svg>
                XML
        ];

        yield 'Ignores colors in non-standard attributes' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" data-color="rgb(255, 255, 255)" />
                    <circle cx="50" cy="50" r="40" data-stroke="rgb(0, 0, 255)" />
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" data-color="rgb(255, 255, 255)"/><circle cx="50" cy="50" r="40" data-stroke="rgb(0, 0, 255)"/></svg>
                XML
        ];

        yield 'Converts rgba colors to Hex with opacity' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgba(255, 0, 0, 0.5)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgba(0, 255, 0, 0.3)"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="rgba(255, 0, 0, 0.5)"/><circle cx="50" cy="50" r="40" stroke="rgba(0, 255, 0, 0.3)"/></svg>
                XML
        ];

        yield 'Ignore color names' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="red"/>
                    <circle cx="50" cy="50" r="40" stroke="lime"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/><circle cx="50" cy="50" r="40" stroke="lime"/></svg>
                XML
        ];
    }

    #[DataProvider('svgContentProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new ConvertColorsToHex());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
