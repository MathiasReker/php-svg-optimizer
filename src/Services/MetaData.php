<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services;

class MetaData
{
    /**
     * @param int $originalSize  the original size of the SVG file in bytes
     * @param int $optimizedSize the optimized size of the SVG file in bytes
     */
    public function __construct(
        private readonly int $originalSize,
        private readonly int $optimizedSize
    ) {
    }

    /**
     * Converts the metadata to an associative array.
     *
     * @return array{ originalSize: int, optimizedSize: int, savedBytes: int, savedPercentage: float }
     */
    public function toArray(): array
    {
        return [
            'originalSize' => $this->originalSize,
            'optimizedSize' => $this->optimizedSize,
            'savedBytes' => $this->calculateSavedBytes(),
            'savedPercentage' => $this->calculateSavedPercentage(),
        ];
    }

    private function calculateSavedBytes(): int
    {
        return $this->originalSize - $this->optimizedSize;
    }

    private function calculateSavedPercentage(): float
    {
        return $this->originalSize > 0
            ? round(($this->calculateSavedBytes() / $this->originalSize) * 100, 2)
            : 0.0;
    }
}
