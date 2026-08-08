<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\ValueObject;

/**
 * @no-named-arguments
 */
final readonly class Metrics
{
    /**
     * @param int   $originalSize     The original size of the SVG file in bytes
     * @param int   $optimizedSize    The optimized size of the SVG file in bytes
     * @param int   $savedBytes       The number of bytes saved through optimization
     * @param float $savedPercentage  The percentage of bytes saved through optimization
     * @param float $optimizationTime The time it took to optimize the SVG file in seconds
     */
    public function __construct(
        private int $originalSize,
        private int $optimizedSize,
        private int $savedBytes,
        private float $savedPercentage,
        private float $optimizationTime,
    ) {}

    /**
     * @return int The original size of the SVG file in bytes
     */
    public function getOriginalSize(): int
    {
        return $this->originalSize;
    }

    /**
     * @return int The optimized size of the SVG file in bytes
     */
    public function getOptimizedSize(): int
    {
        return $this->optimizedSize;
    }

    /**
     * @return int The number of bytes saved through optimization
     */
    public function getSavedBytes(): int
    {
        return $this->savedBytes;
    }

    /**
     * @return float The percentage of bytes saved through optimization
     */
    public function getSavedPercentage(): float
    {
        return $this->savedPercentage;
    }

    /**
     * @return float The time it took to optimize the SVG file in seconds
     */
    public function getOptimizationTime(): float
    {
        return $this->optimizationTime;
    }
}
