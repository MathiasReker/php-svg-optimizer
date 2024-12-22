<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\ValueObjects;

use MathiasReker\PhpSvgOptimizer\ValueObjects\MetaDataValueObject;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
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

    private MetaDataValueObject $metaDataValueObject;

    public function testGetOriginalSize(): void
    {
        Assert::assertSame(self::ORIGINAL_SIZE, $this->metaDataValueObject->getOriginalSize());
    }

    public function testGetOptimizedSize(): void
    {
        Assert::assertSame(self::OPTIMIZED_SIZE, $this->metaDataValueObject->getOptimizedSize());
    }

    public function testGetSavedBytes(): void
    {
        Assert::assertSame(self::SAVED_BYTES, $this->metaDataValueObject->getSavedBytes());
    }

    public function testGetSavedPercentage(): void
    {
        Assert::assertEqualsWithDelta(self::SAVED_PERCENTAGE, $this->metaDataValueObject->getSavedPercentage(), \PHP_FLOAT_EPSILON);
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
