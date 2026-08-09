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
    private const string COLOR_GREEN = "\033[32m";

    private const string COLOR_YELLOW = "\033[33m";

    private const string COLOR_RED = "\033[31m";

    private const string COLOR_RESET = "\033[0m";

    /**
     * @param StreamInterface $stream       The output stream to write messages to
     * @param bool            $colorEnabled Whether ANSI color output should be used
     */
    public function __construct(
        private StreamInterface $stream,
        private bool $colorEnabled,
    ) {}

    /**
     * @param string $message The message to print
     */
    public function printError(string $message): void
    {
        $this->stream->writeln($this->colorize('Error: ' . $message, self::COLOR_RED));
    }

    public function printHelp(): void
    {
        $argumentData = new ArgumentData();

        $this->stream->writeln('PHP SVG Optimizer');
        $this->stream->writeln('');
        $this->stream->writeln('Usage:');
        $this->stream->writeln('  ' . $argumentData->getFormat());
        $this->stream->writeln('');

        $this->stream->writeln('Options:');
        foreach ($argumentData->getOptions() as $cliOption) {
            $this->stream->writeln(\sprintf('  %-3s  %-20s %s', $cliOption->getShorthand(), $cliOption->getFull(), $cliOption->getDescription()));
        }

        $this->stream->writeln('');
        $this->stream->writeln('Commands:');
        $this->stream->writeln('');
        foreach ($argumentData->getCommands() as $commandHelp) {
            $this->stream->writeln(\sprintf('  %-25s %s', $commandHelp->getTitle(), $commandHelp->getDescription()));
        }

        $this->stream->writeln('');
        $this->stream->writeln('Examples:');
        $this->stream->writeln('');
        foreach ($argumentData->getExamples() as $exampleCommandValueObject) {
            $this->stream->writeln('  ' . $exampleCommandValueObject->getCommand());
        }
    }

    /**
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
     * @param string $filePath            Path to the SVG file
     * @param float  $reductionPercentage Percentage of size reduction
     */
    public function printOptimizationResult(string $filePath, float $reductionPercentage): void
    {
        $this->stream->writeln(\sprintf('%s (%s)', $filePath, $this->formatPercentage($reductionPercentage)));
    }

    /**
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
        float $optimizationTime,
    ): void {
        $this->stream->writeln('');
        $this->stream->writeln('Summary:');
        $this->stream->writeln(\sprintf('  Files optimized:      %d', $fileCount));
        $this->stream->writeln(\sprintf('  Original total size:  %s', ByteFormatter::formatBytes($originalSize)));
        $this->stream->writeln(\sprintf('  Optimized total size: %s', ByteFormatter::formatBytes($optimizedSize)));
        $this->stream->writeln(\sprintf('  Space saved:          %s (%s)', ByteFormatter::formatBytes($savedBytes), $this->formatPercentage($savedPercentage)));
        $this->stream->writeln(\sprintf('  Optimization time:    %.4f s', $optimizationTime));
    }

    /**
     * @param float $percentage The percentage value to format
     */
    private function formatPercentage(float $percentage): string
    {
        $formatted = \sprintf('%.2f%%', $percentage);

        $color = match (true) {
            $percentage >= 80.0 => self::COLOR_GREEN,
            $percentage >= 50.0 => self::COLOR_YELLOW,
            default => self::COLOR_RED,
        };

        return $this->colorize($formatted, $color);
    }

    /**
     * @param string $text  The text to colorize
     * @param string $color The ANSI color code to wrap the text with
     */
    private function colorize(string $text, string $color): string
    {
        if (!$this->colorEnabled) {
            return $text;
        }

        return $color . $text . self::COLOR_RESET;
    }
}
