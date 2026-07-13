<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Service\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptions;

/**
 * @no-named-arguments
 */
final readonly class SvgFileProcessor
{
    /**
     * @param CommandOptions     $commandOptions     The options provided by the command line
     * @param OutputManager      $outputManager      The output manager for displaying messages
     * @param MetaDataAggregator $metaDataAggregator The aggregator for metadata about processed files
     */
    public function __construct(
        private CommandOptions $commandOptions,
        private OutputManager $outputManager,
        private MetaDataAggregator $metaDataAggregator,
    ) {}

    /**
     * Process a path - directory or file.
     *
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \LogicException
     * @throws \ValueError
     * @throws RiskyRulesNotAllowedException If risky optimization rules are used but have not been explicitly allowed
     */
    public function processPath(string $path): void
    {
        if (is_dir($path)) {
            $this->processDirectory($path);
        } elseif (is_file($path) && SvgTag::Svg->value === pathinfo($path, \PATHINFO_EXTENSION)) {
            $this->optimizeSvg($path);
        } else {
            $this->outputManager->printError(\sprintf('"%s" is not a valid SVG file or directory.', $path));
        }
    }

    /**
     * Process all SVG files in a directory recursively.
     *
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \LogicException
     * @throws \ValueError
     * @throws RiskyRulesNotAllowedException If risky optimization rules are used but have not been explicitly allowed
     */
    private function processDirectory(string $directory): void
    {
        $paths = (new Finder())
            ->in($directory)
            ->files()
            ->withExtension(SvgTag::Svg->value)
            ->find();

        foreach ($paths as $path) {
            $this->optimizeSvg($path);
        }
    }

    /**
     * Processes a given path, which may represent a directory or a single SVG file.
     *
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \LogicException
     * @throws \ValueError
     * @throws RiskyRulesNotAllowedException If risky optimization rules are used but have not been explicitly allowed
     */
    private function optimizeSvg(string $filePath): void
    {
        $svgOptimizerFacade = SvgOptimizerFacade::fromFile($filePath)
            ->allowRisky($this->commandOptions->allowRisky())
            ->withAllRules($this->commandOptions->withAllRules())
            ->withRules(...array_map(fn (Rule $rule) => $this->getConfig()[$rule->configKey()] ?? false, Rule::cases()))
            ->optimize();

        $metrics = $svgOptimizerFacade->getMetaData();

        if (!$this->commandOptions->isDryRun()) {
            $svgOptimizerFacade->saveToFile($filePath);
        }

        $this->metaDataAggregator->addFileData(
            $metrics->getOriginalSize(),
            $metrics->getOptimizedSize(),
            $metrics->getOptimizationTime(),
        );

        $this->outputManager->printOptimizationResult($filePath, $metrics->getSavedPercentage());
    }

    /**
     * Retrieve the configuration array for rule options.
     *
     * If a configuration file path is provided via command-line options,
     * the configuration is loaded from that file. Otherwise, an empty
     * configuration array is returned.
     *
     * @return array<string, bool> The configuration array for rule flags
     *
     * @throws \JsonException            If the configuration file contains invalid JSON
     * @throws \ValueError
     * @throws \InvalidArgumentException
     */
    private function getConfig(): array
    {
        return '' !== $this->commandOptions->getConfigPath()
            ? ConfigLoader::loadConfig($this->commandOptions->getConfigPath())
            : [];
    }
}
