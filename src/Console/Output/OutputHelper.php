<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Output;

use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\Formatter;

/**
 * @no-named-arguments
 */
final readonly class OutputHelper
{
    /**
     * Print an error message to the console.
     *
     * @param string $message The error message to print
     */
    public static function printError(string $message): void
    {
        printf('Error: %s%s', $message, \PHP_EOL);
    }

    /**
     * Print the help information for the application.
     */
    public static function printHelp(): void
    {
        $argumentData = new ArgumentData();

        printf('PHP SVG Optimizer%s%s', \PHP_EOL, \PHP_EOL);
        printf('Usage:%s', \PHP_EOL);
        printf('  %s%s%s', $argumentData->getFormat(), \PHP_EOL, \PHP_EOL);

        printf('Options:%s', \PHP_EOL);
        foreach ($argumentData->getOptions() as $opt) {
            printf('  %-3s  %-20s %s%s', $opt->getShorthand(), $opt->getFull(), $opt->getDescription(), \PHP_EOL);
        }

        printf('%sCommands:%s%s', \PHP_EOL, \PHP_EOL, \PHP_EOL);
        foreach ($argumentData->getCommands() as $cmd) {
            printf('  %-25s %s%s', $cmd->getTitle(), $cmd->getDescription(), \PHP_EOL);
        }

        printf('%sExamples:%s%s', \PHP_EOL, \PHP_EOL, \PHP_EOL);
        foreach ($argumentData->getExamples() as $example) {
            printf('  %s%s', $example->getCommand(), \PHP_EOL);
        }
    }

    /**
     * Print the version information of the application.
     *
     * @param string $name    Name of the application
     * @param string $version Version of the application
     * @param string $author  Author of the application
     */
    public static function printVersion(string $name, string $version, string $author): void
    {
        printf(
            '%s v%s by %s and contributors%sPHP runtime: %s%s',
            $name,
            $version,
            $author,
            \PHP_EOL,
            \PHP_VERSION,
            \PHP_EOL
        );
    }

    /**
     * Print the result of an SVG optimization.
     *
     * @param string $filePath            Path to the SVG file
     * @param float  $reductionPercentage Percentage of size reduction
     */
    public static function printOptimizationResult(string $filePath, float $reductionPercentage): void
    {
        printf('%s (%.2f%%%s)%s', $filePath, $reductionPercentage, '', \PHP_EOL);
    }

    /**
     * Print a summary of the optimization results.
     *
     * @param int   $fileCount       Number of files optimized
     * @param int   $originalSize    Total original size in bytes
     * @param int   $optimizedSize   Total optimized size in bytes
     * @param int   $savedBytes      Total bytes saved
     * @param float $savedPercentage Percentage of space saved
     */
    public static function printTotalSummary(
        int $fileCount,
        int $originalSize,
        int $optimizedSize,
        int $savedBytes,
        float $savedPercentage,
    ): void {
        printf('%sSummary:%s', \PHP_EOL, \PHP_EOL);
        printf('  Files optimized:      %d%s', $fileCount, \PHP_EOL);
        printf('  Original total size:  %s%s', Formatter::formatBytes($originalSize), \PHP_EOL);
        printf('  Optimized total size: %s%s', Formatter::formatBytes($optimizedSize), \PHP_EOL);
        printf(
            '  Space saved:          %s (%.2f%%%s)%s',
            Formatter::formatBytes($savedBytes),
            $savedPercentage,
            '',
            \PHP_EOL
        );
    }
}
