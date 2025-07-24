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
        $argumentParser = new ArgumentParser($this->argv);
        $optionIntent = new OptionIntent($argumentParser);
        $stream = $optionIntent->isQuiet()
            ? new SilentStream()
            : new StdoutStream();
        $outputManager = new OutputManager($stream);

        try {
            $argumentParser->validateOptions();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $outputManager->printError($invalidArgumentException->getMessage());
            exit(1);
        }

        if (\PHP_SAPI !== 'cli') {
            $outputManager->printError('This command can only be run in a CLI environment.');
            exit(1);
        }

        if ($argumentParser->isEmpty() || $optionIntent->isHelp()) {
            $outputManager->printHelp();
            exit(0);
        }

        if ($optionIntent->isVersion()) {
            $outputManager->printVersion(
                Application::NAME->value,
                Application::VERSION->value,
                Application::AUTHOR->value
            );
            exit(0);
        }

        try {
            $commandOptionsValueObject = new CommandOptionsValueObject(
                $optionIntent->isDryRun(),
                $optionIntent->getConfigPath()
            );

            $command = (new CommandFactory($stream, $argumentParser))->create($commandOptionsValueObject);
            $command->run();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $outputManager->printError($invalidArgumentException->getMessage());
            exit(1);
        }
    }
}
