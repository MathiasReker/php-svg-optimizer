<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Commands\Helpers;

use MathiasReker\PhpSvgOptimizer\Services\Data\ArgumentData;

/**
 * @no-named-arguments
 */
final readonly class OutputHelper
{
    /**
     * The default precision used for percentage formatting.
     */
    private const int DEFAULT_PRECISION = 2;

    /**
     * Print an error message to the console.
     *
     * @param string $message The error message to display
     */
    public static function printError(string $message): void
    {
        printf('Error: %s%s', $message, \PHP_EOL);
    }

    /**
     * Print the help screen, including usage, options, commands, and examples.
     */
    public static function printHelp(): void
    {
        $argumentData = new ArgumentData();

        printf('PHP SVG Optimizer%s%s', \PHP_EOL, \PHP_EOL);
        printf('Usage:%s', \PHP_EOL);
        printf('  %s%s%s', $argumentData->getFormat(), \PHP_EOL, \PHP_EOL);
        printf('Options:%s', \PHP_EOL);

        foreach ($argumentData->getOptions() as $argumentOptionValueObject) {
            printf(
                '  %-3s, %-20s %s' . \PHP_EOL,
                $argumentOptionValueObject->getShorthand(),
                $argumentOptionValueObject->getFull(),
                $argumentOptionValueObject->getDescription()
            );
        }

        printf('%sCommands:%s', \PHP_EOL, \PHP_EOL);
        foreach ($argumentData->getCommands() as $commandOptionValueObject) {
            printf(
                '  %-25s %-3s' . \PHP_EOL,
                $commandOptionValueObject->getTitle(),
                $commandOptionValueObject->getDescription()
            );
        }

        printf('%sExamples:%s', \PHP_EOL, \PHP_EOL);
        foreach ($argumentData->getExamples() as $example) {
            printf('  %s%s', $example->getCommand(), \PHP_EOL);
        }
    }

    /**
     * Print the current version of the application.
     *
     * @param string $version The version string to print
     */
    public static function printVersion(string $version): void
    {
        printf('PHP SVG Optimizer v%s%s', $version, \PHP_EOL);
    }

    /**
     * Print the optimization result for a single file.
     *
     * @param string $filePath            The path to the file
     * @param float  $reductionPercentage The percentage of size reduction
     */
    public static function printOptimizationResult(string $filePath, float $reductionPercentage): void
    {
        printf(
            '%s (%s%%%s)%s',
            $filePath,
            number_format($reductionPercentage, self::DEFAULT_PRECISION),
            '',
            \PHP_EOL
        );
    }
}
