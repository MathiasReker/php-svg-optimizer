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
use MathiasReker\PhpSvgOptimizer\Console\Output\Helper\OutputHelper;
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;

/**
 * @no-named-arguments
 */
final readonly class SvgFileProcessor
{
    private const string SVG_EXTENSION = 'svg';

    public function __construct(
        private CommandOptionsValueObject $commandOptions,
        private OutputHelper $outputHelper,
        private MetaDataAggregator $metaDataAggregator,
    ) {}

    /**
     * Process a path - directory or file.
     *
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function processPath(string $path): void
    {
        if (is_dir($path)) {
            $this->processDirectory($path);
        } elseif (is_file($path) && self::SVG_EXTENSION === pathinfo($path, \PATHINFO_EXTENSION)) {
            $this->optimizeSvg($path);
        } else {
            $this->outputHelper->printError(\sprintf('"%s" is not a valid SVG file or directory.', $path));
        }
    }

    /**
     * Process all SVG files in a directory recursively.
     *
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    public function processDirectory(string $directoryPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo instanceof \SplFileInfo
                && $fileInfo->isFile()
                && self::SVG_EXTENSION === $fileInfo->getExtension()
            ) {
                $this->optimizeSvg($fileInfo->getPathname());
            }
        }
    }

    /**
     * Optimize a single SVG file.
     *
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \InvalidArgumentException
     */
    private function optimizeSvg(string $filePath): void
    {
        $config = '' !== $this->commandOptions->configPath
            ? ConfigLoader::loadConfig($this->commandOptions->configPath)
            : [];

        $rules = array_combine(
            array_map(static fn (Rule $rule): string => $rule->value, Rule::cases()),
            array_map(static fn (Rule $rule): bool => $config[$rule->value] ?? $rule->defaultValue(), Rule::cases()),
        );

        $svgOptimizer = SvgOptimizerFacade::fromFile($filePath)
            ->withRules(
                $rules[Rule::CONVERT_COLORS_TO_HEX->value],
                $rules[Rule::FLATTEN_GROUPS->value],
                $rules[Rule::MINIFY_SVG_COORDINATES->value],
                $rules[Rule::MINIFY_TRANSFORMATIONS->value],
                $rules[Rule::REMOVE_COMMENTS->value],
                $rules[Rule::REMOVE_DEFAULT_ATTRIBUTES->value],
                $rules[Rule::REMOVE_DEPRECATED_ATTRIBUTES->value],
                $rules[Rule::REMOVE_DOCTYPE->value],
                $rules[Rule::REMOVE_ENABLE_BACKGROUND_ATTRIBUTE->value],
                $rules[Rule::REMOVE_EMPTY_ATTRIBUTES->value],
                $rules[Rule::REMOVE_INVISIBLE_CHARACTERS->value],
                $rules[Rule::REMOVE_METADATA->value],
                $rules[Rule::REMOVE_TITLE_AND_DESC->value],
                $rules[Rule::SORT_ATTRIBUTES->value],
                $rules[Rule::CONVERT_EMPTY_TAGS_TO_SELF_CLOSING->value],
                $rules[Rule::REMOVE_UNNECESSARY_WHITESPACE->value],
                $rules[Rule::REMOVE_UNUSED_NAMESPACES->value],
                $rules[Rule::REMOVE_INKSCAPE_FOOTPRINTS->value],
                $rules[Rule::REMOVE_UNSAFE_ELEMENTS->value],
            )
            ->optimize();

        if (!$this->commandOptions->dryRun) {
            $svgOptimizer->saveToFile($filePath);
        }

        $metaData = $svgOptimizer->getMetaData();

        $this->metaDataAggregator->addFileData(
            $metaData->getOriginalSize(),
            $metaData->getOptimizedSize(),
        );

        if (!$this->commandOptions->quiet) {
            $this->outputHelper->printOptimizationResult($filePath, $metaData->getSavedPercentage());
        }
    }
}
