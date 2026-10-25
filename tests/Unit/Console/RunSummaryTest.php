<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console;

use MathiasReker\PhpSvgOptimizer\Console\RunSummary;
use MathiasReker\PhpSvgOptimizer\Metrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RunSummary::class)]
#[UsesClass(Metrics::class)]
final class RunSummaryTest extends TestCase
{
    #[Test]
    public function startsEmpty(): void
    {
        $runSummary = new RunSummary();

        self::assertFalse($runSummary->hasFiles());
        self::assertFalse($runSummary->hasFailures());
        self::assertSame(0, $runSummary->getFileCount());
        self::assertSame(0, $runSummary->getChangedFileCount());
        self::assertSame(0, $runSummary->getOriginalSizeInBytes());
        self::assertSame(0, $runSummary->getOptimizedSizeInBytes());
        self::assertSame(0, $runSummary->getSavedSizeInBytes());
        self::assertSame(0.0, $runSummary->getSavedPercentage());
        self::assertSame(0.0, $runSummary->getDurationInSeconds());
    }

    #[Test]
    public function addsUpTheFiles(): void
    {
        $runSummary = new RunSummary();

        $runSummary->add(new Metrics(1_000, 600, 0.25));
        $runSummary->add(new Metrics(500, 400, 0.5));

        self::assertTrue($runSummary->hasFiles());
        self::assertSame(2, $runSummary->getFileCount());
        self::assertSame(1_500, $runSummary->getOriginalSizeInBytes());
        self::assertSame(1_000, $runSummary->getOptimizedSizeInBytes());
        self::assertSame(500, $runSummary->getSavedSizeInBytes());
        self::assertEqualsWithDelta(33.333, $runSummary->getSavedPercentage(), 0.001);
        self::assertSame(0.75, $runSummary->getDurationInSeconds());
    }

    #[Test]
    public function reportsANegativePercentageWhenTheFilesGrew(): void
    {
        $runSummary = new RunSummary();

        $runSummary->add(new Metrics(100, 150, 0.0));

        self::assertSame(-50, $runSummary->getSavedSizeInBytes());
        self::assertSame(-50.0, $runSummary->getSavedPercentage());
    }

    #[Test]
    public function countsChangesAndFailures(): void
    {
        $runSummary = new RunSummary();

        $runSummary->addChange();
        $runSummary->addChange();
        $runSummary->addFailure();

        self::assertSame(2, $runSummary->getChangedFileCount());
        self::assertTrue($runSummary->hasFailures());
        self::assertFalse($runSummary->hasFiles());
    }
}
