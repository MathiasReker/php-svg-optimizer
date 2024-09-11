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

class MetaData
{
    public function __construct(private int $originalSize, private readonly int $optimizedSize)
    {
        $this->setOriginalSize($originalSize);
    }

    private function setOriginalSize(int $originalSize): void
    {
        if ($this->originalSize <= 0) {
            throw new \InvalidArgumentException('Original size must be greater than 0.');
        }

        $this->originalSize = $originalSize;
    }

    /**
     * Converts the metadata to a value object.
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

    private function calculateSavedBytes(): int
    {
        return $this->originalSize - $this->optimizedSize;
    }

    private function calculateSavedPercentage(): float
    {
        return ($this->calculateSavedBytes() / $this->originalSize) * 100;
    }
}
