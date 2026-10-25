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
final readonly class NullStream implements StreamInterface
{
    /**
     * @param string $message the message to discard
     */
    #[\Override]
    public function write(string $message): void
    {
    }

    /**
     * @param string $message the message to discard
     */
    #[\Override]
    public function writeLine(string $message): void
    {
    }

    #[\Override]
    public function hasColorSupport(): bool
    {
        return false;
    }
}
