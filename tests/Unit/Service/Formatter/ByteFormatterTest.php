<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Formatter;

use MathiasReker\PhpSvgOptimizer\Service\Formatter\ByteFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ByteFormatter::class)]
final class ByteFormatterTest extends TestCase
{
    public function testFormatBytesReturnsBytesForLessThanOneKilobyte(): void
    {
        self::assertSame('512 B', ByteFormatter::formatBytes(512));
        self::assertSame('0 B', ByteFormatter::formatBytes(0));
    }

    public function testFormatBytesReturnsKilobytes(): void
    {
        self::assertSame('1.00 KB', ByteFormatter::formatBytes(1_024));
        self::assertSame('1.50 KB', ByteFormatter::formatBytes(1_536));
    }

    public function testFormatBytesReturnsMegabytes(): void
    {
        $bytes = 2 * 1_024 * 1_024; // 2 MB
        self::assertSame('2.00 MB', ByteFormatter::formatBytes($bytes));
    }

    public function testFormatBytesReturnsGigabytes(): void
    {
        $bytes = 3 * 1_024 * 1_024 * 1_024; // 3 GB
        self::assertSame('3.00 GB', ByteFormatter::formatBytes($bytes));
    }

    public function testFormatBytesReturnsTerabytes(): void
    {
        $bytes = 4 * 1_024 * 1_024 * 1_024 * 1_024; // 4 TB
        self::assertSame('4.00 TB', ByteFormatter::formatBytes($bytes));
    }

    public function testFormatBytesRoundsToTwoDecimals(): void
    {
        self::assertSame('1.95 KB', ByteFormatter::formatBytes(2_000));
        self::assertSame('1.91 MB', ByteFormatter::formatBytes(2_000_000));
    }

    public function testFormatBytesDoesNotExceedDefinedUnits(): void
    {
        // 1024^5 = 1 PB (Petabyte) → just beyond TB (last in units list)
        $bytes = 1_024 ** 5;

        // Should still return formatted using 'TB' (the last available unit)
        self::assertSame('1024.00 TB', ByteFormatter::formatBytes($bytes));
    }
}
