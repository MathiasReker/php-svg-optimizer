<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\ValueObject;

/**
 * @no-named-arguments
 */
final readonly class CommandOptionsValueObject
{
    /**
     * Constructor of CommandOptionsValueObject.
     *
     * @param bool   $dryRun       if true, the command will run in dry-run mode, calculating potential changes without modifying files
     * @param string $configPath   Path to a JSON configuration file defining custom optimization rules. Ignored if not provided.
     * @param bool   $allowRisky   Whether risky optimization rules are allowed. Risky rules may change the visual output of SVGs.
     * @param bool   $withAllRules If true, all available optimization rules will be applied. Non-risky rules only unless $allowRisky is also true.
     */
    public function __construct(
        private bool $dryRun,
        private string $configPath,
        private bool $allowRisky,
        private bool $withAllRules,
    ) {}

    /**
     * Check if the command is set to run in dry-run mode.
     *
     * @return bool true if dry-run is enabled; false otherwise
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Get the path to the configuration file.
     *
     * @return string the configuration file path
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * Determines whether risky optimization rules are allowed.
     *
     * Risky rules may change the visual output of SVGs.
     *
     * @return bool true if risky rules are allowed; false otherwise
     */
    public function allowRisky(): bool
    {
        return $this->allowRisky;
    }

    /**
     * Determines if all available optimization rules should be applied.
     *
     * If true, all rules will be applied. Risky rules will only be applied if `allowRisky()` is true.
     *
     * @return bool true if all rules should be applied, false otherwise
     */
    public function withAllRules(): bool
    {
        return $this->withAllRules;
    }
}
