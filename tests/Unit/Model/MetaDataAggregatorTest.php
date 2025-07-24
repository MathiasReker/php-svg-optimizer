<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Model;

use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MetaDataAggregator::class)]
final class MetaDataAggregatorTest extends TestCase
{
    public function testInitialStateIsZero(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        self::assertSame(0, $metaDataAggregator->getTotalOriginalSize());
        self::assertSame(0, $metaDataAggregator->getTotalOptimizedSize());
        self::assertSame(0, $metaDataAggregator->getSavedBytes());
        self::assertSame(0.0, $metaDataAggregator->getSavedPercentage());
    }

    public function testAddFileDataAccumulatesSizes(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        $metaDataAggregator->addFileData(1_000, 800);
        $metaDataAggregator->addFileData(500, 400);

        self::assertSame(1_500, $metaDataAggregator->getTotalOriginalSize());
        self::assertSame(1_200, $metaDataAggregator->getTotalOptimizedSize());
        self::assertSame(300, $metaDataAggregator->getSavedBytes());
        self::assertSame(20.0, $metaDataAggregator->getSavedPercentage());
    }

    public function testGetSavedPercentageHandlesZeroDivision(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        self::assertSame(0.0, $metaDataAggregator->getSavedPercentage());
    }

    public function testOptimizedFileCountIncrementsCorrectly(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        self::assertSame(0, $metaDataAggregator->getOptimizedFileCount());

        $metaDataAggregator->addFileData(1_000, 800);
        self::assertSame(1, $metaDataAggregator->getOptimizedFileCount());

        $metaDataAggregator->addFileData(500, 400);
        self::assertSame(2, $metaDataAggregator->getOptimizedFileCount());
    }
}
