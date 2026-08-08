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
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Service\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOption;

/**
 * @no-named-arguments
 */
final readonly class Command implements CommandInterface
{
    private MetaDataAggregator $metaDataAggregator;

    private SvgFileProcessor $svgFileProcessor;

    /**
     * @param list<string>  $paths         The paths to SVG files or directories to process
     * @param CommandOption $commandOption The options for the command
     * @param OutputManager $outputManager The output helper for displaying messages
     */
    public function __construct(
        private array $paths,
        private CommandOption $commandOption,
        private OutputManager $outputManager,
    ) {
        $this->metaDataAggregator = new MetaDataAggregator();
        $this->svgFileProcessor = $this->buildProcessor();
    }

    /**
     * @return SvgFileProcessor The configured SVG file processor
     */
    private function buildProcessor(): SvgFileProcessor
    {
        return new SvgFileProcessor(
            $this->commandOption,
            $this->outputManager,
            $this->metaDataAggregator
        );
    }

    /**
     * @throws \LogicException
     * @throws \ValueError
     */
    public function run(): void
    {
        try {
            foreach ($this->paths as $path) {
                $this->processPath($path);
            }

            if ($this->metaDataAggregator->hasOptimizedFiles()) {
                $this->printSummary();
            }
        } catch (RiskyRulesNotAllowedException) {
            $this->outputManager->printError('Risky rules are disabled. Use --allow-risky to enable it.');
        }
    }

    /**
     * @param string $path The path to process, either a file or directory
     *
     * @throws \LogicException
     * @throws \ValueError
     * @throws RiskyRulesNotAllowedException If risky optimization rules are used but have not been explicitly allowed
     */
    private function processPath(string $path): void
    {
        try {
            $this->svgFileProcessor->processPath($path);
        } catch (\RuntimeException $exception) {
            $this->outputManager->printError(\sprintf('Failed processing "%s": %s', $path, $exception->getMessage()));
        } catch (\JsonException $jsonException) {
            $this->outputManager->printError(\sprintf('Invalid JSON in configuration file "%s": %s', $this->commandOption->getConfigPath(), $jsonException->getMessage()));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->outputManager->printError($invalidArgumentException->getMessage());
        }
    }

    private function printSummary(): void
    {
        $this->outputManager->printTotalSummary(
            $this->metaDataAggregator->getOptimizedFileCount(),
            $this->metaDataAggregator->getTotalOriginalSize(),
            $this->metaDataAggregator->getTotalOptimizedSize(),
            $this->metaDataAggregator->getSavedBytes(),
            $this->metaDataAggregator->getSavedPercentage(),
            $this->metaDataAggregator->getOptimizationTime(),
        );
    }
}
