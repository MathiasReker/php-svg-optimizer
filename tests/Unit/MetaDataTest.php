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
    public static function metadataProvider(): \Iterator
    {
        yield [
            1000,
            500,
            new MetaDataValueObject(1000, 500, 500, 50.0),
        ];
        yield [
            1000,
            1000,
            new MetaDataValueObject(1000, 1000, 0, 0.0),
        ];
        yield [
            1,
            0,
            new MetaDataValueObject(1, 0, 1, 100.0),
        ];
        yield [
            1,
            500,
            new MetaDataValueObject(1, 500, -499, -49900.0),
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
