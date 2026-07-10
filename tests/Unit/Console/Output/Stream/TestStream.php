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
    public function __construct()
    {
        parent::__construct();

        $this->stream = fopen('php://memory', 'w+');
        if (false === $this->stream) {
            throw new \RuntimeException('Unable to open memory stream.');
        }
    }

    public function getContent(): string
    {
        rewind($this->stream);

        return stream_get_contents($this->stream);
    }
}
