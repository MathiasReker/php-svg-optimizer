<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Utility;

use MathiasReker\PhpSvgOptimizer\Utility\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Formatter::class)]
final class FormatterTest extends TestCase
{
    public function testFormatBytesReturnsBytesForLessThanOneKilobyte(): void
    {
        self::assertSame('512 B', Formatter::formatBytes(512));
        self::assertSame('0 B', Formatter::formatBytes(0));
    }

    public function testFormatBytesReturnsKilobytes(): void
    {
        self::assertSame('1.00 KB', Formatter::formatBytes(1_024));
        self::assertSame('1.50 KB', Formatter::formatBytes(1_536));
    }

    public function testFormatBytesReturnsMegabytes(): void
    {
        $bytes = 2 * 1_024 * 1_024; // 2 MB
        self::assertSame('2.00 MB', Formatter::formatBytes($bytes));
    }

    public function testFormatBytesReturnsGigabytes(): void
    {
        $bytes = 3 * 1_024 * 1_024 * 1_024; // 3 GB
        self::assertSame('3.00 GB', Formatter::formatBytes($bytes));
    }

    public function testFormatBytesReturnsTerabytes(): void
    {
        $bytes = 4 * 1_024 * 1_024 * 1_024 * 1_024; // 4 TB
        self::assertSame('4.00 TB', Formatter::formatBytes($bytes));
    }

    public function testFormatBytesRoundsToTwoDecimals(): void
    {
        self::assertSame('1.95 KB', Formatter::formatBytes(2_000));
        self::assertSame('1.91 MB', Formatter::formatBytes(2_000_000));
    }
}
