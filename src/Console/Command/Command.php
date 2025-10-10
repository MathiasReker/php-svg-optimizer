<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
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
     * @param list<string>              $paths                     The paths to SVG files or directories to process
     * @param CommandOptionsValueObject $commandOptionsValueObject The options for the command
     * @param OutputManager             $outputManager             The output helper for displaying messages
     */
    public function __construct(
        private array $paths,
        private CommandOptionsValueObject $commandOptionsValueObject,
        private OutputManager $outputManager,
    ) {
        $this->metaDataAggregator = new MetaDataAggregator();
        $this->svgFileProcessor = $this->buildProcessor();
    }

    /**
     * Builds the SVG file processor with the provided command options and output helper.
     *
     * @return SvgFileProcessor The configured SVG file processor
     */
    private function buildProcessor(): SvgFileProcessor
    {
        return new SvgFileProcessor(
            $this->commandOptionsValueObject,
            $this->outputManager,
            $this->metaDataAggregator
        );
    }

    /**
     * Executes the SVG optimization command.
     *
     * @throws \LogicException
     * @throws \ValueError
     */
    public function run(): void
    {
        foreach ($this->paths as $path) {
            $this->processPath($path);
        }

        if ($this->metaDataAggregator->getOptimizedFileCount() > 0) {
            $this->printSummary();
        }
    }

    /**
     * Processes a single path, handling exceptions and errors.
     *
     * @param string $path The path to process, either a file or directory
     *
     * @throws \LogicException
     * @throws \ValueError
     */
    private function processPath(string $path): void
    {
        try {
            $this->svgFileProcessor->processPath($path);
        } catch (\RuntimeException $exception) {
            $this->outputManager->printError(\sprintf('Failed processing "%s": %s', $path, $exception->getMessage()));
        } catch (\JsonException $jsonException) {
            $this->outputManager->printError(\sprintf('Invalid JSON in configuration file "%s": %s', $this->commandOptionsValueObject->getConfigPath(), $jsonException->getMessage()));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->outputManager->printError($invalidArgumentException->getMessage());
        }
    }

    /**
     * Prints a summary of the optimization results.
     */
    private function printSummary(): void
    {
        $this->outputManager->printTotalSummary(
            $this->metaDataAggregator->getOptimizedFileCount(),
            $this->metaDataAggregator->getTotalOriginalSize(),
            $this->metaDataAggregator->getTotalOptimizedSize(),
            $this->metaDataAggregator->getSavedBytes(),
            $this->metaDataAggregator->getSavedPercentage(),
        );
    }
}
