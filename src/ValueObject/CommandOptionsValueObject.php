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
     * Constructor for CommandOptionsValueObject.
     *
     * @param bool   $dryRun     Indicates if the command should run in dry-run mode
     * @param string $configPath The path to the configuration file
     */
    public function __construct(
        private bool $dryRun,
        private string $configPath,
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
}
