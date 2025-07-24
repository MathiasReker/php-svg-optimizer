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
class StdoutStream extends AbstractStream implements StreamInterface
{
    /**
     * Constructor for StdoutStream.
     *
     * @throws \RuntimeException If unable to open the stdout stream
     */
    public function __construct()
    {
        $stream = fopen('php://stdout', 'w');

        if (false === $stream) {
            throw new \RuntimeException('Unable to open stdout stream.');
        }

        $this->stream = $stream;
    }

    /**
     * Close the stream resource when the object is destroyed.
     */
    public function __destruct()
    {
        if (\is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
