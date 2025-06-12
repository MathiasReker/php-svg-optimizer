<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Commands;

use MathiasReker\PhpSvgOptimizer\Enums\Option;
use MathiasReker\PhpSvgOptimizer\Enums\Rule;
use MathiasReker\PhpSvgOptimizer\Services\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Services\SvgOptimizerService;
use MathiasReker\PhpSvgOptimizer\Services\Util\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Services\Util\ConfigLoader;

/**
 * @no-named-arguments
 */
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
     * The exit code for a successful operation.
     */
    private const int EXIT_CODE_SUCCESS = 0;

    /**
     * The exit code for an error during the operation.
     */
    private const int EXIT_CODE_ERROR = 1;

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

    /** @var array<string, bool>|null */
    private ?array $config = null;

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
     * @param string|null  $configPath The path to the configuration file
     */
    private function __construct(array $paths, ?string $configPath)
    {
        foreach ($paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                fprintf(\STDERR, 'Error: "%s" is not a valid directory or file.', $path);
                exit(self::EXIT_CODE_ERROR);
            }
        }

        $this->paths = $paths;

        if (null !== $configPath) {
            try {
                $this->config = ConfigLoader::loadConfig($configPath);
            } catch (\InvalidArgumentException $exception) {
                fprintf(\STDERR, '%s', $exception->getMessage());
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
            self::printHelp();
            exit(self::EXIT_CODE_SUCCESS);
        }

        if ($argumentParser->hasOption(Option::VERSION)) {
            self::printVersion();
            exit(self::EXIT_CODE_SUCCESS);
        }

        try {
            $paths = \array_slice($args, $argumentParser->getNextPositionalArgumentIndex() + 1);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            fprintf(\STDERR, "%s\n", $invalidArgumentException->getMessage());
            exit(self::EXIT_CODE_ERROR);
        }

        if ([] === $paths) {
            self::printHelp();
            exit(self::EXIT_CODE_SUCCESS);
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

        printf('%sCommands:%s', \PHP_EOL, \PHP_EOL);
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
     *
     * @throws \UnexpectedValueException
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

            $rules = [];

            foreach (Rule::cases() as $rule) {
                $rules[$rule->value] = $this->config[$rule->value] ?? $rule->defaultValue();
            }

            $svgOptimizer = $svgOptimizer->withRules(
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
            );

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
        $reductionPercentage = $this->totalOriginalSize > 0
            ? ($reduction / $this->totalOriginalSize) * self::PERCENTAGE_FACTOR
            : 0;

        printf('%sTotal files processed: %d%s', \PHP_EOL, $this->optimizedFiles, \PHP_EOL);
        printf('Total size reduction: %d bytes%s', $reduction, \PHP_EOL);
        printf('Total reduction percentage: %s%%%s', number_format($reductionPercentage, self::DEFAULT_PRECISION), \PHP_EOL);
    }
}
