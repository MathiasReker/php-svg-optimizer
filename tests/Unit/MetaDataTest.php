<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit;

use MathiasReker\PhpSvgOptimizer\Services\MetaData;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetaData::class)]
final class MetaDataTest extends TestCase
{
    public static function metadataProvider(): \Iterator
    {
        yield [
            1000,
            500,
            [
                'originalSize' => 1000,
                'optimizedSize' => 500,
                'savedBytes' => 500,
                'savedPercentage' => 50.0,
            ],
        ];
        yield [
            1000,
            1000,
            [
                'originalSize' => 1000,
                'optimizedSize' => 1000,
                'savedBytes' => 0,
                'savedPercentage' => 0.0,
            ],
        ];
        yield [
            0,
            0,
            [
                'originalSize' => 0,
                'optimizedSize' => 0,
                'savedBytes' => 0,
                'savedPercentage' => 0.0,
            ],
        ];
        yield [
            0,
            500,
            [
                'originalSize' => 0,
                'optimizedSize' => 500,
                'savedBytes' => -500,
                'savedPercentage' => 0.0,
            ],
        ];
    }

    /**
     * @param array{originalSize: int, optimizedSize: int, savedBytes: int, savedPercentage: float} $expected
     */
    #[DataProvider('metadataProvider')]
    public function testToArray(int $originalSize, int $optimizedSize, array $expected): void
    {
        $metaData = new MetaData($originalSize, $optimizedSize);
        $result = $metaData->toArray();

        Assert::assertSame($expected['originalSize'], $result['originalSize']);
        Assert::assertSame($expected['optimizedSize'], $result['optimizedSize']);
        Assert::assertSame($expected['savedBytes'], $result['savedBytes']);
        Assert::assertSame($expected['savedPercentage'], $result['savedPercentage']);
    }
}
