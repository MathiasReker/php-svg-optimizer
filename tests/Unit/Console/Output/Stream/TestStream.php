<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output\Stream;

use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StdoutStream;

final class TestStream extends StdoutStream
{
    /**
     * @throws \RuntimeException
     */
    public function __construct()
    {
        parent::__construct();

        $stream = fopen('php://memory', 'w+');

        if (false === $stream) {
            throw new \RuntimeException('Unable to open memory stream.');
        }

        $this->stream = $stream;
    }

    /**
     * @throws \RuntimeException
     */
    public function getContent(): string
    {
        rewind($this->stream);

        $content = stream_get_contents($this->stream);

        if (false === $content) {
            throw new \RuntimeException('Unable to read memory stream.');
        }

        return $content;
    }
}
