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
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Service\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;

/**
 * @no-named-arguments
 */
final class SvgOptimizerCommand implements CommandInterface
{
    /** @var list<string> */
    private readonly array $paths;

    private readonly OutputHelper $outputHelper;

    private MetaDataAggregator $metaDataAggregator;

    private SvgFileProcessor $svgFileProcessor;

    private CommandOptionsValueObject $commandOptions;

    /**
     * Constructor for SvgOptimizerCommand.
     *
     * @param list<string> $paths
     */
    public function __construct(
        array $paths,
        CommandOptionsValueObject $options,
        OutputHelper $outputHelper,
    ) {
        $this->paths = $paths;
        $this->commandOptions = $options;
        $this->outputHelper = $outputHelper;
        $this->metaDataAggregator = new MetaDataAggregator();

        if ([] === $paths) {
            $this->outputHelper->printError('No SVG files or directories specified for optimization.');
        }

        foreach ($paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                $this->outputHelper->printError(\sprintf('"%s" is not a valid directory or file.', $path));
            }
        }

        if ('' !== trim($this->commandOptions->configPath)) {
            if (!is_file($this->commandOptions->configPath)) {
                $this->outputHelper->printError(\sprintf('The configuration file "%s" does not exist.', $this->commandOptions->configPath));
            }
        }

        $this->svgFileProcessor = new SvgFileProcessor(
            new CommandOptionsValueObject(
                $this->commandOptions->dryRun,
                $this->commandOptions->quiet,
                $this->commandOptions->configPath
            ),
            $this->outputHelper,
            $this->metaDataAggregator
        );
    }

    /**
     * Executes the SVG optimization command.
     */
    public function run(): void
    {
        foreach ($this->paths as $path) {
            $this->processPathWithHandling($path);
        }

        $this->printSummaryIfNeeded();
    }

    /**
     * Processes a single path, handling exceptions and errors.
     *
     * @param string $path The path to process, either a file or directory
     */
    private function processPathWithHandling(string $path): void
    {
        try {
            $this->svgFileProcessor->processPath($path);
        } catch (\RuntimeException $exception) {
            $this->printErrorUnlessQuiet(\sprintf('Failed processing "%s": %s', $path, $exception->getMessage()));
        } catch (\JsonException $jsonException) {
            $this->printErrorUnlessQuiet(\sprintf('Invalid JSON in configuration file "%s": %s', $this->commandOptions->configPath, $jsonException->getMessage()));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->printErrorUnlessQuiet(\sprintf('Invalid argument for "%s": %s', $path, $invalidArgumentException->getMessage()));
        }
    }

    /**
     * Prints an error message unless the command is run in quiet mode.
     *
     * @param string $message The error message to print
     */
    private function printErrorUnlessQuiet(string $message): void
    {
        if (!$this->commandOptions->quiet) {
            $this->outputHelper->printError($message);
        }
    }

    /**
     * Prints a summary of the optimization results if there are any optimized files.
     */
    private function printSummaryIfNeeded(): void
    {
        if (!$this->commandOptions->quiet && $this->metaDataAggregator->getOptimizedFileCount() > 0) {
            $this->printSummary();
        }
    }

    /**
     * Prints a summary of the optimization results.
     */
    private function printSummary(): void
    {
        $this->outputHelper->printTotalSummary(
            $this->metaDataAggregator->getOptimizedFileCount(),
            $this->metaDataAggregator->getTotalOriginalSize(),
            $this->metaDataAggregator->getTotalOptimizedSize(),
            $this->metaDataAggregator->getSavedBytes(),
            $this->metaDataAggregator->getSavedPercentage(),
        );
    }
}
