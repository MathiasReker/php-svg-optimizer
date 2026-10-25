<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console;

use Composer\InstalledVersions;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLine;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLineParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigFile;
use MathiasReker\PhpSvgOptimizer\Console\Output\NullStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Printer;
use MathiasReker\PhpSvgOptimizer\Console\Output\StreamInterface;
use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class Application
{
    private const string NAME = 'PHP SVG Optimizer';

    private const string AUTHOR = 'Mathias Reker';

    private const string PACKAGE = 'mathiasreker/php-svg-optimizer';

    private const string UNKNOWN_VERSION = 'unknown';

    private const string CONFIG_FILE = 'svg-optimizer.json';

    /**
     * @param StreamInterface $output where results and information are written
     * @param StreamInterface $error  where errors are written
     */
    public function __construct(
        private StreamInterface $output,
        private StreamInterface $error,
    ) {}

    /**
     * @param list<string> $arguments the command line arguments after the script name
     */
    public function run(array $arguments): ExitCode
    {
        try {
            $commandLine = (new CommandLineParser())->parse($arguments);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->createPrinter($this->output, \in_array('--no-ansi', $arguments, true), \in_array('--ansi', $arguments, true))->printError($invalidArgumentException->getMessage());

            return ExitCode::InvalidUsage;
        }

        $printer = $this->createPrinter($this->output, $commandLine->isColorDisabled(), $commandLine->isColorForced());

        return match (true) {
            [] === $arguments || $commandLine->isHelpRequested() => $this->showHelp($printer),
            $commandLine->isVersionRequested() => $this->showVersion($printer),
            $commandLine->isRuleListRequested() => $this->showRules($printer),
            default => $this->optimize($commandLine, $commandLine->isQuiet() ? $this->createPrinter(new NullStream(), $commandLine->isColorDisabled(), $commandLine->isColorForced()) : $printer),
        };
    }

    private function createPrinter(StreamInterface $stream, bool $colorDisabled, bool $colorForced): Printer
    {
        return new Printer(
            $stream,
            $this->error,
            !$colorDisabled && ($colorForced || $stream->hasColorSupport()),
            !$colorDisabled && ($colorForced || $this->error->hasColorSupport()),
        );
    }

    private function showHelp(Printer $printer): ExitCode
    {
        $printer->printHelp(new HelpRenderer());

        return ExitCode::Success;
    }

    private function showVersion(Printer $printer): ExitCode
    {
        $printer->printVersion(self::NAME, $this->version(), self::AUTHOR);

        return ExitCode::Success;
    }

    private function showRules(Printer $printer): ExitCode
    {
        $printer->printRuleList();

        return ExitCode::Success;
    }

    private function version(): string
    {
        $version = InstalledVersions::isInstalled(self::PACKAGE) ? InstalledVersions::getPrettyVersion(self::PACKAGE) : null;

        return null === $version ? self::UNKNOWN_VERSION : ltrim($version, 'v');
    }

    private function optimize(CommandLine $commandLine, Printer $printer): ExitCode
    {
        try {
            $optimizer = $this->createOptimizer($commandLine);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $printer->printError($invalidArgumentException->getMessage());

            return ExitCode::InvalidUsage;
        }

        $runSummary = new RunSummary();
        $fileOptimizer = new FileOptimizer($optimizer, $printer, $runSummary, !$commandLine->isDryRun() && !$commandLine->isCheckRequested());

        try {
            foreach ($this->findFiles($commandLine->getPaths(), $printer, $runSummary) as $file) {
                $this->optimizeFile($fileOptimizer, $file, $printer, $runSummary);
            }
        } catch (RiskyRulesNotAllowedException $riskyRulesNotAllowedException) {
            $printer->printError(\sprintf(
                'Risky rules need --allow-risky, or "allowRisky" in the configuration: %s.',
                implode(', ', array_map(static fn (Rule $rule): string => $rule->value, $riskyRulesNotAllowedException->getRules())),
            ));

            return ExitCode::InvalidUsage;
        }

        return $this->finish($commandLine, $printer, $runSummary);
    }

    /**
     * @throws RiskyRulesNotAllowedException If a selected rule is risky and risky rules are not allowed
     */
    private function optimizeFile(FileOptimizer $fileOptimizer, string $file, Printer $printer, RunSummary $runSummary): void
    {
        try {
            $fileOptimizer->optimizePath($file);
        } catch (InvalidSvgException $invalidSvgException) {
            $reason = $invalidSvgException->getPrevious()?->getMessage();
            $printer->printError(null === $reason ? \sprintf('"%s" is not a valid SVG file.', $file) : \sprintf('"%s" is not a valid SVG file: %s', $file, $reason));
            $runSummary->addFailure();
        } catch (IOException $ioException) {
            $printer->printError($ioException->getMessage());
            $runSummary->addFailure();
        }
    }

    /**
     * @param list<string> $paths      the files and directories to search
     * @param Printer      $printer    where the paths that cannot be searched are reported
     * @param RunSummary   $runSummary the run, which counts the paths that cannot be searched as failures
     *
     * @return list<string> the SVG files, each one once
     */
    private function findFiles(array $paths, Printer $printer, RunSummary $runSummary): array
    {
        $fileFinder = new FileFinder();
        $files = [];

        foreach ($paths as $path) {
            try {
                foreach ($fileFinder->find([$path]) as $file) {
                    $files[$this->identify($file)] ??= $file;
                }
            } catch (\InvalidArgumentException|\UnexpectedValueException $exception) {
                $printer->printError($exception->getMessage());
                $runSummary->addFailure();
            }
        }

        return array_values($files);
    }

    private function identify(string $file): string
    {
        $realPath = realpath($file);

        return false === $realPath ? $file : $realPath;
    }

    private function finish(CommandLine $commandLine, Printer $printer, RunSummary $runSummary): ExitCode
    {
        if ($runSummary->hasFiles()) {
            $printer->printSummary($runSummary);
        } elseif (!$runSummary->hasFailures()) {
            $printer->printNotice('No SVG files found.');
        }

        $changed = $runSummary->getChangedFileCount();
        $changesDetected = $commandLine->isCheckRequested() && $changed > 0;

        if ($changesDetected) {
            $printer->printError(\sprintf('%d %s would change.', $changed, 1 === $changed ? 'file' : 'files'));
        }

        return match (true) {
            $runSummary->hasFailures() => ExitCode::Failure,
            $changesDetected => ExitCode::ChangesDetected,
            default => ExitCode::Success,
        };
    }

    /**
     * @throws \InvalidArgumentException If there is no path or the configuration is not valid
     */
    private function createOptimizer(CommandLine $commandLine): SvgOptimizer
    {
        if ([] === $commandLine->getPaths()) {
            throw new \InvalidArgumentException('No path given. Run with --help to see the usage.');
        }

        $configFile = $this->loadConfig($commandLine->getConfig());
        $rules = [...$configFile->getRules(), ...$commandLine->getRules()];
        $ruleSets = [...$configFile->getRuleSets(), ...$commandLine->getRuleSets()];
        $excludedRules = [
            ...array_filter($configFile->getExcludedRules(), static fn (Rule $rule): bool => !\in_array($rule, $commandLine->getRules(), true)),
            ...$commandLine->getExcludedRules(),
        ];

        $svgOptimizer = (new SvgOptimizer())
            ->withRules(...$rules)
            ->withRuleSets(...$ruleSets)
            ->withoutRules(...$excludedRules)
            ->withRiskyRulesAllowed($commandLine->isRiskyAllowed() || $configFile->isRiskyAllowed());

        return $configFile->isDefaultSelected() || $commandLine->isDefaultSelected() || ([] === $rules && [] === $ruleSets)
            ? $svgOptimizer->withDefaultRules()
            : $svgOptimizer;
    }

    /**
     * @param string $config the value of --config, or an empty string if it was not given
     *
     * @throws \InvalidArgumentException If the configuration is not valid
     */
    private function loadConfig(string $config): ConfigFile
    {
        return match (true) {
            str_starts_with(ltrim($config), '{') || str_starts_with(ltrim($config), '[') => ConfigFile::fromJson($config),
            '' !== $config => ConfigFile::fromFile($config),
            is_file(self::CONFIG_FILE) => ConfigFile::fromFile(self::CONFIG_FILE),
            default => ConfigFile::empty(),
        };
    }
}
