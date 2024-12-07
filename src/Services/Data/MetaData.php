<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Data;

use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;

/**
 * Represents metadata about SVG optimization, including original and optimized sizes.
 */
final readonly class MetaData
{
    /**
     * Constant for the percentage factor used in calculations.
     */
    private const int PERCENTAGE_FACTOR = 100;

    /**
     * Constructor for MetaData.
     *
     * @param int $originalSize  The original size of the SVG file in bytes
     * @param int $optimizedSize The optimized size of the SVG file in bytes
     *
     * @throws \InvalidArgumentException If the original size is less than or equal to 0
     */
    public function __construct(
        private int $originalSize,
        private int $optimizedSize
    ) {
        if ($this->originalSize <= 0) {
            throw new \InvalidArgumentException(\sprintf('Original size must be greater than 0. Given: %d', $this->originalSize));
        }
    }

    /**
     * Converts the metadata to a value object.
     *
     * @return MetaDataValueObject The value object representing the metadata
     */
    public function toValueObject(): MetaDataValueObject
    {
        return new MetaDataValueObject(
            $this->originalSize,
            $this->optimizedSize,
            $this->calculateSavedBytes(),
            $this->calculateSavedPercentage()
        );
    }

    /**
     * Calculates the number of bytes saved through optimization.
     *
     * @return int The number of bytes saved
     */
    private function calculateSavedBytes(): int
    {
        return $this->originalSize - $this->optimizedSize;
    }

    /**
     * Calculates the percentage of bytes saved through optimization.
     *
     * @return float The percentage of bytes saved
     */
    private function calculateSavedPercentage(): float
    {
        return ($this->calculateSavedBytes() / $this->originalSize) * self::PERCENTAGE_FACTOR;
    }
}
