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
        $aggregator = new MetaDataAggregator();

        self::assertSame(0, $aggregator->getTotalOriginalSize());
        self::assertSame(0, $aggregator->getTotalOptimizedSize());
        self::assertSame(0, $aggregator->getSavedBytes());
        self::assertSame(0.0, $aggregator->getSavedPercentage());
    }

    public function testAddFileDataAccumulatesSizes(): void
    {
        $aggregator = new MetaDataAggregator();

        $aggregator->addFileData(1_000, 800);
        $aggregator->addFileData(500, 400);

        self::assertSame(1_500, $aggregator->getTotalOriginalSize());
        self::assertSame(1_200, $aggregator->getTotalOptimizedSize());
        self::assertSame(300, $aggregator->getSavedBytes());
        self::assertSame(20.0, $aggregator->getSavedPercentage());
    }

    public function testGetSavedPercentageHandlesZeroDivision(): void
    {
        $aggregator = new MetaDataAggregator();

        self::assertSame(0.0, $aggregator->getSavedPercentage());
    }

    public function testOptimizedFileCountIncrementsCorrectly(): void
    {
        $aggregator = new MetaDataAggregator();

        self::assertSame(0, $aggregator->getOptimizedFileCount());

        $aggregator->addFileData(1_000, 800);
        self::assertSame(1, $aggregator->getOptimizedFileCount());

        $aggregator->addFileData(500, 400);
        self::assertSame(2, $aggregator->getOptimizedFileCount());
    }
}
