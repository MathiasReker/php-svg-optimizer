<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Contract\Console\Output\Stream;

/**
 * @no-named-arguments
 */
interface StreamInterface
{
    /**
     * Write a message to the output stream.
     *
     * @param string $message The message to write
     */
    public function write(string $message): void;

    /**
     * Write a message to the output stream, followed by a newline.
     *
     * @param string $message The message to write
     */
    public function writeln(string $message): void;
}
