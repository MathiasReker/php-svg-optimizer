<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Enums;

enum Command: string
{
    case PROCESS = 'process';

    /**
     * Get the shorthand for the option.
     */
    public function getTitle(): string
    {
        return match ($this) {
            self::PROCESS => 'Process',
        };
    }

    /**
     * Get the description for the option.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PROCESS => 'Provide a list of directories or files to process.',
        };
    }
}
