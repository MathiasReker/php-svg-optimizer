<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Data;

use MathiasReker\PhpSvgOptimizer\ValueObject\Metrics;

/**
 * @no-named-arguments
 */
final readonly class MetaData
{
    /**
     * @param int   $originalSize     The original size of the SVG file in bytes
     * @param int   $optimizedSize    The optimized size of the SVG file in bytes
     * @param float $optimizationTime The time it took to optimize the SVG file in seconds
     *
     * @throws \InvalidArgumentException If the original size is less than or equal to 0
     */
    public function __construct(
        private int $originalSize,
        private int $optimizedSize,
        private float $optimizationTime,
    ) {
        if ($this->originalSize <= 0) {
            throw new \InvalidArgumentException(\sprintf('Original size must be greater than 0. Given: %d', $this->originalSize));
        }
    }

    /**
     * @return Metrics The value object representing the metadata
     */
    public function toValueObject(): Metrics
    {
        return new Metrics(
            $this->originalSize,
            $this->optimizedSize,
            $this->calculateSavedBytes(),
            $this->calculateSavedPercentage(),
            $this->optimizationTime,
        );
    }

    /**
     * @return int The number of bytes saved
     */
    private function calculateSavedBytes(): int
    {
        return $this->originalSize - $this->optimizedSize;
    }

    /**
     * @return float The percentage of bytes saved
     */
    private function calculateSavedPercentage(): float
    {
        return ($this->calculateSavedBytes() / $this->originalSize) * 100;
    }
}
