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
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RemoveUnnecessaryWhitespace::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
final class RemoveUnnecessaryWhitespaceTest extends TestCase
{
    public static function svgWhitespaceProvider(): \Iterator
    {
        yield 'Removes Unnecessary Whitespace' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">

                    <circle cx="50" cy="50" r="20" fill="red"/>

                    <rect x="10" y="10" width="30" height="30" fill="blue"/>

                    <!-- A comment -->
                    <text x="20" y="20"> Hello World </text>

                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/><!-- A comment --><text x="20" y="20"> Hello World </text></svg>
                XML
        ];

        yield 'Only Whitespace and Comments' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg">

                    <!-- A comment -->


                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><!-- A comment --></svg>
                XML
        ];

        yield 'Multiple Elements and Mixed Whitespace' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <circle cx="100" cy="100" r="50" fill="green" />

                    <!-- Comment -->

                    <rect x="20" y="20" width="100" height="100" fill="yellow"/>

                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><circle cx="100" cy="100" r="50" fill="green"/><!-- Comment --><rect x="20" y="20" width="100" height="100" fill="yellow"/></svg>
                XML
        ];

        yield 'Minimal SVG Content' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML
        ];

        yield 'Well-Formatted SVG' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML
        ];
    }

    #[DataProvider('svgWhitespaceProvider')]
    public function testOptimizeRemovesUnnecessaryWhitespace(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new RemoveUnnecessaryWhitespace());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
