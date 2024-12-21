<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Commands;

use MathiasReker\PhpSvgOptimizer\Configs\RuleConfig;
use MathiasReker\PhpSvgOptimizer\Enums\Option;
use MathiasReker\PhpSvgOptimizer\Services\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;
use MathiasReker\PhpSvgOptimizer\Services\Util\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Services\Util\ConfigLoader;

final class SvgOptimizerCommand
{
    /**
     * The factor to convert a decimal to a percentage.
     */
    private const int PERCENTAGE_FACTOR = 100;

    /**
     * The default precision for percentage values.
     */
    private const int DEFAULT_PRECISION = 2;

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

    /**
     * @var array<string, bool>|null
     */
    private ?array $config = null;

    /**
     * Whether to run the command in dry-run mode.
     */
    private bool $dryRun = false;

    /**
     * Whether to run the command in quiet mode.
     */
    private bool $quiet = false;

    /**
     * @var array<string>
     */
    private readonly array $paths;

    /**
     * Constructor for the SvgOptimizerCommand class.
     *
     * @param array<string> $paths      The paths to the SVG files or directories to process
     * @param string|null   $configPath The path to the configuration file
     */
    private function __construct(array $paths, ?string $configPath)
    {
        foreach ($paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                fprintf(\STDERR, 'Error: "%s" is not a valid directory or file.', $path);
                exit(1);
            }
        }

        $this->paths = $paths;

