<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Command;

use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Console\Output\OutputHelper;
use MathiasReker\PhpSvgOptimizer\Contract\Console\Command\CommandInterface;
use MathiasReker\PhpSvgOptimizer\Service\SvgOptimizerService;
use MathiasReker\PhpSvgOptimizer\Type\Rule;

/**
 * @no-named-arguments
 */
final class SvgOptimizerCommand implements CommandInterface
{
    /**
     * The factor used to calculate the percentage saved.
     */
    private const int PERCENTAGE_FACTOR = 100;

    /**
     * The default precision for percentage calculations.
     */
    private const int DEFAULT_PRECISION = 2;

    /**
     * The file extension for SVG files.
     */
    private const string SVG_EXTENSION = 'svg';

    /**
     * The total size of original SVG files before optimization.
     */
    private int $totalOriginalSize = 0;

    /**
     * The total size of optimized SVG files after optimization.
     */
    private int $totalOptimizedSize = 0;

    /**
     * The number of SVG files that have been optimized.
     */
    private int $optimizedFiles = 0;

    /** @var array<string, bool> */
    private array $config = [];

    /**
     * Indicates whether the command should perform a dry run.
     */
    private bool $dryRun;

    /**
     * Indicates whether the command should run in quiet mode.
     */
    private bool $quiet;

    /** @var list<string> */
    private readonly array $paths;

    /**
     * @param list<string> $paths
     */
    private readonly OutputHelper $outputHelper;

    /**
     * @param list<string> $paths        The paths to SVG files or directories to be optimized
     * @param string       $configPath   The path to the configuration file
     * @param OutputHelper $outputHelper The output helper for printing messages
     * @param bool         $dryRun       If true, performs a dry run without saving changes
     * @param bool         $quiet        If true, suppresses output messages
     *
     * @throws \JsonException
     */
    public function __construct(
        array $paths,
        string $configPath,
        OutputHelper $outputHelper,
        bool $dryRun,
        bool $quiet,
    ) {
        $this->paths = $paths;
        $this->outputHelper = $outputHelper;
        $this->dryRun = $dryRun;
        $this->quiet = $quiet;

        if ([] === $paths) {
            $this->outputHelper->printError('No SVG files or directories specified for optimization.');
        }

        foreach ($paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                $this->outputHelper->printError(\sprintf('"%s" is not a valid directory or file.', $path));
            }
        }

        if ('' !== trim($configPath)) {
            if (!is_file($configPath)) {
                $this->outputHelper->printError(\sprintf('The configuration file "%s" does not exist.', $configPath));
            }

            try {
                $this->config = ConfigLoader::loadConfig($configPath);
            } catch (\InvalidArgumentException $e) {
                $this->outputHelper->printError(\sprintf('Failed to load the configuration from "%s": %s.', $configPath, $e->getMessage()));
            }
        }
    }

    /**
     * Executes the SVG optimization command.
     *
     * This method processes each path provided, optimizing SVG files according to the specified rules
     * It prints a summary of the optimization results if not in quiet mode.
     *
     * @throws \RuntimeException if the command is not run from CLI or if an error occurs during processing
     */
    public function run(): void
    {
        $this->ensureCli();

        foreach ($this->paths as $path) {
            $this->processPath($path);
        }

        if (!$this->quiet && $this->optimizedFiles > 0) {
            $this->printSummary();
        }
    }

    /**
     * Ensures that the command is run from the command line interface (CLI).
     *
     * If the command is not run from CLI, it prints an error message and exits.
     */
    private function ensureCli(): void
    {
        if (\PHP_SAPI !== 'cli') {
            $this->outputHelper->printError('This command can only be run from the command line.');
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function processPath(string $path): void
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
     * @throws \RuntimeException
     */
    private function processDirectory(string $directoryPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo instanceof \SplFileInfo
                && $fileInfo->isFile()
                && self::SVG_EXTENSION === $fileInfo->getExtension()) {
                $this->optimizeSvg($fileInfo->getPathname());
            }
        }
    }

    /**
     * Optimizes the SVG file at the given file path.
     *
     * This method applies various optimization rules to the SVG file and saves the optimized version.
     * It also updates the total original and optimized sizes, and prints the result if not in quiet mode.
     *
     * @param string $filePath The path to the SVG file to be optimized
     */
    private function optimizeSvg(string $filePath): void
    {
        try {
            $rules = [];

            foreach (Rule::cases() as $rule) {
                $rules[$rule->value] = $this->config[$rule->value] ?? $rule->defaultValue();
            }

            $svgOptimizer = SvgOptimizerService::fromFile($filePath)->withRules(
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
            );

            $svgOptimizer->optimize();

            if (!$this->dryRun) {
                $svgOptimizer->saveToFile($filePath);
            }

            $metaData = $svgOptimizer->getMetaData();
            $this->totalOriginalSize += $metaData->getOriginalSize();
            $this->totalOptimizedSize += $metaData->getOptimizedSize();
            ++$this->optimizedFiles;

            if (!$this->quiet) {
                $this->outputHelper->printOptimizationResult($filePath, $metaData->getSavedPercentage());
            }
        } catch (\Exception $exception) {
            if (!$this->quiet) {
                $this->outputHelper->printError(\sprintf('Error processing "%s": %s', $filePath, $exception->getMessage()));
            }
        }
    }

    /**
     * Prints a summary of the optimization results.
     *
     * This method calculates the total bytes saved and the percentage of space saved,
     * then prints the summary to the output.
     */
    private function printSummary(): void
    {
        $savedBytes = $this->totalOriginalSize - $this->totalOptimizedSize;

        $savedPercentage = $this->totalOriginalSize > 0
            ? round(($savedBytes / $this->totalOriginalSize) * self::PERCENTAGE_FACTOR, self::DEFAULT_PRECISION)
            : 0.0;

        $this->outputHelper->printTotalSummary(
            $this->optimizedFiles,
            $this->totalOriginalSize,
            $this->totalOptimizedSize,
            $savedBytes,
            $savedPercentage
        );
    }
}
