<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Models;

/**
 * Represents metadata about SVG optimization.
 */
readonly class MetaDataValueObject
{
    public function __construct(private int $originalSize, private int $optimizedSize, private int $savedBytes, private float $savedPercentage)
    {
    }

    public function getOriginalSize(): int
    {
        return $this->originalSize;
    }

    public function getOptimizedSize(): int
    {
        return $this->optimizedSize;
    }

    public function getSavedBytes(): int
    {
        return $this->savedBytes;
    }

    public function getSavedPercentage(): float
    {
        return $this->savedPercentage;
    }
}
