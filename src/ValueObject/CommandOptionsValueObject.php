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
     * @param bool   $quiet      Indicates if the command should run quietly
     * @param string $configPath The path to the configuration file
     */
    public function __construct(
        public bool $dryRun,
        public bool $quiet,
        public string $configPath,
    ) {}
}
