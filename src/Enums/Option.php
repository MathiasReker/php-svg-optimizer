<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Enums;

enum Option: string
{
    case HELP = 'help';
    case CONFIG = 'config';
    case DRY_RUN = 'dry-run';
    case QUIET = 'quiet';
    case VERSION = 'version';

    /**
     * Get the shorthand for the option.
     */
    public function getShorthand(): string
    {
        return match ($this) {
            self::HELP => '-h',
            self::CONFIG => '-c',
            self::DRY_RUN => '-d',
            self::QUIET => '-q',
            self::VERSION => '-v',
        };
    }

    /**
     * Get the full name for the option.
     */
    public function getFull(): string
    {
        return match ($this) {
            self::HELP => '--help',
            self::CONFIG => '--config',
            self::DRY_RUN => '--dry-run',
            self::QUIET => '--quiet',
            self::VERSION => '--version',
        };
    }

    /**
     * Get the description for the option.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::HELP => 'Display help for the command.',
            self::CONFIG => 'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.',
            self::DRY_RUN => 'Only calculate potential savings without modifying the files.',
            self::QUIET => 'Suppress all output except errors.',
            self::VERSION => 'Display the version of the library.',
        };
    }
}
