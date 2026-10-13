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
use MathiasReker\PhpSvgOptimizer\Console\Output\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Console\Processor\SvgFileProcessor;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandOption;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Command\CommandInterface;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class Command implements CommandInterface
{
    public const int SUCCESS = 0;

    public const int FAILURE = 1;

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
     * @return int The exit code: SUCCESS if every path was processed, FAILURE otherwise
     *
     * @throws \LogicException
     */
    public function run(): int
    {
        $exitCode = self::SUCCESS;

        if (!$this->validateConfiguration()) {
            return self::FAILURE;
        }

        try {
            foreach ($this->paths as $path) {
                if (!$this->processPath($path)) {
                    $exitCode = self::FAILURE;
                }
            }

            if ($this->metaDataAggregator->hasOptimizedFiles()) {
                $this->printSummary();
            }
        } catch (RiskyRulesNotAllowedException) {
            $this->outputManager->printError('Risky rules are disabled. Use --allow-risky to enable it.');

            return self::FAILURE;
        }

        return $exitCode;
    }

    /**
     * @param string $path The path to process, either a file or directory
     *
     * @return bool True if the path was processed, false if an error was printed
     *
     * @throws \LogicException
     * @throws RiskyRulesNotAllowedException If risky optimization rules are used but have not been explicitly allowed
     */
    private function processPath(string $path): bool
    {
        try {
            $this->svgFileProcessor->processPath($path);

            return true;
        } catch (\RuntimeException|SvgValidationException $exception) {
            $this->outputManager->printError(\sprintf('Failed processing "%s": %s', $path, $exception->getMessage()));
        } catch (\InvalidArgumentException|\JsonException $exception) {
            $this->outputManager->printError($exception->getMessage());
        }

        return false;
    }

    /**
     * @return bool True if the configuration can be used, false if an error was printed
     */
    private function validateConfiguration(): bool
    {
        try {
            $unknownConfigKeys = $this->svgFileProcessor->getUnknownConfigKeys();
        } catch (\JsonException $jsonException) {
            $this->outputManager->printError(\sprintf('Invalid JSON in configuration file "%s": %s', $this->commandOption->getConfigPath(), $jsonException->getMessage()));

            return false;
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->outputManager->printError($invalidArgumentException->getMessage());

            return false;
        }

        foreach ($unknownConfigKeys as $unknownConfigKey) {
            $this->outputManager->printWarning(\sprintf('Unknown rule "%s" in the configuration is ignored.', $unknownConfigKey));
        }

        return true;
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
