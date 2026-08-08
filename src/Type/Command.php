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
    case Process = 'process';

    public function getTitle(): string
    {
        return match ($this->value) {
            self::Process->value => 'Process',
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            self::Process->value => 'Provide a list of directories or files to process.',
        };
    }
}
