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
class StdoutStream extends AbstractStreamOutput
{
    /**
     * @throws \RuntimeException If unable to open the stdout stream.
     */
    public function __construct()
    {
        $stream = fopen('php://stdout', 'w');

        if (false === $stream) {
            throw new \RuntimeException('Unable to open stdout stream.');
        }

        $this->stream = $stream;
    }
}
