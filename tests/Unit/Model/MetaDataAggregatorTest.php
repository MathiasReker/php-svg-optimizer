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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MetaDataAggregator::class)]
final class MetaDataAggregatorTest extends TestCase
{
    #[Test]
    public function initialStateIsZero(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        self::assertSame(0, $metaDataAggregator->getTotalOriginalSize());
        self::assertSame(0, $metaDataAggregator->getTotalOptimizedSize());
        self::assertSame(0, $metaDataAggregator->getSavedBytes());
        self::assertSame(0.0, $metaDataAggregator->getSavedPercentage());
    }

    #[Test]
    public function addFileDataAccumulatesSizes(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        $metaDataAggregator->addFileData(1_000, 800, 0.1);
        $metaDataAggregator->addFileData(500, 400, 0.1);

        self::assertSame(1_500, $metaDataAggregator->getTotalOriginalSize());
        self::assertSame(1_200, $metaDataAggregator->getTotalOptimizedSize());
        self::assertSame(300, $metaDataAggregator->getSavedBytes());
        self::assertSame(20.0, $metaDataAggregator->getSavedPercentage());
    }

    #[Test]
    public function getSavedPercentageHandlesZeroDivision(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        self::assertSame(0.0, $metaDataAggregator->getSavedPercentage());
    }

    #[Test]
    public function optimizedFileCountIncrementsCorrectly(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        self::assertSame(0, $metaDataAggregator->getOptimizedFileCount());

        $metaDataAggregator->addFileData(1_000, 800, 0.1);
        self::assertSame(1, $metaDataAggregator->getOptimizedFileCount());

        $metaDataAggregator->addFileData(500, 400, 0.1);
        self::assertSame(2, $metaDataAggregator->getOptimizedFileCount());
    }

    #[Test]
    public function hasOptimizedFilesReturnsFalseInitially(): void
    {
        $metaDataAggregator = new MetaDataAggregator();
        self::assertFalse($metaDataAggregator->hasOptimizedFiles());
    }

    #[Test]
    public function hasOptimizedFilesReturnsTrueAfterAddingFileData(): void
    {
        $metaDataAggregator = new MetaDataAggregator();
        $metaDataAggregator->addFileData(1_000, 800, 0.1);

        self::assertTrue($metaDataAggregator->hasOptimizedFiles());
    }

    #[Test]
    public function noSavingsResultsInZeroSavedBytesAndPercentage(): void
    {
        $metaDataAggregator = new MetaDataAggregator();
        $metaDataAggregator->addFileData(500, 500, 0.1);

        self::assertSame(0, $metaDataAggregator->getSavedBytes());
        self::assertSame(0.0, $metaDataAggregator->getSavedPercentage());
    }

    #[Test]
    public function multipleFilesCalculateSavedPercentageCorrectly(): void
    {
        $metaDataAggregator = new MetaDataAggregator();
        $metaDataAggregator->addFileData(1_000, 800, 0.1);
        $metaDataAggregator->addFileData(2_000, 1_500, 0.1);

        self::assertSame(3_000, $metaDataAggregator->getTotalOriginalSize());
        self::assertSame(2_300, $metaDataAggregator->getTotalOptimizedSize());
        self::assertSame(700, $metaDataAggregator->getSavedBytes());
        self::assertSame(23.333_333_333_333_332, $metaDataAggregator->getSavedPercentage());
    }

    #[Test]
    public function addFileDataWithNegativeValues(): void
    {
        $metaDataAggregator = new MetaDataAggregator();
        $metaDataAggregator->addFileData(-100, -50, -0.1);

        self::assertSame(-100, $metaDataAggregator->getTotalOriginalSize());
        self::assertSame(-50, $metaDataAggregator->getTotalOptimizedSize());
        self::assertSame(-50, $metaDataAggregator->getSavedBytes());
    }

    #[Test]
    public function initialOptimizationTimeIsZero(): void
    {
        $metaDataAggregator = new MetaDataAggregator();
        self::assertSame(0.0, $metaDataAggregator->getOptimizationTime());
    }

    #[Test]
    public function addFileDataAccumulatesOptimizationTime(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        $metaDataAggregator->addFileData(1_000, 800, 0.123_456);
        $metaDataAggregator->addFileData(500, 400, 0.654_321);

        self::assertSame(0.777_777, $metaDataAggregator->getOptimizationTime());
    }

    #[Test]
    public function addFileDataHandlesZeroOptimizationTime(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        $metaDataAggregator->addFileData(1_000, 800, 0.0);
        self::assertSame(0.0, $metaDataAggregator->getOptimizationTime());
    }

    #[Test]
    public function multipleFilesSumOptimizationTimeCorrectly(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        $metaDataAggregator->addFileData(1_000, 900, 0.1);
        $metaDataAggregator->addFileData(500, 400, 0.2);
        $metaDataAggregator->addFileData(200, 150, 0.05);

        self::assertSame(0.35, round($metaDataAggregator->getOptimizationTime(), 2));
    }

    #[Test]
    public function negativeOptimizationTime(): void
    {
        $metaDataAggregator = new MetaDataAggregator();

        $metaDataAggregator->addFileData(1_000, 900, -0.1);
        self::assertSame(-0.1, $metaDataAggregator->getOptimizationTime());
    }

    #[Test]
    public function hasSavedBytesReturnsTrueWhenBytesAreSaved(): void
    {
        $metaDataAggregator = new MetaDataAggregator();
        $metaDataAggregator->addFileData(1_000, 800, 0.1);
        self::assertTrue($metaDataAggregator->hasSavedBytes());
    }

    #[Test]
    public function hasSavedBytesReturnsFalseWhenNoBytesAreSaved(): void
    {
        $metaDataAggregator = new MetaDataAggregator();
        $metaDataAggregator->addFileData(1_000, 1_000, 0.1);
        self::assertFalse($metaDataAggregator->hasSavedBytes());
    }
}
