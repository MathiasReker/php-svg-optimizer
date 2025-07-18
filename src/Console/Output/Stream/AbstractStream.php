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
abstract class AbstractStream implements StreamInterface
{
    /**
     * The output stream resource.
     *
     * @var resource
     */
    protected $stream;

    /**
     * Write a message to the output stream, followed by a newline.
     *
     * @param string $message The message to write
     */
    final public function writeln(string $message): void
    {
        $this->write(\sprintf('%s%s', $message, \PHP_EOL));
    }

    /**
     * Write a message to the output stream.
     *
     * @param string $message The message to write
     */
    final public function write(string $message): void
    {
        fwrite($this->stream, $message);
    }
}
