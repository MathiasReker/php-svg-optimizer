<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\RuleSet;

/**
 * @internal
 *
 * @no-named-arguments
 */
enum Option: string
{
    case Config = 'config';

    case Sets = 'sets';

    case Rules = 'rules';

    case WithoutRules = 'without-rules';

    case AllowRisky = 'allow-risky';

    case DryRun = 'dry-run';

    case Check = 'check';

    case ListRules = 'list-rules';

    case Quiet = 'quiet';

    case Version = 'version';

    case Help = 'help';

    case Ansi = 'ansi';

    case NoAnsi = 'no-ansi';

    /**
     * @return string the single character of the short form, or an empty string if the option has none
     */
    public function getShortName(): string
    {
        return match ($this) {
            self::Config => 'c',
            self::Sets => 's',
            self::Rules => 'r',
            self::DryRun => 'd',
            self::Quiet => 'q',
            self::Version => 'V',
            self::Help => 'h',
            self::WithoutRules,
            self::AllowRisky,
            self::Check,
            self::ListRules,
            self::Ansi,
            self::NoAnsi => '',
        };
    }

    /**
     * @return bool true if the option is followed by a value
     */
    public function takesValue(): bool
    {
        return match ($this) {
            self::Config,
            self::Sets,
            self::Rules,
            self::WithoutRules => true,
            self::AllowRisky,
            self::DryRun,
            self::Check,
            self::ListRules,
            self::Quiet,
            self::Version,
            self::Help,
            self::Ansi,
            self::NoAnsi => false,
        };
    }

    /**
     * @return bool true if the option may be given more than once
     */
    public function isRepeatable(): bool
    {
        return self::Config !== $this;
    }

    /**
     * @return string the placeholder of the value in the help, or an empty string if the option takes no value
     */
    public function getValueName(): string
    {
        return match ($this) {
            self::Config => 'file|json',
            self::Sets => 'sets',
            self::Rules,
            self::WithoutRules => 'rules',
            self::AllowRisky,
            self::DryRun,
            self::Check,
            self::ListRules,
            self::Quiet,
            self::Version,
            self::Help,
            self::Ansi,
            self::NoAnsi => '',
        };
    }

    /**
     * @return non-empty-string what the option does
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Config => 'Configuration file or inline JSON. Defaults to svg-optimizer.json in the working directory.',
            self::Sets => \sprintf('Comma-separated rule sets to select (default, %s). Can be repeated.', implode(', ', array_column(RuleSet::cases(), 'value'))),
            self::Rules => 'Comma-separated rules to select. Can be repeated. See --list-rules.',
            self::WithoutRules => 'Comma-separated rules to leave out, even if a set or --rules selects them. Can be repeated.',
            self::AllowRisky => 'Allow risky rules selected by --rules, --sets or the configuration file.',
            self::DryRun => 'Report the savings without writing files.',
            self::Check => 'Write nothing and exit with code 3 if a file would change.',
            self::ListRules => 'List the rules with their set, whether they are risky, and what they do.',
            self::Quiet => 'Suppress all output except errors.',
            self::Version => 'Show the version.',
            self::Help => 'Show this help.',
            self::Ansi => 'Force ANSI color output, even when output is not an interactive terminal.',
            self::NoAnsi => 'Disable ANSI color output.',
        };
    }
}
