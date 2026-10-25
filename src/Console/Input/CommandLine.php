<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class CommandLine
{
    /**
     * @param list<string>  $paths           the files and directories to optimize
     * @param list<Option>  $flags           the options that were given, each option once
     * @param string        $config          the configuration file or inline JSON, or an empty string if none was given
     * @param list<Rule>    $rules           the rules that --rules selects
     * @param list<Rule>    $excludedRules   the rules that --without-rules leaves out
     * @param list<RuleSet> $ruleSets        the rule sets that --sets selects, without "default"
     * @param bool          $defaultSelected whether --sets includes "default"
     */
    public function __construct(
        private array $paths,
        private array $flags,
        private string $config,
        private array $rules,
        private array $excludedRules,
        private array $ruleSets,
        private bool $defaultSelected,
    ) {}

    /**
     * @return list<string>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return string the configuration file or inline JSON, or an empty string if none was given
     */
    public function getConfig(): string
    {
        return $this->config;
    }

    /**
     * @return list<Rule>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return list<Rule>
     */
    public function getExcludedRules(): array
    {
        return $this->excludedRules;
    }

    /**
     * @return list<RuleSet>
     */
    public function getRuleSets(): array
    {
        return $this->ruleSets;
    }

    public function isDefaultSelected(): bool
    {
        return $this->defaultSelected;
    }

    public function isRiskyAllowed(): bool
    {
        return $this->has(Option::AllowRisky);
    }

    public function isDryRun(): bool
    {
        return $this->has(Option::DryRun);
    }

    public function isCheckRequested(): bool
    {
        return $this->has(Option::Check);
    }

    public function isRuleListRequested(): bool
    {
        return $this->has(Option::ListRules);
    }

    public function isQuiet(): bool
    {
        return $this->has(Option::Quiet);
    }

    public function isVersionRequested(): bool
    {
        return $this->has(Option::Version);
    }

    public function isHelpRequested(): bool
    {
        return $this->has(Option::Help);
    }

    public function isColorForced(): bool
    {
        return $this->has(Option::Ansi);
    }

    public function isColorDisabled(): bool
    {
        return $this->has(Option::NoAnsi);
    }

    private function has(Option $option): bool
    {
        return \in_array($option, $this->flags, true);
    }
}
