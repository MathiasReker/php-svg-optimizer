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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ByteFormatter::class)]
final class ByteFormatterTest extends TestCase
{
    #[Test]
    public function formatBytesReturnsBytesForLessThanOneKilobyte(): void
    {
        self::assertSame('512 B', ByteFormatter::formatBytes(512));
        self::assertSame('0 B', ByteFormatter::formatBytes(0));
    }

    #[Test]
    public function formatBytesReturnsKilobytes(): void
    {
        self::assertSame('1.00 KB', ByteFormatter::formatBytes(1_024));
        self::assertSame('1.50 KB', ByteFormatter::formatBytes(1_536));
    }

    #[Test]
    public function formatBytesReturnsMegabytes(): void
    {
        $bytes = 2 * 1_024 * 1_024;
        self::assertSame('2.00 MB', ByteFormatter::formatBytes($bytes));
    }

    #[Test]
    public function formatBytesReturnsGigabytes(): void
    {
        $bytes = 3 * 1_024 * 1_024 * 1_024;
        self::assertSame('3.00 GB', ByteFormatter::formatBytes($bytes));
    }

    #[Test]
    public function formatBytesReturnsTerabytes(): void
    {
        $bytes = 4 * 1_024 * 1_024 * 1_024 * 1_024;
        self::assertSame('4.00 TB', ByteFormatter::formatBytes($bytes));
    }

    #[Test]
    public function formatBytesRoundsToTwoDecimals(): void
    {
        self::assertSame('1.95 KB', ByteFormatter::formatBytes(2_000));
        self::assertSame('1.91 MB', ByteFormatter::formatBytes(2_000_000));
    }

    #[Test]
    public function formatBytesDoesNotExceedDefinedUnits(): void
    {
        $bytes = 1_024 ** 5;

        self::assertSame('1024.00 TB', ByteFormatter::formatBytes($bytes));
    }
}
