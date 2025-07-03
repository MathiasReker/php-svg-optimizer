<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Type;

/**
 * @no-named-arguments
 */
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
        return match ($this->value) {
            self::HELP->value => '-h',
            self::CONFIG->value => '-c',
            self::DRY_RUN->value => '-d',
            self::QUIET->value => '-q',
            self::VERSION->value => '-v',
        };
    }

    /**
     * Get the full name for the option.
     */
    public function getFull(): string
    {
        return match ($this->value) {
            self::HELP->value => '--help',
            self::CONFIG->value => '--config',
            self::DRY_RUN->value => '--dry-run',
            self::QUIET->value => '--quiet',
            self::VERSION->value => '--version',
        };
    }

    /**
     * Get the description for the option.
     */
    public function getDescription(): string
    {
        return match ($this->value) {
            self::HELP->value => 'Display help for the command.',
            self::CONFIG->value => 'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.',
            self::DRY_RUN->value => 'Only calculate potential savings without modifying the files.',
            self::QUIET->value => 'Suppress all output except errors.',
            self::VERSION->value => 'Display the version of the library.',
        };
    }
}
