<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Double;

use MathiasReker\PhpSvgOptimizer\Console\Output\StreamInterface;

/**
 * @internal
 *
 * @no-named-arguments
 */
final class MemoryStream implements StreamInterface
{
    private string $content = '';

    /**
     * @param bool $colorSupport whether the stream reports ANSI color support
     */
    public function __construct(
        private readonly bool $colorSupport,
    ) {}

    #[\Override]
    public function write(string $message): void
    {
        $this->content .= $message;
    }

    #[\Override]
    public function writeLine(string $message): void
    {
        $this->write($message . \PHP_EOL);
    }

    #[\Override]
    public function hasColorSupport(): bool
    {
        return $this->colorSupport;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
