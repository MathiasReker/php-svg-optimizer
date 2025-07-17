<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input\Stream;

/**
 * @no-named-arguments
 */
class MemoryStream extends AbstractStreamOutput
{
    /**
     * Constructor for MemoryStream.
     *
     * @throws \RuntimeException If unable to open the memory stream
     */
    public function __construct()
    {
        $stream = fopen('php://memory', 'w+');

        if (false === $stream) {
            throw new \RuntimeException('Unable to open memory stream.');
        }

        $this->stream = $stream;
    }

    /**
     * Write a message to the memory stream.
     */
    public function getContent(): string
    {
        rewind($this->stream);

        return stream_get_contents($this->stream);
    }
}
