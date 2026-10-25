<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Output;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ByteSizeFormatter
{
    private const int UNIT_SIZE = 1_024;

    private const array UNITS = ['KB', 'MB', 'GB', 'TB'];

    /**
     * @param int $bytes the size in bytes, negative if a size shrank
     *
     * @return string the size with its unit, such as "1.23 MB"
     */
    public static function format(int $bytes): string
    {
        if ($bytes < 0) {
            return '-' . self::format(-$bytes);
        }

        if ($bytes < self::UNIT_SIZE) {
            return \sprintf('%d B', $bytes);
        }

        $size = $bytes;
        $unit = 0;

        while ($size >= self::UNIT_SIZE && $unit < \count(self::UNITS)) {
            $size /= self::UNIT_SIZE;
            ++$unit;
        }

        return \sprintf('%.2f %s', $size, self::UNITS[$unit - 1]);
    }
}
