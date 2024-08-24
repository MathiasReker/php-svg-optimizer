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
use MathiasReker\PhpSvgOptimizer\Services\Rules\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FlattenGroups::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
final class FlattenGroupsTest extends TestCase
{
    public static function svgGroupsProvider(): \Iterator
    {
        yield 'Flattens Groups' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML
        ];

        yield 'No Groups to Flatten' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30"/>
                    <circle cx="50" cy="50" r="20"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML
        ];

        yield 'Nested Groups' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <g>
                            <rect x="10" y="10" width="30" height="30"/>
                        </g>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML
        ];

        yield 'Empty Group' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g></g>
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML
        ];

        yield 'Group with Attributes' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g fill="red">
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" fill="red"/><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML
        ];

        yield 'Group with Mixed Content' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g fill="blue">
                        <rect x="10" y="10" width="30" height="30"/>
                        <text x="20" y="20">Hello</text>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" fill="blue"/><text x="20" y="20" fill="blue">Hello</text></svg>
                XML
        ];

        yield 'Multiple Nested Groups' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <g>
                            <circle cx="30" cy="30" r="20"/>
                        </g>
                        <g>
                            <rect x="50" y="50" width="20" height="20"/>
                        </g>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="30" cy="30" r="20"/><rect x="50" y="50" width="20" height="20"/></svg>
                XML
        ];

        yield 'Deeply Nested Groups' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <g>
                            <g>
                                <g>
                                    <rect x="10" y="10" width="30" height="30"/>
                                </g>
                            </g>
                        </g>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML
        ];
    }

    #[DataProvider('svgGroupsProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new FlattenGroups());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
