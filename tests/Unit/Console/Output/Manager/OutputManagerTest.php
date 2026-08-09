<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output\Manager;

use MathiasReker\PhpSvgOptimizer\Console\Output\Manager\OutputManager;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\AbstractStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\ByteFormatter;
use MathiasReker\PhpSvgOptimizer\Type\Command;
use MathiasReker\PhpSvgOptimizer\Type\Option;
use MathiasReker\PhpSvgOptimizer\ValueObject\CliOption;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandHelp;
use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OutputManager::class)]
#[CoversClass(AbstractStream::class)]
#[CoversClass(MemoryStream::class)]
#[CoversClass(ByteFormatter::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(Command::class)]
#[CoversClass(Option::class)]
#[CoversClass(CliOption::class)]
#[CoversClass(ExampleCommand::class)]
#[CoversClass(CommandHelp::class)]
final class OutputManagerTest extends TestCase
{
    private MemoryStream $memoryStream;

    private OutputManager $outputManager;

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printError(): void
    {
        $this->outputManager->printError('Something went wrong');
        $output = $this->memoryStream->getContent();

        self::assertSame('Error: Something went wrong' . \PHP_EOL, $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printVersion(): void
    {
        $this->outputManager->printVersion('PHP SVG Optimizer', '1.2.3', 'Mathias Reker');
        $output = $this->memoryStream->getContent();

        $expected = 'PHP SVG Optimizer v1.2.3 by Mathias Reker and contributors' . \PHP_EOL .
            'PHP runtime: ' . \PHP_VERSION . \PHP_EOL;

        self::assertSame($expected, $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printOptimizationResult(): void
    {
        $this->outputManager->printOptimizationResult('file.svg', 42.567_89);
        $output = $this->memoryStream->getContent();

        self::assertSame('file.svg (42.57%)' . \PHP_EOL, $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printOptimizationResultWithColorDisabledNeverAddsAnsiCodes(): void
    {
        $this->outputManager->printOptimizationResult('file.svg', 100.0);
        $output = $this->memoryStream->getContent();

        self::assertSame('file.svg (100.00%)' . \PHP_EOL, $output);
        self::assertStringNotContainsString("\033[", $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printOptimizationResultWithColorEnabledColorsHighPercentageGreen(): void
    {
        $memoryStream = new MemoryStream();
        $outputManager = new OutputManager($memoryStream, true);

        $outputManager->printOptimizationResult('file.svg', 80.0);

        self::assertSame(
            "file.svg (\033[32m80.00%\033[0m)" . \PHP_EOL,
            $memoryStream->getContent()
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printOptimizationResultWithColorEnabledColorsMidPercentageYellow(): void
    {
        $memoryStream = new MemoryStream();
        $outputManager = new OutputManager($memoryStream, true);

        $outputManager->printOptimizationResult('file.svg', 50.0);

        self::assertSame(
            "file.svg (\033[33m50.00%\033[0m)" . \PHP_EOL,
            $memoryStream->getContent()
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printOptimizationResultWithColorEnabledColorsLowPercentageRed(): void
    {
        $memoryStream = new MemoryStream();
        $outputManager = new OutputManager($memoryStream, true);

        $outputManager->printOptimizationResult('file.svg', 49.99);

        self::assertSame(
            "file.svg (\033[31m49.99%\033[0m)" . \PHP_EOL,
            $memoryStream->getContent()
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printErrorWithColorEnabledWrapsMessageInRed(): void
    {
        $memoryStream = new MemoryStream();
        $outputManager = new OutputManager($memoryStream, true);

        $outputManager->printError('Something went wrong');

        self::assertSame(
            "\033[31mError: Something went wrong\033[0m" . \PHP_EOL,
            $memoryStream->getContent()
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printErrorWithColorDisabledNeverAddsAnsiCodes(): void
    {
        $this->outputManager->printError('Something went wrong');

        self::assertStringNotContainsString("\033[", $this->memoryStream->getContent());
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printTotalSummaryWithColorEnabledColorsSavedPercentage(): void
    {
        $memoryStream = new MemoryStream();
        $outputManager = new OutputManager($memoryStream, true);

        $outputManager->printTotalSummary(
            3,
            10_240,
            5_120,
            5_120,
            50.0,
            0.001,
        );

        self::assertStringContainsString(
            "\033[33m50.00%\033[0m",
            $memoryStream->getContent()
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printHelpIncludesOptions(): void
    {
        $this->outputManager->printHelp();
        $output = $this->memoryStream->getContent();

        self::assertStringContainsString('Options:', $output);
        self::assertMatchesRegularExpression('/\s+-h\s+--help\s+.+/', $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printHelp(): void
    {
        $this->outputManager->printHelp();
        $output = $this->memoryStream->getContent();

        self::assertStringContainsString('PHP SVG Optimizer', $output);
        self::assertStringContainsString('Usage:', $output);
        self::assertStringContainsString('Options:', $output);
        self::assertStringContainsString('Commands:', $output);
        self::assertStringContainsString('Examples:', $output);
        self::assertStringContainsString('--help', $output);
        self::assertStringContainsString('--config', $output);
        self::assertStringContainsString('--dry-run', $output);
        self::assertStringContainsString('--quiet', $output);
        self::assertStringContainsString('--version', $output);
        self::assertStringContainsString('process', $output);
        self::assertStringContainsString('vendor/bin/svg-optimizer', $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printHelpIncludesCommands(): void
    {
        $this->outputManager->printHelp();
        $output = $this->memoryStream->getContent();

        self::assertStringContainsString('Commands:', $output);
        self::assertMatchesRegularExpression('/\s+process\s+.+/', $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function printTotalSummary(): void
    {
        $this->outputManager->printTotalSummary(
            3,
            10_240,
            5_120,
            5_120,
            50.0,
            0.001,
        );
        $output = $this->memoryStream->getContent();

        self::assertStringContainsString('Summary:', $output);
        self::assertStringContainsString('Files optimized:      3', $output);
        self::assertStringContainsString('Original total size:', $output);
        self::assertStringContainsString('Optimized total size:', $output);
        self::assertStringContainsString('Space saved:', $output);
        self::assertStringContainsString('(50.00%)', $output);
        self::assertStringContainsString('Optimization time:', $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->memoryStream = new MemoryStream();
        $this->outputManager = new OutputManager($this->memoryStream, false);
    }
}
