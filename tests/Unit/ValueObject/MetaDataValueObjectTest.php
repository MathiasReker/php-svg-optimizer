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
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MetaDataValueObject::class)]
final class MetaDataValueObjectTest extends TestCase
{
    /**
     * The original size of the SVG file in bytes.
     * This is used to test the MetaDataValueObject's methods.
     */
    private const int ORIGINAL_SIZE = 1_000;

    /**
     * The optimized size of the SVG file in bytes.
     * This is used to test the MetaDataValueObject's methods.
     */
    private const int OPTIMIZED_SIZE = 800;

    /**
     * The number of bytes saved after optimization.
     * This is used to test the MetaDataValueObject's methods.
     */
    private const int SAVED_BYTES = 200;

    /**
     * The percentage of size saved after optimization.
     * This is used to test the MetaDataValueObject's methods.
     */
    private const float SAVED_PERCENTAGE = 20.0;

    private MetaDataValueObject $metaDataValueObject;

    public function testGetOriginalSize(): void
    {
        self::assertSame(self::ORIGINAL_SIZE, $this->metaDataValueObject->getOriginalSize());
    }

    public function testGetOptimizedSize(): void
    {
        self::assertSame(self::OPTIMIZED_SIZE, $this->metaDataValueObject->getOptimizedSize());
    }

    public function testGetSavedBytes(): void
    {
        self::assertSame(self::SAVED_BYTES, $this->metaDataValueObject->getSavedBytes());
    }

    public function testGetSavedPercentage(): void
    {
        self::assertEqualsWithDelta(self::SAVED_PERCENTAGE, $this->metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    public function testZeroAndNegativeValues(): void
    {
        $metaDataValueObject = new MetaDataValueObject(0, -1, -1, -100.0);
        self::assertSame(0, $metaDataValueObject->getOriginalSize());
        self::assertSame(-1, $metaDataValueObject->getOptimizedSize());
        self::assertSame(-1, $metaDataValueObject->getSavedBytes());
        self::assertEqualsWithDelta(-100.0, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    public function testLargeValues(): void
    {
        $metaDataValueObject = new MetaDataValueObject(\PHP_INT_MAX, \PHP_INT_MAX - 1, 1, 0.000_000_1);
        self::assertSame(\PHP_INT_MAX, $metaDataValueObject->getOriginalSize());
        self::assertSame(\PHP_INT_MAX - 1, $metaDataValueObject->getOptimizedSize());
        self::assertSame(1, $metaDataValueObject->getSavedBytes());
        self::assertEqualsWithDelta(0.000_000_1, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    public function testBoundaryValues(): void
    {
        $metaDataValueObject = new MetaDataValueObject(\PHP_INT_MIN, \PHP_INT_MAX, 0, 0.0);
        self::assertSame(\PHP_INT_MIN, $metaDataValueObject->getOriginalSize());
        self::assertSame(\PHP_INT_MAX, $metaDataValueObject->getOptimizedSize());
        self::assertSame(0, $metaDataValueObject->getSavedBytes());
        self::assertEqualsWithDelta(0.0, $metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->metaDataValueObject = new MetaDataValueObject(
            self::ORIGINAL_SIZE,
            self::OPTIMIZED_SIZE,
            self::SAVED_BYTES,
            self::SAVED_PERCENTAGE
        );
    }
}
