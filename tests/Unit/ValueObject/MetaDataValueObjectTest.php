<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Metrics::class)]
final class MetaDataValueObjectTest extends TestCase
{
    private const int ORIGINAL_SIZE = 1_000;

    private const int OPTIMIZED_SIZE = 800;

    private const int SAVED_BYTES = 200;

    private const float SAVED_PERCENTAGE = 20.0;

    private const float OPTIMIZATION_TIME = 0.001;

    private Metrics $metrics;

    #[Test]
    public function zeroAndNegativeValues(): void
    {
        $metrics = new Metrics(0, -1, -1, -100.0, -0.1);
        self::assertSame(0, $metrics->getOriginalSize());
        self::assertSame(-1, $metrics->getOptimizedSize());
        self::assertSame(-1, $metrics->getSavedBytes());
        self::assertEqualsWithDelta(-100.0, $metrics->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function getOriginalSize(): void
    {
        self::assertSame(self::ORIGINAL_SIZE, $this->metrics->getOriginalSize());
    }

    #[Test]
    public function getOptimizedSize(): void
    {
        self::assertSame(self::OPTIMIZED_SIZE, $this->metrics->getOptimizedSize());
    }

    #[Test]
    public function getSavedBytes(): void
    {
        self::assertSame(self::SAVED_BYTES, $this->metrics->getSavedBytes());
    }

    #[Test]
    public function getSavedPercentage(): void
    {
        self::assertEqualsWithDelta(self::SAVED_PERCENTAGE, $this->metrics->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function largeValues(): void
    {
        $metrics = new Metrics(\PHP_INT_MAX, \PHP_INT_MAX - 1, 1, 0.000_000_1, \PHP_FLOAT_MAX);
        self::assertSame(\PHP_INT_MAX, $metrics->getOriginalSize());
        self::assertSame(\PHP_INT_MAX - 1, $metrics->getOptimizedSize());
        self::assertSame(1, $metrics->getSavedBytes());
        self::assertEqualsWithDelta(0.000_000_1, $metrics->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function boundaryValues(): void
    {
        $metrics = new Metrics(\PHP_INT_MIN, \PHP_INT_MAX, 0, 0.0, 0.0);
        self::assertSame(\PHP_INT_MIN, $metrics->getOriginalSize());
        self::assertSame(\PHP_INT_MAX, $metrics->getOptimizedSize());
        self::assertSame(0, $metrics->getSavedBytes());
        self::assertEqualsWithDelta(0.0, $metrics->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function getOptimizedTime(): void
    {
        self::assertEqualsWithDelta(self::OPTIMIZATION_TIME, $this->metrics->getOptimizationTime(), 0.000_001);
    }

    #[Test]
    public function zeroOptimizedTime(): void
    {
        $metrics = new Metrics(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            0.0,
        );

        self::assertSame(0.0, $metrics->getOptimizationTime());
    }

    #[Test]
    public function smallOptimizedTime(): void
    {
        $smallTime = 0.000_001;
        $metrics = new Metrics(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            $smallTime,
        );

        self::assertEqualsWithDelta($smallTime, $metrics->getOptimizationTime(), 0.000_000_1);
    }

    #[Test]
    public function largeOptimizedTime(): void
    {
        $largeTime = 12_345.678_9;
        $metrics = new Metrics(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            $largeTime,
        );

        self::assertEqualsWithDelta($largeTime, $metrics->getOptimizationTime(), 0.000_001);
    }

    #[Test]
    public function negativeOptimizedTime(): void
    {
        $negativeTime = -0.5;
        $metrics = new Metrics(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            $negativeTime,
        );

        self::assertEqualsWithDelta($negativeTime, $metrics->getOptimizationTime(), 0.000_001);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->metrics = new Metrics(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            self::OPTIMIZATION_TIME,
        );
    }
}