        if (null !== $configPath) {
            try {
                $this->config = ConfigLoader::loadConfig($configPath);
            } catch (\InvalidArgumentException $exception) {
                fprintf(\STDERR, '%s', $exception->getMessage());
                exit(1);
            }
        }
    }

    /**
     * Creates a new SvgOptimizerCommand instance from the command-line arguments.
     *
     * @param array<string> $args The command-line arguments passed to the script
     *
     * @return SvgOptimizerCommand The SvgOptimizerCommand instance
     */
    public static function fromArgs(array $args): self
    {
        $argumentParser = new ArgumentParser($args);

        if ($argumentParser->hasOption(Option::HELP) || 1 === \count($args)) {
            self::printHelp();
            exit(0);
        }

        if ($argumentParser->hasOption(Option::VERSION)) {
            self::printVersion();
            exit(0);
        }

        try {
            $paths = \array_slice($args, $argumentParser->getNextPositionalArgumentIndex() + 1);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            fprintf(\STDERR, "%s\n", $invalidArgumentException->getMessage());
            exit(1);
        }

        if ([] === $paths) {
            self::printHelp();
            exit(0);
        }

        $command = new self($paths, $argumentParser->getOption(Option::CONFIG));
        $command->dryRun = $argumentParser->hasOption(Option::DRY_RUN);
        $command->quiet = $argumentParser->hasOption(Option::QUIET);

        return $command;
    }

    /**
     * Prints the help message for the command.
     */
    private static function printHelp(): void
    {
        $argumentData = new ArgumentData();
        printf('PHP SVG Optimizer%s%s', \PHP_EOL, \PHP_EOL);
        printf('Usage:%s', \PHP_EOL);
        printf('  %s%s%s', $argumentData->getFormat(), \PHP_EOL, \PHP_EOL);
        printf('Options:%s', \PHP_EOL);
        foreach ($argumentData->getOptions() as $argumentOptionValueObject) {
            $shorthand = $argumentOptionValueObject->getShorthand();
            $full = $argumentOptionValueObject->getFull();
            $description = $argumentOptionValueObject->getDescription();
            printf('  %-3s, %-20s %s' . \PHP_EOL, $shorthand, $full, $description);
        }

        echo \PHP_EOL;
        printf('Commands:%s', \PHP_EOL);
        foreach ($argumentData->getCommands() as $commandOptionValueObject) {
            printf('  %-25s %-3s' . \PHP_EOL, $commandOptionValueObject->getTitle(), $commandOptionValueObject->getDescription());
        }

        printf('%sExamples:%s', \PHP_EOL, \PHP_EOL);
        foreach ($argumentData->getExamples() as $exampleCommandValueObject) {
            printf('  %s%s', $exampleCommandValueObject->getCommand(), \PHP_EOL);
        }
    }

    /**
     * Prints the version of the library.
     */
    private static function printVersion(): void
    {
        $version = self::getVersionFromPackageJson();

        printf('PHP SVG Optimizer v%s%s', $version, \PHP_EOL);
    }

    /**
     * Retrieves the version of the library from the package.json file.
     */
    private static function getVersionFromPackageJson(): ?string
    {
        $packageJsonPath = __DIR__ . '/../../composer.json';
        if (file_exists($packageJsonPath)) {
            $packageJson = file_get_contents($packageJsonPath);
            if (false === $packageJson) {
                return null;
            }

            $data = json_decode($packageJson, true);
            if (\is_array($data) && \array_key_exists('version', $data) && \is_string($data['version'])) {
                return $data['version'];
            }
        }

        return null;
    }

    /**
     * Runs the SVG optimization process.
     */
    public function run(): void
    {
        foreach ($this->paths as $path) {
            if (is_dir($path)) {
                $this->processDirectory($path);
            } elseif (is_file($path) && 'svg' === pathinfo($path, \PATHINFO_EXTENSION)) {
                $this->optimizeSvg($path);
            } else {
                printf('Error: "%s" is not a valid SVG file.', $path);
            }
        }

        if (!$this->quiet) {
            $this->printSummary();
        }
    }

    /**
     * Processes all SVG files in a directory.
     *
     * @param string $directoryPath The path to the directory containing the SVG files
     */
    private function processDirectory(string $directoryPath): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directoryPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo instanceof \SplFileInfo && $fileInfo->isFile() && 'svg' === $fileInfo->getExtension()) {
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
            $svgOptimizer = SvgOptimizerService::fromFile($filePath);
            $rules = RuleConfig::DEFAULT_RULES;
            if (null !== $this->config) {
                $rules = array_merge($rules, $this->config);
            }

            $svgOptimizer = $svgOptimizer->withRules(convertColorsToHex: $rules['convertColorsToHex'], flattenGroups: $rules['flattenGroups'], minifySvgCoordinates: $rules['minifySvgCoordinates'], minifyTransformations: $rules['minifyTransformations'], removeComments: $rules['removeComments'], removeDefaultAttributes: $rules['removeDefaultAttributes'], removeDeprecatedAttributes: $rules['removeDeprecatedAttributes'], removeDoctype: $rules['removeDoctype'], removeEmptyAttributes: $rules['removeEmptyAttributes'], removeMetadata: $rules['removeMetadata'], removeTitleAndDesc: $rules['removeTitleAndDesc'], removeUnnecessaryWhitespace: $rules['removeUnnecessaryWhitespace'], sortAttributes: $rules['sortAttributes']);
            $svgOptimizer->optimize();
            if (!$this->dryRun) {
                $svgOptimizer->saveToFile($filePath);
            }

            $metaData = $svgOptimizer->getMetaData();
            $this->totalOriginalSize += $metaData->getOriginalSize();
            $this->totalOptimizedSize += $metaData->getOptimizedSize();
            ++$this->optimizedFiles;
            $reduction = $metaData->getOriginalSize() - $metaData->getOptimizedSize();
            $reductionPercentage = $metaData->getOriginalSize() > 0 ? ($reduction / $metaData->getOriginalSize()) * self::PERCENTAGE_FACTOR : 0;
            if (!$this->quiet) {
                printf('%s (%s%%)%s', $filePath, number_format($reductionPercentage, self::DEFAULT_PRECISION), \PHP_EOL);
            }
        } catch (\Exception $exception) {
            if (!$this->quiet) {
                fprintf(\STDERR, 'Error: Failed processing "%s": %s%s', $filePath, $exception->getMessage(), \PHP_EOL);
            }
        }
    }

    /**
     * Prints a summary of the SVG optimization process.
     */
    private function printSummary(): void
    {
        $reduction = $this->totalOriginalSize - $this->totalOptimizedSize;
        $reductionPercentage = $this->totalOriginalSize > 0 ? ($reduction / $this->totalOriginalSize) * self::PERCENTAGE_FACTOR : 0;

        echo \PHP_EOL;
        printf('Total files processed: %d%s', $this->optimizedFiles, \PHP_EOL);
        printf('Total size reduction: %d bytes%s', $reduction, \PHP_EOL);
        printf('Total reduction percentage: %s%%%s', number_format($reductionPercentage, self::DEFAULT_PRECISION), \PHP_EOL);
    }
}
