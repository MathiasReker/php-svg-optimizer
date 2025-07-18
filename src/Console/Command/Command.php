<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Output\Helper\OutputHelper;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Command\CommandInterface;
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Service\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;

/**
 * @no-named-arguments
 */
final readonly class Command implements CommandInterface
{
    /**
     * The aggregator for collecting metadata about processed SVG files.
     */
    private MetaDataAggregator $metaDataAggregator;

    /**
     * The processor for handling SVG files.
     */
    private SvgFileProcessor $svgFileProcessor;

    /**
     * Constructor for SvgOptimizerCommand.
     *
     * @param list<string>              $paths
     * @param CommandOptionsValueObject $commandOptions The options for the command
     * @param OutputHelper              $outputHelper   The output helper for displaying messages
     */
    public function __construct(
        private array $paths,
        private CommandOptionsValueObject $commandOptions,
        private OutputHelper $outputHelper,
    ) {
        $this->metaDataAggregator = new MetaDataAggregator();

        $this->validateInputs();
        $this->svgFileProcessor = $this->buildProcessor();
    }

    /**
     * Validates the input paths and configuration options.
     */
    private function validateInputs(): void
    {
        if ([] === $this->paths) {
            $this->outputHelper->printError('No SVG files or directories specified for optimization.');
        }

        foreach ($this->paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                $this->outputHelper->printError(\sprintf('"%s" is not a valid directory or file.', $path));
            }
        }

        $config = trim($this->commandOptions->configPath);
        if ('' !== $config && !is_file($config)) {
            $this->outputHelper->printError(\sprintf('The configuration file "%s" does not exist.', $config));
        }
    }

    /**
     * Builds the SVG file processor with the provided command options and output helper.
     *
     * @return SvgFileProcessor The configured SVG file processor
     */
    private function buildProcessor(): SvgFileProcessor
    {
        return new SvgFileProcessor(
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

        if (!$this->commandOptions->quiet && $this->metaDataAggregator->getOptimizedFileCount() > 0) {
            $this->printSummary();
        }
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
            $this->outputHelper->printError(\sprintf('Failed processing "%s": %s', $path, $exception->getMessage()));
        } catch (\JsonException $jsonException) {
            $this->outputHelper->printError(\sprintf('Invalid JSON in configuration file "%s": %s', $this->commandOptions->configPath, $jsonException->getMessage()));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->outputHelper->printError(\sprintf('Invalid argument for "%s": %s', $path, $invalidArgumentException->getMessage()));
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
