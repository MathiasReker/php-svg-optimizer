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
     * @param ArgumentParser $argumentParser the argument parser to check for options
     */
    public function __construct(
        private ArgumentParser $argumentParser,
    ) {}

    /**
     * @return bool true if the dry-run option is present, false otherwise
     */
    public function isDryRun(): bool
    {
        return $this->argumentParser->hasOption(Option::DryRun);
    }

    /**
     * @return bool true if the quiet option is present, false otherwise
     */
    public function isQuiet(): bool
    {
        return $this->argumentParser->hasOption(Option::Quiet);
    }

    /**
     * @return bool true if the help option is present, false otherwise
     */
    public function isHelp(): bool
    {
        return $this->argumentParser->hasOption(Option::Help);
    }

    /**
     * @return bool true if the version option is present, false otherwise
     */
    public function isVersion(): bool
    {
        return $this->argumentParser->hasOption(Option::Version);
    }

    /**
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
     * @return bool true if risky rules are allowed, false otherwise
     */
    public function allowRisky(): bool
    {
        return $this->argumentParser->hasOption(Option::AllowRisky);
    }

    /**
     * @return bool true if all optimization rules should be applied; false otherwise
     */
    public function withAllRules(): bool
    {
        return $this->argumentParser->hasOption(Option::WithAllRules);
    }
}
