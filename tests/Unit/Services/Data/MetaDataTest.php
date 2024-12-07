<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Data;

use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;
use MathiasReker\PhpSvgOptimizer\Services\Data\MetaData;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetaData::class)]
#[CoversClass(MetaDataValueObject::class)]
final class MetaDataTest extends TestCase
{
    private const int ORIGINAL_SIZE = 1000;

    private const int OPTIMIZED_SIZE = 800;

    private const int ZERO_SIZE = 0;

    private const int EXPECTED_SAVED_BYTES = 200;

    private const float EXPECTED_SAVED_PERCENTAGE = 20.0;

    public function testConstructorInvalidOriginalSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Original size must be greater than 0. Given: 0');

        new MetaData(self::ZERO_SIZE, self::OPTIMIZED_SIZE);
    }

    public function testToValueObject(): void
    {
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE);
        $metaDataValueObject = $metaData->toValueObject();

        Assert::assertSame(self::ORIGINAL_SIZE, $metaDataValueObject->getOriginalSize());
        Assert::assertSame(self::OPTIMIZED_SIZE, $metaDataValueObject->getOptimizedSize());
        Assert::assertSame(self::EXPECTED_SAVED_BYTES, $metaDataValueObject->getSavedBytes());
        Assert::assertEqualsWithDelta(self::EXPECTED_SAVED_PERCENTAGE, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCalculateSavedBytes(): void
    {
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE);

        $reflectionClass = new \ReflectionClass($metaData);
        $reflectionMethod = $reflectionClass->getMethod('calculateSavedBytes');

        $savedBytes = $reflectionMethod->invoke($metaData);

        Assert::assertSame(self::EXPECTED_SAVED_BYTES, $savedBytes);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCalculateSavedPercentage(): void
    {
        $metaData = new MetaData(self::ORIGINAL_SIZE, self::OPTIMIZED_SIZE);

        $reflectionClass = new \ReflectionClass($metaData);
        $reflectionMethod = $reflectionClass->getMethod('calculateSavedPercentage');

        $savedPercentage = $reflectionMethod->invoke($metaData);

        Assert::assertEqualsWithDelta(self::EXPECTED_SAVED_PERCENTAGE, $savedPercentage, \PHP_FLOAT_EPSILON);
    }
}
