<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Model;

/**
 * @no-named-arguments
 */
final class MetaDataAggregator
{
    private int $totalOriginalSize = 0;

    private int $totalOptimizedSize = 0;

    private int $optimizedFileCount = 0;

    private float $totalOptimizationTime = 0.0;

    public function addFileData(int $originalSize, int $optimizedSize, float $optimizationTime): void
    {
        $this->totalOriginalSize += $originalSize;
        $this->totalOptimizedSize += $optimizedSize;
        $this->totalOptimizationTime += $optimizationTime;
        ++$this->optimizedFileCount;
    }

    /**
     * @return int The total original size in bytes
     */
    public function getTotalOriginalSize(): int
    {
        return $this->totalOriginalSize;
    }

    /**
     * @return int The total optimized size in bytes
     */
    public function getTotalOptimizedSize(): int
    {
        return $this->totalOptimizedSize;
    }

    /**
     * @return float The percentage of bytes saved
     */
    public function getSavedPercentage(): float
    {
        if (0 === $this->totalOriginalSize) {
            return 0.0;
        }

        return ($this->getSavedBytes() / $this->totalOriginalSize) * 100;
    }

    /**
     * @return int The total bytes saved
     */
    public function getSavedBytes(): int
    {
        return $this->totalOriginalSize - $this->totalOptimizedSize;
    }

    /**
     * @return float Time in seconds
     */
    public function getOptimizationTime(): float
    {
        return $this->totalOptimizationTime;
    }

    public function getOptimizedFileCount(): int
    {
        return $this->optimizedFileCount;
    }

    /**
     * @return bool True if one or more files were optimized, false otherwise
     */
    public function hasOptimizedFiles(): bool
    {
        return $this->optimizedFileCount > 0;
    }

    /**
     * @return bool True if one or more bytes were saved, false otherwise
     */
    public function hasSavedBytes(): bool
    {
        return $this->getSavedBytes() > 0;
    }
}
