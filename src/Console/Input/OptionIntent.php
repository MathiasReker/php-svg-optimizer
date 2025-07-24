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
 * @no-named-arguments
 */
final readonly class OptionIntent
{
    /**
     * Constructor for OptionIntent.
     *
     * @param ArgumentParser $argumentParser The argument parser to check for options
     */
    public function __construct(
        private ArgumentParser $argumentParser,
    ) {}

    /**
     * Check if the given option is present in the command-line arguments.
     *
     * @return bool True if the option is present, false otherwise
     */
    public function isDryRun(): bool
    {
        return $this->argumentParser->hasOption(Option::DRY_RUN);
    }

    /**
     * Check if the quiet option is set.
     *
     * @return bool True if the quiet option is set, false otherwise
     */
    public function isQuiet(): bool
    {
        return $this->argumentParser->hasOption(Option::QUIET);
    }

    /**
     * Check if the help option is set.
     *
     * @return bool True if the help option is set, false otherwise
     */
    public function isHelp(): bool
    {
        return $this->argumentParser->hasOption(Option::HELP);
    }

    /**
     * Check if the version option is set.
     *
     * @return bool True if the version option is set, false otherwise
     */
    public function isVersion(): bool
    {
        return $this->argumentParser->hasOption(Option::VERSION);
    }

    /**
     * Get the value of the specified option.
     *
     * @throws \InvalidArgumentException if the option does not exist
     */
    public function getConfigPath(): string
    {
        return $this->argumentParser->hasOption(Option::CONFIG)
            ? $this->argumentParser->getOption(Option::CONFIG)
            : '';
    }
}
