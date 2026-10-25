<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Output\Stream;

/**
 * @internal
 *
 * @no-named-arguments
 */
final class StderrStream extends AbstractStream implements StreamInterface
{
    /**
     * @throws \RuntimeException If the stream cannot be opened
     */
    public function __construct()
    {
        parent::__construct('php://stderr', 'w');
    }
}
