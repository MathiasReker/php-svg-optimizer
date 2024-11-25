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
    public function testConstructorValid(): void
    {
        $metaData = new MetaData(1000, 800);

        Assert::assertInstanceOf(MetaData::class, $metaData);
    }

    public function testConstructorInvalidOriginalSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Original size must be greater than 0. Given: 0');

        new MetaData(0, 500);
    }

    public function testToValueObject(): void
    {
        $metaData = new MetaData(1000, 800);
        $metaDataValueObject = $metaData->toValueObject();

        Assert::assertInstanceOf(MetaDataValueObject::class, $metaDataValueObject);
        Assert::assertSame(1000, $metaDataValueObject->getOriginalSize());
        Assert::assertSame(800, $metaDataValueObject->getOptimizedSize());
        Assert::assertSame(200, $metaDataValueObject->getSavedBytes());
        Assert::assertEqualsWithDelta(20.0, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCalculateSavedBytes(): void
    {
        $metaData = new MetaData(1000, 800);

        $reflectionClass = new \ReflectionClass($metaData);
        $reflectionMethod = $reflectionClass->getMethod('calculateSavedBytes');

        $savedBytes = $reflectionMethod->invoke($metaData);

        Assert::assertSame(200, $savedBytes);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCalculateSavedPercentage(): void
    {
        $metaData = new MetaData(1000, 800);

        $reflectionClass = new \ReflectionClass($metaData);
        $reflectionMethod = $reflectionClass->getMethod('calculateSavedPercentage');

        $savedPercentage = $reflectionMethod->invoke($metaData);

        Assert::assertEqualsWithDelta(20.0, $savedPercentage, \PHP_FLOAT_EPSILON);
    }
}
