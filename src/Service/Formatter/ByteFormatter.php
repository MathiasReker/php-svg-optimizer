<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Formatter;

/**
 * @no-named-arguments
 */
final readonly class ByteFormatter
{
    /**
     * Format a byte value into a human-readable string.
     *
     * @param int $bytes The number of bytes
     *
     * @return string Formatted byte string (e.g., "1.23 MB")
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1_024) {
            return \sprintf('%d B', $bytes);
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1_024 && $i < \count($units)) {
            $bytes /= 1_024;
            ++$i;
        }

        return \sprintf('%.2f %s', $bytes, $units[$i - 1]);
    }
}
