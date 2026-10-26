<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit;

use MathiasReker\PhpSvgOptimizer\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Metrics::class)]
final class MetricsTest extends TestCase
{
    #[Test]
    public function reportsTheOriginalSize(): void
    {
        self::assertSame(1_000, (new Metrics(1_000, 800, 0.001))->getOriginalSizeInBytes());
    }

    #[Test]
    public function reportsTheOptimizedSize(): void
    {
        self::assertSame(800, (new Metrics(1_000, 800, 0.001))->getOptimizedSizeInBytes());
    }

    #[Test]
    public function reportsTheDuration(): void
    {
        self::assertSame(0.001, (new Metrics(1_000, 800, 0.001))->getDurationInSeconds());
    }

    #[DataProvider('provideReportsTheSavedSizeCases')]
    #[Test]
    public function reportsTheSavedSize(int $original, int $optimized, int $saved): void
    {
        self::assertSame($saved, (new Metrics($original, $optimized, 0.0))->getSavedSizeInBytes());
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function provideReportsTheSavedSizeCases(): iterable
    {
        yield 'smaller' => [1_000, 800, 200];

        yield 'same size' => [1_000, 1_000, 0];

        yield 'larger' => [47, 52, -5];
    }

    #[DataProvider('provideReportsTheSavedSizeAsAPercentageOfTheOriginalSizeCases')]
    #[Test]
    public function reportsTheSavedSizeAsAPercentageOfTheOriginalSize(int $original, int $optimized, float $percentage): void
    {
        self::assertEqualsWithDelta($percentage, (new Metrics($original, $optimized, 0.0))->getSavedPercentage(), 0.000_001);
    }

    /**
     * @return iterable<string, array{int, int, float}>
     */
    public static function provideReportsTheSavedSizeAsAPercentageOfTheOriginalSizeCases(): iterable
    {
        yield 'a fifth saved' => [1_000, 800, 20.0];

        yield 'everything saved' => [10, 0, 100.0];

        yield 'nothing saved' => [10, 10, 0.0];

        yield 'larger' => [47, 52, -5 / 47 * 100];

        yield 'empty original' => [0, 0, 0.0];
    }

    #[DataProvider('provideTellsWhetherTheOptimizedSvgIsSmallerCases')]
    #[Test]
    public function tellsWhetherTheOptimizedSvgIsSmaller(int $original, int $optimized, bool $hasSavings): void
    {
        self::assertSame($hasSavings, (new Metrics($original, $optimized, 0.0))->hasSavings());
    }

    /**
     * @return iterable<string, array{int, int, bool}>
     */
    public static function provideTellsWhetherTheOptimizedSvgIsSmallerCases(): iterable
    {
        yield 'smaller' => [10, 9, true];

        yield 'same size' => [10, 10, false];

        yield 'larger' => [10, 11, false];
    }
}
