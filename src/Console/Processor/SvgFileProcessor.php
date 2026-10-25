<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Processor;

use MathiasReker\PhpSvgOptimizer\Console\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandOption;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class SvgFileProcessor
{
    /**
     * @param CommandOption      $commandOption      The options provided by the command line
     * @param OutputManager      $outputManager      The output manager for displaying messages
     * @param MetaDataAggregator $metaDataAggregator The aggregator for metadata about processed files
     */
    public function __construct(
        private CommandOption $commandOption,
        private OutputManager $outputManager,
        private MetaDataAggregator $metaDataAggregator,
    ) {}

    /**
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \LogicException
     */
    public function processPath(string $path): void
    {
        if (is_dir($path)) {
            $this->processDirectory($path);
        } elseif (is_file($path) && 0 === strcasecmp(pathinfo($path, \PATHINFO_EXTENSION), Element::Svg->value)) {
            $this->optimizeSvg($path);
        } else {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid SVG file or directory.', $path));
        }
    }

    /**
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \LogicException
     */
    private function processDirectory(string $directory): void
    {
        $paths = (new Finder(\DIRECTORY_SEPARATOR))
            ->in($directory)
            ->files()
            ->withExtension(Element::Svg->value)
            ->find();

        foreach ($paths as $path) {
            $this->optimizeSvg($path);
        }
    }

    /**
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \LogicException
     */
    private function optimizeSvg(string $filePath): void
    {
        $optimizationResult = (new SvgOptimizer())
            ->withRiskyRulesAllowed($this->commandOption->allowRisky())
            ->withRules(...$this->allRules(), ...$this->configRules())
            ->withRuleSets(...$this->commandOption->getRuleSets(), ...$this->configRuleSets())
            ->optimizeFile($filePath);

        $metrics = $optimizationResult->getMetrics();

        if (!$this->commandOption->isDryRun()) {
            $optimizationResult->saveToFile($filePath);
        }

        $this->metaDataAggregator->addFileData(
            $metrics->getOriginalSizeInBytes(),
            $metrics->getOptimizedSizeInBytes(),
            $metrics->getDurationInSeconds(),
        );

        $this->outputManager->printOptimizationResult($filePath, $metrics->getSavedPercentage());
    }

    /**
     * @return list<Rule> every rule if all rules are enabled, without the risky rules unless they are allowed
     */
    private function allRules(): array
    {
        if (!$this->commandOption->withAllRules()) {
            return [];
        }

        return array_values(array_filter(
            Rule::cases(),
            fn (Rule $rule): bool => $this->commandOption->allowRisky() || !$rule->isRisky(),
        ));
    }

    /**
     * @return list<Rule> the rules the configuration enables
     *
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    private function configRules(): array
    {
        $config = $this->getConfig();

        return array_values(array_filter(
            Rule::cases(),
            static fn (Rule $rule): bool => $config[$rule->value] ?? false,
        ));
    }

    /**
     * @return list<RuleSet> The rule sets listed in the configuration
     *
     * @throws \JsonException            If the configuration file contains invalid JSON
     * @throws \InvalidArgumentException If the configuration cannot be read
     */
    private function configRuleSets(): array
    {
        return '' !== $this->commandOption->getConfigPath()
            ? ConfigLoader::loadRuleSets($this->commandOption->getConfigPath())
            : [];
    }

    /**
     * @return list<string> The configuration keys that do not name an optimization rule
     *
     * @throws \JsonException            If the configuration file contains invalid JSON
     * @throws \InvalidArgumentException If the configuration cannot be read
     */
    public function getUnknownConfigKeys(): array
    {
        return array_values(array_diff(array_map(strval(...), array_keys($this->getConfig())), $this->ruleNames()));
    }

    /**
     * @return list<string> the rule names accepted in the configuration
     */
    private function ruleNames(): array
    {
        return array_column(Rule::cases(), 'value');
    }

    /**
     * @return array<array-key, bool> The configuration array for rule flags
     *
     * @throws \JsonException            If the configuration file contains invalid JSON
     * @throws \InvalidArgumentException
     */
    private function getConfig(): array
    {
        return '' !== $this->commandOption->getConfigPath()
            ? ConfigLoader::loadConfig($this->commandOption->getConfigPath())
            : [];
    }
}
