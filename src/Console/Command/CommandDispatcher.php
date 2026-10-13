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
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StderrStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StdoutStream;
use MathiasReker\PhpSvgOptimizer\Console\Type\Application;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandOption;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Output\Stream\StreamInterface;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class CommandDispatcher
{
    /**
     * @param array<int, string> $argv         The command line arguments
     * @param StreamInterface    $outputStream The stream for regular output
     * @param StreamInterface    $errorStream  The stream for errors
     */
    private function __construct(
        private array $argv,
        private StreamInterface $outputStream,
        private StreamInterface $errorStream,
    ) {}

    /**
     * @param array<int, string> $argv The command line arguments
     *
     * @throws \RuntimeException If the standard output or error stream cannot be opened
     */
    public static function fromArgs(array $argv): self
    {
        return new self($argv, new StdoutStream(), new StderrStream());
    }

    /**
     * @param array<int, string> $argv         The command line arguments
     * @param StreamInterface    $outputStream The stream for regular output
     * @param StreamInterface    $errorStream  The stream for errors
     */
    public static function withStreams(array $argv, StreamInterface $outputStream, StreamInterface $errorStream): self
    {
        return new self($argv, $outputStream, $errorStream);
    }

    /**
     * @return int The exit code of the command line tool
     *
     * @throws \LogicException
     */
    public function run(): int
    {
        $argumentParser = new ArgumentParser($this->argv);
        $optionIntent = new OptionIntent($argumentParser);
        $stream = $optionIntent->isQuiet() ? new SilentStream() : $this->outputStream;
        $colorEnabled = match (true) {
            $optionIntent->forceNoColor() => false,
            $optionIntent->forceColor() => true,
            default => $stream->supportsColor(),
        };
        $outputManager = new OutputManager($stream, $colorEnabled, $this->errorStream);

        try {
            $argumentParser->validateOptions();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $outputManager->printError($invalidArgumentException->getMessage());

            return Command::FAILURE;
        }

        if ($argumentParser->isEmpty() || $optionIntent->isHelp()) {
            $outputManager->printHelp();

            return Command::SUCCESS;
        }

        if ($optionIntent->isVersion()) {
            $outputManager->printVersion(Application::Name->value, Application::Version->value, Application::Author->value);

            return Command::SUCCESS;
        }

        try {
            $commandOption = new CommandOption(
                $optionIntent->isDryRun(),
                $optionIntent->getConfigPath(),
                $optionIntent->allowRisky(),
                $optionIntent->withAllRules(),
            );

            return (new CommandFactory($stream, $argumentParser, $colorEnabled, $this->errorStream))
                ->create($commandOption)
                ->run();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $outputManager->printError($invalidArgumentException->getMessage());

            return Command::FAILURE;
        }
    }
}
