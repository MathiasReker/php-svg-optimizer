<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output;

use MathiasReker\PhpSvgOptimizer\Console\Output\ByteSizeFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ByteSizeFormatter::class)]
final class ByteSizeFormatterTest extends TestCase
{
    #[DataProvider('provideFormatsASizeWithItsUnitCases')]
    #[Test]
    public function formatsASizeWithItsUnit(int $bytes, string $expected): void
    {
        self::assertSame($expected, ByteSizeFormatter::format($bytes));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function provideFormatsASizeWithItsUnitCases(): iterable
    {
        yield 'zero' => [0, '0 B'];

        yield 'below a kilobyte' => [1_023, '1023 B'];

        yield 'a kilobyte' => [1_024, '1.00 KB'];

        yield 'kilobytes' => [1_536, '1.50 KB'];

        yield 'megabytes' => [5 * 1_024 * 1_024, '5.00 MB'];

        yield 'gigabytes' => [3 * 1_024 ** 3, '3.00 GB'];

        yield 'terabytes' => [2 * 1_024 ** 4, '2.00 TB'];

        yield 'a negative size' => [-2_048, '-2.00 KB'];

        yield 'a small negative size' => [-5, '-5 B'];
    }
}
