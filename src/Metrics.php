<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class Metrics
{
    private const int PERCENT = 100;

    /**
     * @param int   $originalSizeInBytes  the size of the original SVG
     * @param int   $optimizedSizeInBytes the size of the optimized SVG
     * @param float $durationInSeconds    the time the optimization took
     *
     * @internal
     */
    public function __construct(
        private int $originalSizeInBytes,
        private int $optimizedSizeInBytes,
        private float $durationInSeconds,
    ) {}

    public function getOriginalSizeInBytes(): int
    {
        return $this->originalSizeInBytes;
    }

    public function getOptimizedSizeInBytes(): int
    {
        return $this->optimizedSizeInBytes;
    }

    /**
     * @return int the original size minus the optimized size, negative if the optimized SVG is larger
     */
    public function getSavedSizeInBytes(): int
    {
        return $this->originalSizeInBytes - $this->optimizedSizeInBytes;
    }

    /**
     * @return float the saved size as a percentage of the original size, from 0 to 100 when the SVG got smaller
     */
    public function getSavedPercentage(): float
    {
        return 0 === $this->originalSizeInBytes ? 0.0 : $this->getSavedSizeInBytes() / $this->originalSizeInBytes * self::PERCENT;
    }

    public function getDurationInSeconds(): float
    {
        return $this->durationInSeconds;
    }

    /**
     * @return bool true if the optimized SVG is smaller than the original
     */
    public function hasSavings(): bool
    {
        return $this->getSavedSizeInBytes() > 0;
    }
}
