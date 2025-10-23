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
use MathiasReker\PhpSvgOptimizer\Model\MetaDataAggregator;
use MathiasReker\PhpSvgOptimizer\Service\Facade\SvgOptimizerFacade;
use MathiasReker\PhpSvgOptimizer\Service\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandOptionsValueObject;

/**
 * @no-named-arguments
 */
final readonly class SvgFileProcessor
{
    /**
     * The file extension for SVG files.
     */
    private const string SVG_EXTENSION = 'svg';

    /**
     * Constructor for SvgFileProcessor.
     *
     * @param CommandOptionsValueObject $commandOptionsValueObject The options provided by the command line
     * @param OutputManager             $outputManager             The output manager for displaying messages
     * @param MetaDataAggregator        $metaDataAggregator        The aggregator for metadata about processed files
     */
    public function __construct(
        private CommandOptionsValueObject $commandOptionsValueObject,
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
     */
    public function processPath(string $path): void
    {
        if (is_dir($path)) {
            $this->processDirectory($path);
        } elseif (is_file($path) && self::SVG_EXTENSION === pathinfo($path, \PATHINFO_EXTENSION)) {
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
     */
    private function processDirectory(string $directory): void
    {
        $filePaths = (new Finder())
            ->in($directory)
            ->files()
            ->withExtension(self::SVG_EXTENSION)
            ->find();

        foreach ($filePaths as $filePath) {
            $this->optimizeSvg($filePath);
        }
    }

    /**
     * Optimize a single SVG file.
     *
     * @throws \RuntimeException
     * @throws \JsonException
     * @throws \LogicException
     * @throws \ValueError
     */
    private function optimizeSvg(string $filePath): void
    {
        $config = '' !== $this->commandOptionsValueObject->getConfigPath()
            ? ConfigLoader::loadConfig($this->commandOptionsValueObject->getConfigPath())
            : [];

        $rules = array_combine(
            array_map(static fn (Rule $rule): string => $rule->value, Rule::cases()),
            array_map(static fn (Rule $rule): bool => $config[$rule->value] ?? $rule->defaultValue(), Rule::cases()),
        );

        $svgOptimizerFacade = SvgOptimizerFacade::fromFile($filePath)
            ->withRules(
                $rules[Rule::CONVERT_COLORS_TO_HEX->value],
                $rules[Rule::CONVERT_CSS_CLASSES_TO_ATTRIBUTES->value],
                $rules[Rule::CONVERT_EMPTY_TAGS_TO_SELF_CLOSING->value],
                $rules[Rule::CONVERT_INLINE_STYLES_TO_ATTRIBUTES->value],
                $rules[Rule::FLATTEN_GROUPS->value],
                $rules[Rule::MINIFY_SVG_COORDINATES->value],
                $rules[Rule::MINIFY_TRANSFORMATIONS->value],
                $rules[Rule::REMOVE_COMMENTS->value],
                $rules[Rule::REMOVE_DEFAULT_ATTRIBUTES->value],
                $rules[Rule::REMOVE_DEPRECATED_ATTRIBUTES->value],
                $rules[Rule::REMOVE_DOCTYPE->value],
                $rules[Rule::REMOVE_ENABLE_BACKGROUND_ATTRIBUTE->value],
                $rules[Rule::REMOVE_EMPTY_ATTRIBUTES->value],
                $rules[Rule::REMOVE_INKSCAPE_FOOTPRINTS->value],
                $rules[Rule::REMOVE_INVISIBLE_CHARACTERS->value],
                $rules[Rule::REMOVE_METADATA->value],
                $rules[Rule::REMOVE_TITLE_AND_DESC->value],
                $rules[Rule::REMOVE_UNNECESSARY_WHITESPACE->value],
                $rules[Rule::REMOVE_UNSAFE_ELEMENTS->value],
                $rules[Rule::REMOVE_UNUSED_MASKS->value],
                $rules[Rule::REMOVE_UNUSED_NAMESPACES->value],
                $rules[Rule::REMOVE_WIDTH_HEIGHT_ATTRIBUTES->value],
                $rules[Rule::SORT_ATTRIBUTES->value],
            )
            ->optimize();

        if (!$this->commandOptionsValueObject->isDryRun()) {
            $svgOptimizerFacade->saveToFile($filePath);
        }

        $metaDataValueObject = $svgOptimizerFacade->getMetaData();

        $this->metaDataAggregator->addFileData(
            $metaDataValueObject->getOriginalSize(),
            $metaDataValueObject->getOptimizedSize(),
        );

        $this->outputManager->printOptimizationResult($filePath, $metaDataValueObject->getSavedPercentage());
    }
}
