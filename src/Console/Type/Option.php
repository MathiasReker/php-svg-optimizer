<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Type;

use MathiasReker\PhpSvgOptimizer\RuleSet;

/**
 * @internal
 *
 * @no-named-arguments
 */
enum Option: string
{
    case Help = 'help';

    case Config = 'config';

    case DryRun = 'dry-run';

    case AllowRisky = 'allow-risky';

    case WithAllRules = 'with-all-rules';

    case Set = 'set';

    case Quiet = 'quiet';

    case Version = 'version';

    case Ansi = 'ansi';

    case NoAnsi = 'no-ansi';

    public function getShorthand(): string
    {
        return match ($this->value) {
            self::Help->value => '-h',
            self::Config->value => '-c',
            self::DryRun->value => '-d',
            self::AllowRisky->value => '-r',
            self::WithAllRules->value => '-a',
            self::Set->value => '-s',
            self::Quiet->value => '-q',
            self::Version->value => '-v',
            self::Ansi->value, self::NoAnsi->value => '',
        };
    }

    public function getFull(): string
    {
        return match ($this->value) {
            self::Help->value => '--help',
            self::Config->value => '--config',
            self::DryRun->value => '--dry-run',
            self::AllowRisky->value => '--allow-risky',
            self::WithAllRules->value => '--with-all-rules',
            self::Set->value => '--set',
            self::Quiet->value => '--quiet',
            self::Version->value => '--version',
            self::Ansi->value => '--ansi',
            self::NoAnsi->value => '--no-ansi',
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            self::Help->value => 'Display help for the command.',
            self::Config->value => 'Path to a JSON file, or a raw JSON string, with the optimization rules to enable. Without --config, --set and --with-all-rules, no rules are applied.',
            self::DryRun->value => 'Only calculate potential savings without modifying the files.',
            self::AllowRisky->value => 'Explicitly enables risky rules, allowing them to be applied.',
            self::WithAllRules->value => 'Enable all non-risky rules. Use --allow-risky to include risky rules as well.',
            self::Set->value => \sprintf('Comma-separated rule sets to enable (%s). Can be repeated. Use --allow-risky to include their risky rules as well.', implode(', ', array_column(RuleSet::cases(), 'value'))),
            self::Quiet->value => 'Suppress all output except errors.',
            self::Version->value => 'Display the version of the library.',
            self::Ansi->value => 'Force ANSI color output, even when output is not an interactive terminal.',
            self::NoAnsi->value => 'Disable ANSI color output.',
        };
    }
}
