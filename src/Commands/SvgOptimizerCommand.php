<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Commands;

use MathiasReker\PhpSvgOptimizer\Commands\Helpers\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Commands\Helpers\ConfigLoader;
use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;

final class SvgOptimizerCommand
{
    private const int PERCENTAGE_FACTOR = 100;

    private const int DEFAULT_PRECISION = 2;

    private int $totalOriginalSize = 0;

    private int $totalOptimizedSize = 0;

    private int $optimizedFiles = 0;

    /**
     * Configuration for SVG optimization rules.
     *
     * @var array<string, bool>|null
     */
    private ?array $config = null;

    private bool $dryRun = false;

    private bool $quiet = false;

    /**
     * @var string[] List of paths to files or directories
     */
    private readonly array $paths;

    /**
     * Constructor.
     *
     * @param string[]    $paths      Paths to SVG files or directories
     * @param string|null $configPath Path to the configuration file
     *
     * @throws \InvalidArgumentException If any path is invalid
     */
    public function __construct(array $paths, ?string $configPath)
    {
        foreach ($paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                echo \sprintf('Error: "%s" is not a valid directory or file.', $path);
                exit(1);
            }
        }

        $this->paths = $paths;

        if (null !== $configPath) {
            $this->config = ConfigLoader::loadConfig($configPath);
        }
    }

    /**
     * Create an instance of the command from CLI arguments.
     *
     * @param array<string> $args Command-line arguments
     */
    public static function fromArgs(array $args): self
    {
        $argumentParser = new ArgumentParser($args);

        if ($argumentParser->hasOption('--help') || 1 === \count($args)) {
            self::printHelp();
            exit(0);
        }

        $configPath = $argumentParser->getOption('--config');
        $dryRun = $argumentParser->hasOption('--dry-run');
        $quiet = $argumentParser->hasOption('--quiet');

        $subcommand = $args[$argumentParser->getNextPositionalArgumentIndex()] ?? null;

        if ('process' !== $subcommand) {
            self::printHelp();
            exit(1);
        }

        $paths = \array_slice($args, $argumentParser->getNextPositionalArgumentIndex() + 1);
        if ([] === $paths) {
            echo "Error: No paths provided for processing.\n";
            self::printHelp();
            exit(1);
        }

        $command = new self($paths, $configPath);
        $command->dryRun = $dryRun;
        $command->quiet = $quiet;

        return $command;
    }

    /**
     * Print help information for the command.
     */
    public static function printHelp(): void
    {
        echo <<<HELP
            PHP SVG Optimizer

            Usage:
              vendor/bin/svg-optimizer [options] process <path1> <path2> ...

            Options:
              -h, --help                 Display help for the command.
              -c, --config=<config.json> Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.
              -d, --dry-run              Only calculate potential savings without modifying the files.
              -q, --quiet                Suppress all output except errors.

            Examples:
              vendor/bin/svg-optimizer --dry-run --quiet process /path/to/svgs
              vendor/bin/svg-optimizer --config=config.json process /path/to/file.svg
            HELP;
    }

    /**
     * Run the optimization command.
     *
     * @throws \InvalidArgumentException If a path is not valid
     */
    public function run(): void
    {
        foreach ($this->paths as $path) {
            if (is_dir($path)) {
                $this->processDirectory($path);
            } elseif (is_file($path) && 'svg' === pathinfo($path, \PATHINFO_EXTENSION)) {
                $this->optimizeSvg($path);
            } else {
                echo \sprintf('Error: "%s" is not a valid SVG file.', $path) . \PHP_EOL;
            }
        }

        if (!$this->quiet) {
            $this->printSummary();
        }
    }

    /**
     * Process a directory of SVG files.
     *
     * @param string $directoryPath Path to the directory
     */
    private function processDirectory(string $directoryPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo instanceof \SplFileInfo && $fileInfo->isFile() && 'svg' === $fileInfo->getExtension()) {
                $this->optimizeSvg($fileInfo->getPathname());
            }
        }
    }

    /**
     * Optimize a single SVG file.
     *
     * @param string $filePath Path to the SVG file
     */
    private function optimizeSvg(string $filePath): void
    {
        try {
            $svgOptimizer = SvgOptimizerService::fromFile($filePath);

            if (null !== $this->config) {
                $svgOptimizer = $svgOptimizer->withRules(
                    convertColorsToHex: $this->config['convertColorsToHex'] ?? true,
                    flattenGroups: $this->config['flattenGroups'] ?? true,
                    minifySvgCoordinates: $this->config['minifySvgCoordinates'] ?? true,
                    minifyTransformations: $this->config['minifyTransformations'] ?? true,
                    removeComments: $this->config['removeComments'] ?? true,
                    removeDefaultAttributes: $this->config['removeDefaultAttributes'] ?? true,
                    removeDeprecatedAttributes: $this->config['removeDeprecatedAttributes'] ?? true,
                    removeDoctype: $this->config['removeDoctype'] ?? true,
                    removeMetadata: $this->config['removeMetadata'] ?? true,
                    removeTitleAndDesc: $this->config['removeTitleAndDesc'] ?? true,
                    removeUnnecessaryWhitespace: $this->config['removeUnnecessaryWhitespace'] ?? true,
                );
            }

            $svgOptimizer->optimize();

            if (!$this->dryRun) {
                $svgOptimizer->saveToFile($filePath);
            }

            $metaData = $svgOptimizer->getMetaData();
            $this->totalOriginalSize += $metaData->getOriginalSize();
            $this->totalOptimizedSize += $metaData->getOptimizedSize();
            ++$this->optimizedFiles;

            $reduction = $metaData->getOriginalSize() - $metaData->getOptimizedSize();
            $reductionPercentage = $metaData->getOriginalSize() > 0
                ? ($reduction / $metaData->getOriginalSize()) * self::PERCENTAGE_FACTOR
                : 0;

            if (!$this->quiet) {
                echo \sprintf('%s (%s%%)%s', $filePath, number_format($reductionPercentage, self::DEFAULT_PRECISION), \PHP_EOL);
            }
        } catch (\Exception $exception) {
            if (!$this->quiet) {
                echo \sprintf('Error processing "%s": %s%s', $filePath, $exception->getMessage(), \PHP_EOL);
            }
        }
    }

    /**
     * Print a summary of the optimization results.
     */
    private function printSummary(): void
    {
        $reduction = $this->totalOriginalSize - $this->totalOptimizedSize;
        $reductionPercentage = $this->totalOriginalSize > 0
            ? ($reduction / $this->totalOriginalSize) * self::PERCENTAGE_FACTOR
            : 0;

        echo \PHP_EOL;
        echo \sprintf('Total files processed: %d%s', $this->optimizedFiles, \PHP_EOL);
        echo \sprintf('Total size reduction: %d bytes%s', $reduction, \PHP_EOL);
        echo \sprintf('Total reduction percentage: %s%%%s', number_format($reductionPercentage, self::DEFAULT_PRECISION), \PHP_EOL);
    }
}
