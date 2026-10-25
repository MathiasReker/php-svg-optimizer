<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Output;

/**
 * @internal
 *
 * @no-named-arguments
 */
interface StreamInterface
{
    /**
     * @param string $message the message to write
     */
    public function write(string $message): void;

    /**
     * @param string $message the message to write, followed by a line break
     */
    public function writeLine(string $message): void;

    /**
     * @return bool true if the stream supports ANSI color output
     */
    public function hasColorSupport(): bool;
}
