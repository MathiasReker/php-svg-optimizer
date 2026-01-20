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
    case Help = 'help';

    case Config = 'config';

    case DryRun = 'dry-run';

    case AllowRisky = 'allow-risky';

    case WithAllRules = 'with-all-rules';

    case Quiet = 'quiet';

    case Version = 'version';

    /**
     * Get the shorthand for the option.
     */
    public function getShorthand(): string
    {
        return match ($this->value) {
            self::Help->value => '-h',
            self::Config->value => '-c',
            self::DryRun->value => '-d',
            self::AllowRisky->value => '-r',
            self::WithAllRules->value => '-a',
            self::Quiet->value => '-q',
            self::Version->value => '-v',
        };
    }

    /**
     * Get the full name for the option.
     */
    public function getFull(): string
    {
        return match ($this->value) {
            self::Help->value => '--help',
            self::Config->value => '--config',
            self::DryRun->value => '--dry-run',
            self::AllowRisky->value => '--allow-risky',
            self::WithAllRules->value => '--with-all-rules',
            self::Quiet->value => '--quiet',
            self::Version->value => '--version',
        };
    }

    /**
     * Get the description for the option.
     */
    public function getDescription(): string
    {
        return match ($this->value) {
            self::Help->value => 'Display help for the command.',
            self::Config->value => 'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.',
            self::DryRun->value => 'Only calculate potential savings without modifying the files.',
            self::AllowRisky->value => 'Explicitly enables risky rules, allowing them to be applied.',
            self::WithAllRules->value => 'Enable all non-risky rules. Use --allow-risky to include risky rules as well.',
            self::Quiet->value => 'Suppress all output except errors.',
            self::Version->value => 'Display the version of the library.',
        };
    }
}
