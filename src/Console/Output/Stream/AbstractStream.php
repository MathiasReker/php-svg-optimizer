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
abstract class AbstractStream implements StreamInterface
{
    /** @var resource */
    protected $stream;

    /**
     * @param string $message The message to write
     */
    final public function writeln(string $message): void
    {
        $this->write(\sprintf('%s%s', $message, \PHP_EOL));
    }

    /**
     * @param string $message The message to write
     */
    final public function write(string $message): void
    {
        fwrite($this->stream, $message);
    }

    /**
     * Auto-detects color support, honoring the same environment variables most CLI
     * tools do: https://no-color.org (opt-out, takes precedence over everything else),
     * https://force-color.org (FORCE_COLOR=0 disables, any other value forces it on),
     * and the BSD/macOS CLICOLOR_FORCE convention (forces it on unless "0").
     *
     * @return bool True if the stream supports ANSI color output, false otherwise
     */
    final public function supportsColor(): bool
    {
        if (false !== getenv('NO_COLOR')) {
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

        return stream_isatty($this->stream);
    }
}
