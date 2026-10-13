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
 * @internal
 *
 * @no-named-arguments
 */
final class StderrStream extends AbstractStream implements StreamInterface
{
    /**
     * @throws \RuntimeException If unable to open the stderr stream
     */
    public function __construct()
    {
        $stream = fopen('php://stderr', 'w');

        if (!\is_resource($stream)) {
            throw new \RuntimeException('Unable to open stderr stream.');
        }

        $this->stream = $stream;
    }

    public function __destruct()
    {
        if (\is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
