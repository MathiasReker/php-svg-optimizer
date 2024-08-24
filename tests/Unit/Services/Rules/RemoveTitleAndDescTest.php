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
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RemoveTitleAndDesc::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
final class RemoveTitleAndDescTest extends TestCase
{
    public static function svgTitleDescProvider(): \Iterator
    {
        yield 'Removes Title and Desc' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <title>This is a title</title>
                    <desc>This is a description</desc>
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/></svg>
                XML
        ];

        yield 'No Title or Desc to Remove' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/></svg>
                XML
        ];

        yield 'Removes Only Title' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <title>This is a title</title>
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/></svg>
                XML
        ];

        yield 'Removes Only Desc' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <desc>This is a description</desc>
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/></svg>
                XML
        ];

        yield 'Multiple Titles and Descriptions' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <title>First Title</title>
                    <desc>First Description</desc>
                    <circle cx="50" cy="50" r="40" fill="green"/>
                    <title>Second Title</title>
                    <desc>Second Description</desc>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40" fill="green"/></svg>
                XML
        ];

        yield 'Nested Elements with Title and Desc' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <title>Group Title</title>
                        <desc>Group Description</desc>
                        <circle cx="50" cy="50" r="40" fill="blue"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="50" cy="50" r="40" fill="blue"/></g></svg>
                XML
        ];

        yield 'Non-Standard Title and Desc Elements' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <titleExtra>Extra Title</titleExtra>
                    <descExtra>Extra Description</descExtra>
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><titleExtra>Extra Title</titleExtra><descExtra>Extra Description</descExtra><rect width="100" height="100" fill="red"/></svg>
                XML
        ];
    }

    #[DataProvider('svgTitleDescProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new RemoveTitleAndDesc());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
