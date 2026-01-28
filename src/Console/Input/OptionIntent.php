<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\Type\Option;

/**
 * Handles command-line options and determines user intent for the SVG optimizer CLI.
 *
 * @no-named-arguments
 */
final readonly class OptionIntent
{
    /**
     * Constructor for OptionIntent.
     *
     * @param ArgumentParser $argumentParser the argument parser to check for options
     */
    public function __construct(
        private ArgumentParser $argumentParser,
    ) {}

    /**
     * Check if the dry-run option is set.
     *
     * When set, the optimizer will only calculate potential savings without modifying files.
     *
     * @return bool true if the dry-run option is present, false otherwise
     */
    public function isDryRun(): bool
    {
        return $this->argumentParser->hasOption(Option::DryRun);
    }

    /**
     * Check if the quiet option is set.
     *
     * When set, suppresses all output except errors.
     *
     * @return bool true if the quiet option is present, false otherwise
     */
    public function isQuiet(): bool
    {
        return $this->argumentParser->hasOption(Option::Quiet);
    }

    /**
     * Check if the help option is set.
     *
     * When set, displays help information for the CLI command.
     *
     * @return bool true if the help option is present, false otherwise
     */
    public function isHelp(): bool
    {
        return $this->argumentParser->hasOption(Option::Help);
    }

    /**
     * Check if the version option is set.
     *
     * When set, displays the version of the php-svg-optimizer library.
     *
     * @return bool true if the version option is present, false otherwise
     */
    public function isVersion(): bool
    {
        return $this->argumentParser->hasOption(Option::Version);
    }

    /**
     * Get the value of the configuration file path option.
     *
     * Allows the user to provide a path to a JSON file with custom optimization rules.
     *
     * @return string the path to the configuration file, or an empty string if not set
     *
     * @throws \InvalidArgumentException
     */
    public function getConfigPath(): string
    {
        return $this->argumentParser->hasOption(Option::Config)
            ? $this->argumentParser->getOption(Option::Config)
            : '';
    }

    /**
     * Check if the allow-risky option is set.
     *
     * Explicitly enables risky rules, allowing them to be applied during SVG optimization.
     *
     * @return bool true if risky rules are allowed, false otherwise
     */
    public function allowRisky(): bool
    {
        return $this->argumentParser->hasOption(Option::AllowRisky);
    }

    /**
     * Checks whether the command should apply all available optimization rules.
     *
     * @return bool true if all optimization rules should be applied; false otherwise
     */
    public function withAllRules(): bool
    {
        return $this->argumentParser->hasOption(Option::WithAllRules);
    }
}
