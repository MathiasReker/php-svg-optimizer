<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Data;

use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MetaData::class)]
#[CoversClass(MetaDataValueObject::class)]
final class MetaDataTest extends TestCase
{
    /**
     * The original size of the SVG file in bytes.
     * This is used to test the MetaData's methods.
     */
    private const int ORIGINAL_SIZE = 1_000;

    /**
     * The optimized size of the SVG file in bytes.
     * This is used to test the MetaData's methods.
     */
    private const int OPTIMIZED_SIZE = 800;

    /**
     * The time taken to optimize the SVG file in seconds.
     * Used for testing purposes in unit tests.
     */
    private const float OPTIMIZED_TIME = 0.001;

    /**
     * The size of the SVG file after optimization in bytes.
     * This is used to test the MetaData's methods.
     */
    private const int ZERO_SIZE = 0;

    /**
     * The number of bytes saved after optimization.
     * This is used to test the MetaData's methods.
     */
    private const int EXPECTED_SAVED_BYTES = 200;

    /**
     * The percentage of size saved after optimization.
     * This is used to test the MetaData's methods.
     */
    private const float EXPECTED_SAVED_PERCENTAGE = 20.0;

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function constructorInvalidOriginalSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Original size must be greater than 0. Given: 0');

        new MetaData(self::ZERO_SIZE, self::OPTIMIZED_SIZE, self::OPTIMIZED_TIME);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function toValueObject(): void
    {
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE, self::OPTIMIZED_TIME);
        $metaDataValueObject = $metaData->toValueObject();

        self::assertSame(self::ORIGINAL_SIZE, $metaDataValueObject->getOriginalSize());
        self::assertSame(self::OPTIMIZED_SIZE, $metaDataValueObject->getOptimizedSize());
        self::assertSame(self::EXPECTED_SAVED_BYTES, $metaDataValueObject->getSavedBytes());
        self::assertSame(self::OPTIMIZED_TIME, $metaDataValueObject->getOptimizationTime());
        self::assertEqualsWithDelta(self::EXPECTED_SAVED_PERCENTAGE, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    /**
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function calculateSavedBytes(): void
    {
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE, self::OPTIMIZED_TIME);

        $reflectionClass = new \ReflectionClass($metaData);
        $reflectionMethod = $reflectionClass->getMethod('calculateSavedBytes');

        $savedBytes = $reflectionMethod->invoke($metaData);

        self::assertSame(self::EXPECTED_SAVED_BYTES, $savedBytes);
    }

    /**
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function calculateSavedPercentage(): void
    {
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE, self::OPTIMIZED_TIME);

        $reflectionClass = new \ReflectionClass($metaData);
        $reflectionMethod = $reflectionClass->getMethod('calculateSavedPercentage');

        $savedPercentage = $reflectionMethod->invoke($metaData);

        self::assertEqualsWithDelta(self::EXPECTED_SAVED_PERCENTAGE, $savedPercentage, \PHP_FLOAT_EPSILON);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function optimizationTimeIsCorrectlyReturned(): void
    {
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE, self::OPTIMIZED_TIME);
        $metaDataValueObject = $metaData->toValueObject();

        self::assertSame(
            self::OPTIMIZED_TIME,
            $metaDataValueObject->getOptimizationTime(),
            'Optimization time should match the value passed to MetaData.'
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function zeroOptimizationTime(): void
    {
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE, 0.0);
        $metaDataValueObject = $metaData->toValueObject();

        self::assertSame(0.0, $metaDataValueObject->getOptimizationTime());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function smallOptimizationTime(): void
    {
        $smallTime = 0.000_001;
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE, $smallTime);
        $metaDataValueObject = $metaData->toValueObject();

        self::assertEqualsWithDelta($smallTime, $metaDataValueObject->getOptimizationTime(), 0.000_000_1);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function largeOptimizationTime(): void
    {
        $largeTime = 12_345.678_9;
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE, $largeTime);
        $metaDataValueObject = $metaData->toValueObject();

        self::assertEqualsWithDelta($largeTime, $metaDataValueObject->getOptimizationTime(), 0.000_001);
    }
}
