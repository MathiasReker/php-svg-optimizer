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
abstract class AbstractStream implements StreamInterface
{
    /** @var resource */
    protected $stream;

    /**
     * @param string $filename the stream to open
     * @param string $mode     the mode to open the stream in
     *
     * @throws \RuntimeException If the stream cannot be opened
     */
    public function __construct(string $filename, string $mode)
    {
        $stream = fopen($filename, $mode);

        if (!\is_resource($stream)) {
            throw new \RuntimeException(\sprintf('Unable to open the stream "%s".', $filename));
        }

        $this->stream = $stream;
    }

    final public function __destruct()
    {
        if (\is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    /**
     * @param string $message the message to write
     */
    #[\Override]
    final public function write(string $message): void
    {
        fwrite($this->stream, $message);
    }

    /**
     * @param string $message the message to write, followed by a line break
     */
    #[\Override]
    final public function writeLine(string $message): void
    {
        $this->write($message . \PHP_EOL);
    }

    #[\Override]
    final public function hasColorSupport(): bool
    {
        if ('' !== (string) getenv('NO_COLOR')) {
            return false;
        }

        $forceColor = getenv('FORCE_COLOR');
        if (false !== $forceColor) {
            return '0' !== $forceColor;
        }

        $cliColorForce = getenv('CLICOLOR_FORCE');
        if (false !== $cliColorForce && '0' !== $cliColorForce) {
            return true;
        }

        return 'dumb' !== getenv('TERM') && stream_isatty($this->stream);
    }
}
