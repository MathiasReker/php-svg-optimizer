<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionIntent;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\SilentStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StdoutStream;
use MathiasReker\PhpSvgOptimizer\Type\Application;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;

/**
 * @no-named-arguments
 */
final readonly class CommandDispatcher
{
    /**
     * Constructor for SvgOptimizerApplication.
     *
     * @param array<int, string> $argv The command line arguments
     */
    private function __construct(
        private array $argv,
    ) {}

    /**
     * Create a new SvgOptimizerApplication instance from command line arguments.
     *
     * @param array<int, string> $argv The command line arguments
     */
    public static function fromArgs(array $argv): self
    {
        return new self($argv);
    }

    /**
     * Run the application.
     *
     * This method processes the command line arguments and executes the appropriate command.
     *
     * @throws \RuntimeException If the application is not run in a CLI environment or if an error occurs during command execution
     */
    public function run(): void
    {
        $parser = new ArgumentParser($this->argv);
        $option = new OptionIntent($parser);
        $stream = $option->isQuiet()
            ? new SilentStream()
            : new StdoutStream();
        $output = new OutputManager($stream);

        if (\PHP_SAPI !== 'cli') {
            $output->printError('This command can only be run in a CLI environment.');
            exit(1);
        }

        if ($parser->isEmpty() || $option->isHelp()) {
            $output->printHelp();
            exit(0);
        }

        if ($option->isVersion()) {
            $output->printVersion(
                Application::NAME->value,
                Application::VERSION->value,
                Application::AUTHOR->value
            );
            exit(0);
        }

        try {
            $options = new CommandOptionsValueObject(
                $option->isDryRun(),
                $option->isQuiet(),
                $option->getConfigPath()
            );

            $command = (new CommandFactory($stream, $parser))->create($options);
            $command->run();
        } catch (\InvalidArgumentException $e) {
            $output->printError($e->getMessage());
            exit(1);
        }
    }
}
