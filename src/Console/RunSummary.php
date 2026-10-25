<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console;

use MathiasReker\PhpSvgOptimizer\Metrics;

/**
 * @internal
 *
 * @no-named-arguments
 */
final class RunSummary
{
    private int $originalSizeInBytes = 0;

    private int $optimizedSizeInBytes = 0;

    private int $fileCount = 0;

    private float $durationInSeconds = 0.0;

    private int $changedFileCount = 0;

    private int $failureCount = 0;

    /**
     * @param Metrics $metrics the sizes and the duration of one file
     */
    public function add(Metrics $metrics): void
    {
        $this->originalSizeInBytes += $metrics->getOriginalSizeInBytes();
        $this->optimizedSizeInBytes += $metrics->getOptimizedSizeInBytes();
        $this->durationInSeconds += $metrics->getDurationInSeconds();
        ++$this->fileCount;
    }

    public function addChange(): void
    {
        ++$this->changedFileCount;
    }

    public function addFailure(): void
    {
        ++$this->failureCount;
    }

    public function getOriginalSizeInBytes(): int
    {
        return $this->originalSizeInBytes;
    }

    public function getOptimizedSizeInBytes(): int
    {
        return $this->optimizedSizeInBytes;
    }

    public function getSavedSizeInBytes(): int
    {
        return $this->metrics()->getSavedSizeInBytes();
    }

    /**
     * @return float the saved size as a percentage of the original size, negative if the files grew
     */
    public function getSavedPercentage(): float
    {
        return $this->metrics()->getSavedPercentage();
    }

    public function getDurationInSeconds(): float
    {
        return $this->durationInSeconds;
    }

    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    /**
     * @return int the number of files whose content changed, or with writing disabled would change
     */
    public function getChangedFileCount(): int
    {
        return $this->changedFileCount;
    }

    public function hasFailures(): bool
    {
        return $this->failureCount > 0;
    }

    public function hasFiles(): bool
    {
        return $this->fileCount > 0;
    }

    private function metrics(): Metrics
    {
        return new Metrics($this->originalSizeInBytes, $this->optimizedSizeInBytes, $this->durationInSeconds);
    }
}
