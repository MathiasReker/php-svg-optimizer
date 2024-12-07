<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit;

use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;
use MathiasReker\PhpSvgOptimizer\Services\Data\MetaData;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetaData::class)]
#[CoversClass(MetaDataValueObject::class)]
final class MetaDataTest extends TestCase
{
    private const int ORIGINAL_SIZE_1 = 1000;

    private const int ORIGINAL_SIZE_2 = 1;

    private const int OPTIMIZED_SIZE_1 = 500;

    private const int OPTIMIZED_SIZE_2 = 1000;

    private const int OPTIMIZED_SIZE_3 = 0;

    private const int OPTIMIZED_SIZE_4 = 500;

    private const int SAVED_BYTES_1 = 500;

    private const int SAVED_BYTES_2 = 0;

    private const int SAVED_BYTES_3 = 1;

    private const int SAVED_BYTES_4 = -499;

    private const float SAVED_PERCENTAGE_1 = 50.0;

    private const float SAVED_PERCENTAGE_2 = 0.0;

    private const float SAVED_PERCENTAGE_3 = 100.0;

    private const float SAVED_PERCENTAGE_4 = -49900.0;

    public static function metadataProvider(): \Iterator
    {
        yield [
            self::ORIGINAL_SIZE_1,
            self::OPTIMIZED_SIZE_1,
            new MetaDataValueObject(
                self::ORIGINAL_SIZE_1,
                self::OPTIMIZED_SIZE_1,
                self::SAVED_BYTES_1,
                self::SAVED_PERCENTAGE_1
            ),
        ];
        yield [
            self::ORIGINAL_SIZE_1,
            self::OPTIMIZED_SIZE_2,
            new MetaDataValueObject(
                self::ORIGINAL_SIZE_1,
                self::OPTIMIZED_SIZE_2,
                self::SAVED_BYTES_2,
                self::SAVED_PERCENTAGE_2
            ),
        ];
        yield [
            self::ORIGINAL_SIZE_2,
            self::OPTIMIZED_SIZE_3,
            new MetaDataValueObject(
                self::ORIGINAL_SIZE_2,
                self::OPTIMIZED_SIZE_3,
                self::SAVED_BYTES_3,
                self::SAVED_PERCENTAGE_3
            ),
        ];
        yield [
            self::ORIGINAL_SIZE_2,
            self::OPTIMIZED_SIZE_4,
            new MetaDataValueObject(
                self::ORIGINAL_SIZE_2,
                self::OPTIMIZED_SIZE_4,
                self::SAVED_BYTES_4,
                self::SAVED_PERCENTAGE_4
            ),
        ];
    }

    #[DataProvider('metadataProvider')]
    public function testToValueObject(int $originalSize, int $optimizedSize, MetaDataValueObject $metaDataValueObject): void
    {
        $metaData = new MetaData($originalSize, $optimizedSize);
        $result = $metaData->toValueObject();

        Assert::assertSame($metaDataValueObject->getOriginalSize(), $result->getOriginalSize());
        Assert::assertSame($metaDataValueObject->getOptimizedSize(), $result->getOptimizedSize());
        Assert::assertSame($metaDataValueObject->getSavedBytes(), $result->getSavedBytes());
        Assert::assertSame($metaDataValueObject->getSavedPercentage(), $result->getSavedPercentage());
    }
}
