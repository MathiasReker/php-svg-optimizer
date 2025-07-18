<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Output\Stream;

use MathiasReker\PhpSvgOptimizer\Contract\Console\Output\Stream\StreamInterface;

/**
 * @no-named-arguments
 */
final class SilentStream implements StreamInterface
{
    /**
     * This stream does not open any resource, as it is silent.
     *
     * @param string $message The message to write
     */
    public function writeln(string $message): void
    {
    }

    /**
     * This stream does not open any resource, as it is silent.
     *
     * @param string $message The message to write
     */
    public function write(string $message): void
    {
    }
}
