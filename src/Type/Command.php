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
enum Command: string
{
    case PROCESS = 'process';

    /**
     * Get the title for the command.
     */
    public function getTitle(): string
    {
        return match ($this->value) {
            self::PROCESS->value => 'Process',
        };
    }

    /**
     * Get the description for the command.
     */
    public function getDescription(): string
    {
        return match ($this->value) {
            self::PROCESS->value => 'Provide a list of directories or files to process.',
        };
    }
}
