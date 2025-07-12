<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Output\OutputHelper;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Command\CommandInterface;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Input\Stream\StreamInterface;

/**
 * @no-named-arguments
 */
abstract class AbstractCommandFactory
{
    /**
     * The output stream for the command.
     */
    private StreamInterface $output;

    public function __construct(StreamInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Create a command instance based on the provided arguments.
     *
     * @param array<int, string> $argv The command-line arguments
     *
     * @return CommandInterface The created command instance
     */
    abstract public function create(array $argv): CommandInterface;

    /**
     * Build an output helper instance for the command.
     *
     * @return OutputHelper The output helper instance
     */
    final protected function buildOutputHelper(): OutputHelper
    {
        return new OutputHelper($this->output);
    }

    /**
     * Check if the command is being run in a CLI environment.
     *
     * @return bool True if running in CLI, false otherwise
     */
    final protected function isCli(): bool
    {
        return \PHP_SAPI === 'cli';
    }
}
