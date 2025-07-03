<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Commands;

use MathiasReker\PhpSvgOptimizer\Commands\Helpers\OutputHelper;
use MathiasReker\PhpSvgOptimizer\Enums\Application;
use MathiasReker\PhpSvgOptimizer\Enums\Option;
use MathiasReker\PhpSvgOptimizer\Enums\Rule;
use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;
use MathiasReker\PhpSvgOptimizer\Util\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Util\ConfigLoader;

/**
 * @no-named-arguments
 */
final class SvgOptimizerCommand
{
    /**
     * Constant for the percentage factor used in calculations.
     */
    private const int PERCENTAGE_FACTOR = 100;

    /**
     * The default precision used for percentage formatting.
     */
    private const int DEFAULT_PRECISION = 2;

    /**
     * The exit code for a successful operation.
     */
    private const int EXIT_CODE_SUCCESS = 0;

    /**
     * The exit code for an error during the operation.
     */
    private const int EXIT_CODE_ERROR = 1;

    /**
     * The file extension for SVG files.
     */
    private const string SVG_EXTENSION = 'svg';

    /**
     * The total original size of the SVG files.
     */
    private int $totalOriginalSize = 0;

    /**
     * The total optimized size of the SVG files.
     */
    private int $totalOptimizedSize = 0;

    /**
     * The number of files optimized.
     */
    private int $optimizedFiles = 0;

    /** @var array<string, bool> */
    private array $config = [];

    /**
     * Whether to run the command in dry-run mode.
     */
    private bool $dryRun = false;

    /**
     * Whether to run the command in quiet mode.
     */
    private bool $quiet = false;

    /** @var list<string> */
    private readonly array $paths;

    /**
     * Constructor for the SvgOptimizerCommand class.
     *
     * @param list<string> $paths      The paths to the SVG files or directories to process
     * @param string       $configPath The path to the configuration file
     *
     * @throws \JsonException
     */
    private function __construct(array $paths, string $configPath)
    {
        foreach ($paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                OutputHelper::printError(\sprintf('"%s" is not a valid directory or file.', $path));
                exit(self::EXIT_CODE_ERROR);
            }
        }

        $this->paths = $paths;

        if ('' !== trim($configPath)) {
            if (!is_file($configPath)) {
                OutputHelper::printError(\sprintf('The configuration file "%s" does not exist.', $configPath));
                exit(self::EXIT_CODE_ERROR);
            }

            try {
                $this->config = ConfigLoader::loadConfig($configPath);
            } catch (\InvalidArgumentException $exception) {
                OutputHelper::printError(\sprintf('Failed to load the configuration from "%s": %s.', $configPath, $exception->getMessage()));
                exit(self::EXIT_CODE_ERROR);
            }
        }
    }

    /**
     * Creates a new SvgOptimizerCommand instance from the command-line arguments.
     *
     * @param list<string> $args The command-line arguments passed to the script
     *
     * @return self The SvgOptimizerCommand instance
     */
    public static function fromArgs(array $args): self
    {
        $argumentParser = new ArgumentParser($args);

        if ($argumentParser->hasOption(Option::HELP) || 1 === \count($args)) {
            OutputHelper::printHelp();
            exit(self::EXIT_CODE_SUCCESS);
        }

        if ($argumentParser->hasOption(Option::VERSION)) {
            OutputHelper::printVersion(Application::NAME->value, Application::VERSION->value, Application::AUTHOR->value);
            exit(self::EXIT_CODE_SUCCESS);
        }

        try {
            $paths = \array_slice($args, $argumentParser->getNextPositionalArgumentIndex() + 1);

            if ([] === $paths) {
                OutputHelper::printHelp();
                exit(self::EXIT_CODE_SUCCESS);
            }

            $configPath = $argumentParser->hasOption(Option::CONFIG) ? $argumentParser->getOption(Option::CONFIG) : '';
            $command = new self($paths, $configPath);
            $command->dryRun = $argumentParser->hasOption(Option::DRY_RUN);
            $command->quiet = $argumentParser->hasOption(Option::QUIET);

            return $command;
        } catch (\InvalidArgumentException $invalidArgumentException) {
            OutputHelper::printError($invalidArgumentException->getMessage());
            exit(self::EXIT_CODE_ERROR);
        } catch (\JsonException $jsonException) {
            OutputHelper::printError(\sprintf('The configuration file in invalid. %s', $jsonException->getMessage()));
            exit(self::EXIT_CODE_ERROR);
        }
    }

    /**
     * Runs the SVG optimization process.
     *
     * @throws \RuntimeException If the command is not run from the command line
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
     * Ensures that the command is being run in a CLI environment.
     */
    private function ensureCli(): void
    {
        if (\PHP_SAPI !== 'cli') {
            OutputHelper::printError('This command can only be run from the command line.');
            exit(self::EXIT_CODE_ERROR);
        }
    }

    /**
     * Processes a single path, which can be a file or a directory.
     *
     * @param string $path The path to process
     *
     * @throws \UnexpectedValueException If the path is not a valid SVG file or directory
     */
    private function processPath(string $path): void
    {
        if (is_dir($path)) {
            $this->processDirectory($path);
        } elseif (is_file($path) && self::SVG_EXTENSION === pathinfo($path, \PATHINFO_EXTENSION)) {
            $this->optimizeSvg($path);
        } else {
            OutputHelper::printError(\sprintf('"%s" is not a valid SVG file or directory.', $path));
            exit(self::EXIT_CODE_ERROR);
        }
    }

    /**
     * Prints a summary of the optimization results.
     */
    private function printSummary(): void
    {
        $savedBytes = $this->totalOriginalSize - $this->totalOptimizedSize;

        $savedPercentage = $this->totalOriginalSize > 0
            ? round(($savedBytes / $this->totalOriginalSize) * self::PERCENTAGE_FACTOR, self::DEFAULT_PRECISION)
            : 0.0;

        OutputHelper::printTotalSummary(
            $this->optimizedFiles,
            $this->totalOriginalSize,
            $this->totalOptimizedSize,
            $savedBytes,
            $savedPercentage
        );
    }

    /**
     * Processes all SVG files in a directory.
     *
     * @param string $directoryPath The path to the directory containing the SVG files
     *
     * @throws \UnexpectedValueException If the directory path is invalid
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
     * Optimizes an SVG file.
     *
     * @param string $filePath The path to the SVG file
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
                OutputHelper::printOptimizationResult($filePath, $metaData->getSavedPercentage());
            }
        } catch (\Exception $exception) {
            if (!$this->quiet) {
                OutputHelper::printError(\sprintf('Error processing "%s": %s', $filePath, $exception->getMessage()));
                exit(self::EXIT_CODE_ERROR);
            }
        }
    }
}
