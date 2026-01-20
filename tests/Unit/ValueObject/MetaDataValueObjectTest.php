<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObject;

use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MetaDataValueObject::class)]
final class MetaDataValueObjectTest extends TestCase
{
    private const int ORIGINAL_SIZE = 1_000;

    private const int OPTIMIZED_SIZE = 800;

    private const int SAVED_BYTES = 200;

    private const float SAVED_PERCENTAGE = 20.0;

    private const float OPTIMIZATION_TIME = 0.001;

    private MetaDataValueObject $metaDataValueObject;

    #[Test]
    public function zeroAndNegativeValues(): void
    {
        $metaDataValueObject = new MetaDataValueObject(0, -1, -1, -100.0, -0.1);
        self::assertSame(0, $metaDataValueObject->getOriginalSize());
        self::assertSame(-1, $metaDataValueObject->getOptimizedSize());
        self::assertSame(-1, $metaDataValueObject->getSavedBytes());
        self::assertEqualsWithDelta(-100.0, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function getOriginalSize(): void
    {
        self::assertSame(self::ORIGINAL_SIZE, $this->metaDataValueObject->getOriginalSize());
    }

    #[Test]
    public function getOptimizedSize(): void
    {
        self::assertSame(self::OPTIMIZED_SIZE, $this->metaDataValueObject->getOptimizedSize());
    }

    #[Test]
    public function getSavedBytes(): void
    {
        self::assertSame(self::SAVED_BYTES, $this->metaDataValueObject->getSavedBytes());
    }

    #[Test]
    public function getSavedPercentage(): void
    {
        self::assertEqualsWithDelta(self::SAVED_PERCENTAGE, $this->metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function largeValues(): void
    {
        $metaDataValueObject = new MetaDataValueObject(\PHP_INT_MAX, \PHP_INT_MAX - 1, 1, 0.000_000_1, \PHP_FLOAT_MAX);
        self::assertSame(\PHP_INT_MAX, $metaDataValueObject->getOriginalSize());
        self::assertSame(\PHP_INT_MAX - 1, $metaDataValueObject->getOptimizedSize());
        self::assertSame(1, $metaDataValueObject->getSavedBytes());
        self::assertEqualsWithDelta(0.000_000_1, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function boundaryValues(): void
    {
        $metaDataValueObject = new MetaDataValueObject(\PHP_INT_MIN, \PHP_INT_MAX, 0, 0.0, 0.0);
        self::assertSame(\PHP_INT_MIN, $metaDataValueObject->getOriginalSize());
        self::assertSame(\PHP_INT_MAX, $metaDataValueObject->getOptimizedSize());
        self::assertSame(0, $metaDataValueObject->getSavedBytes());
        self::assertEqualsWithDelta(0.0, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[Test]
    public function getOptimizedTime(): void
    {
        self::assertEqualsWithDelta(self::OPTIMIZATION_TIME, $this->metaDataValueObject->getOptimizationTime(), 0.000_001);
    }

    #[Test]
    public function zeroOptimizedTime(): void
    {
        $metaDataValueObject = new MetaDataValueObject(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            0.0,
        );

        self::assertSame(0.0, $metaDataValueObject->getOptimizationTime());
    }

    #[Test]
    public function smallOptimizedTime(): void
    {
        $smallTime = 0.000_001;
        $metaDataValueObject = new MetaDataValueObject(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            $smallTime,
        );

        self::assertEqualsWithDelta($smallTime, $metaDataValueObject->getOptimizationTime(), 0.000_000_1);
    }

    #[Test]
    public function largeOptimizedTime(): void
    {
        $largeTime = 12_345.678_9;
        $metaDataValueObject = new MetaDataValueObject(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            $largeTime,
        );

        self::assertEqualsWithDelta($largeTime, $metaDataValueObject->getOptimizationTime(), 0.000_001);
    }

    #[Test]
    public function negativeOptimizedTime(): void
    {
        $negativeTime = -0.5;
        $metaDataValueObject = new MetaDataValueObject(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            $negativeTime,
        );

        self::assertEqualsWithDelta($negativeTime, $metaDataValueObject->getOptimizationTime(), 0.000_001);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->metaDataValueObject = new MetaDataValueObject(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE,
            self::OPTIMIZATION_TIME,
        );
    }
}
