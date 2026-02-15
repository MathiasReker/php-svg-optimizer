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
    /**
     * The total original size of all SVG files processed, in bytes.
     */
    private int $totalOriginalSize = 0;

    /**
     * The total optimized size of all SVG files processed, in bytes.
     */
    private int $totalOptimizedSize = 0;

    /**
     * The number of SVG files that have been optimized.
     */
    private int $optimizedFileCount = 0;

    /**
     * The total optimization time for all SVG files processed, in seconds.
     */
    private float $totalOptimizationTime = 0.0;

    /**
     * Constructor for MetaDataAggregator.
     *
     * Initializes the total sizes to zero.
     */
    public function addFileData(int $originalSize, int $optimizedSize, float $optimizationTime): void
    {
        $this->totalOriginalSize += $originalSize;
        $this->totalOptimizedSize += $optimizedSize;
        $this->totalOptimizationTime += $optimizationTime;
        ++$this->optimizedFileCount;
    }

    /**
     * Returns the total original size of all SVG files processed.
     *
     * @return int The total original size in bytes
     */
    public function getTotalOriginalSize(): int
    {
        return $this->totalOriginalSize;
    }

    /**
     * Returns the total optimized size of all SVG files processed.
     *
     * @return int The total optimized size in bytes
     */
    public function getTotalOptimizedSize(): int
    {
        return $this->totalOptimizedSize;
    }

    /**
     * Returns the total percentage of bytes saved through optimization.
     *
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
     * Returns the total number of bytes saved through optimization.
     *
     * @return int The total bytes saved
     */
    public function getSavedBytes(): int
    {
        return $this->totalOriginalSize - $this->totalOptimizedSize;
    }

    /**
     * Returns the total optimization time for all SVG files processed, in seconds.
     *
     * @return float Time in seconds
     */
    public function getOptimizationTime(): float
    {
        return $this->totalOptimizationTime;
    }

    /**
     * The number of SVG files that have been optimized.
     */
    public function getOptimizedFileCount(): int
    {
        return $this->optimizedFileCount;
    }

    /**
     * Determines whether any SVG files were successfully optimized.
     *
     * @return bool True if one or more files were optimized, false otherwise
     */
    public function hasOptimizedFiles(): bool
    {
        return $this->optimizedFileCount > 0;
    }

    /**
     * Determines whether any bytes were saved during optimization.
     *
     * @return bool True if one or more bytes were saved, false otherwise
     */
    public function hasSavedBytes(): bool
    {
        return $this->getSavedBytes() > 0;
    }
}
