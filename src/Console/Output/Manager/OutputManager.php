<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Output\Manager;

use MathiasReker\PhpSvgOptimizer\Contract\Console\Output\Stream\StreamInterface;
use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\ByteFormatter;

/**
 * @no-named-arguments
 */
final readonly class OutputManager
{
    /**
     * Constructor for OutputHelper.
     *
     * @param StreamInterface $stream The output stream to write messages to
     */
    public function __construct(
        private StreamInterface $stream,
    ) {}

    /**
     * Print a message to the output stream.
     *
     * @param string $message The message to print
     */
    public function printError(string $message): void
    {
        $this->stream->writeln('Error: ' . $message);
    }

    /**
     * Print help information for the application.
     *
     * This method outputs the usage instructions, available options, commands,
     * and examples for using the PHP SVG Optimizer.
     */
    public function printHelp(): void
    {
        $argumentData = new ArgumentData();

        $this->stream->writeln('PHP SVG Optimizer');
        $this->stream->writeln('');
        $this->stream->writeln('Usage:');
        $this->stream->writeln('  ' . $argumentData->getFormat());
        $this->stream->writeln('');

        $this->stream->writeln('Options:');
        foreach ($argumentData->getOptions() as $argumentOptionValueObject) {
            $this->stream->writeln(\sprintf('  %-3s  %-20s %s', $argumentOptionValueObject->getShorthand(), $argumentOptionValueObject->getFull(), $argumentOptionValueObject->getDescription()));
        }

        $this->stream->writeln('');
        $this->stream->writeln('Commands:');
        $this->stream->writeln('');
        foreach ($argumentData->getCommands() as $optionValueObject) {
            $this->stream->writeln(\sprintf('  %-25s %s', $optionValueObject->getTitle(), $optionValueObject->getDescription()));
        }

        $this->stream->writeln('');
        $this->stream->writeln('Examples:');
        $this->stream->writeln('');
        foreach ($argumentData->getExamples() as $exampleCommandValueObject) {
            $this->stream->writeln('  ' . $exampleCommandValueObject->getCommand());
        }
    }

    /**
     * Print the version information of the application.
     *
     * @param string $name    Name of the application
     * @param string $version Version of the application
     * @param string $author  Author of the application
     */
    public function printVersion(string $name, string $version, string $author): void
    {
        $this->stream->writeln(\sprintf('%s v%s by %s and contributors', $name, $version, $author));
        $this->stream->writeln('PHP runtime: ' . \PHP_VERSION);
    }

    /**
     * Print the result of an SVG optimization.
     *
     * @param string $filePath            Path to the SVG file
     * @param float  $reductionPercentage Percentage of size reduction
     */
    public function printOptimizationResult(string $filePath, float $reductionPercentage): void
    {
        $this->stream->writeln(\sprintf('%s (%.2f%%)', $filePath, $reductionPercentage));
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
    public function printTotalSummary(
        int $fileCount,
        int $originalSize,
        int $optimizedSize,
        int $savedBytes,
        float $savedPercentage,
    ): void {
        $this->stream->writeln('');
        $this->stream->writeln('Summary:');
        $this->stream->writeln(\sprintf('  Files optimized:      %d', $fileCount));
        $this->stream->writeln(\sprintf('  Original total size:  %s', ByteFormatter::formatBytes($originalSize)));
        $this->stream->writeln(\sprintf('  Optimized total size: %s', ByteFormatter::formatBytes($optimizedSize)));
        $this->stream->writeln(\sprintf('  Space saved:          %s (%.2f%%)', ByteFormatter::formatBytes($savedBytes), $savedPercentage));
    }
}
