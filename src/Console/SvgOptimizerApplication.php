<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console;

use MathiasReker\PhpSvgOptimizer\Console\Command\SvgOptimizerCommandFactory;
use MathiasReker\PhpSvgOptimizer\Console\Input\Stream\StdoutStream;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Input\Stream\StreamInterface;

/**
 * @no-named-arguments
 */
final class SvgOptimizerApplication
{
    /**
     * The command line arguments.
     *
     * @var array<int, string>
     */
    private array $argv;

    /**
     * The output stream for the application.
     */
    private StreamInterface $output;

    /**
     * Constructor for SvgOptimizerApplication.
     *
     * @param array<int, string> $argv The command line arguments
     *
     * @throws \RuntimeException
     */
    private function __construct(array $argv)
    {
        $this->argv = $argv;

        $this->output = new StdoutStream();
    }

    /**
     * Create a new SvgOptimizerApplication instance from command line arguments.
     *
     * @param array<int, string> $argv The command line arguments
     *
     * @throws \RuntimeException
     */
    public static function fromArgs(array $argv): self
    {
        return new self($argv);
    }

    /**
     * Run the application.
     *
     * This method processes the command line arguments and executes the appropriate command.
     */
    public function run(): void
    {
        $factory = new SvgOptimizerCommandFactory($this->output);
        $command = $factory->create($this->argv);
        $command->run();
    }
}
