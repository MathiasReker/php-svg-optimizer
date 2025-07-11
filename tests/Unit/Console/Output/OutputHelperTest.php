<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output;

use MathiasReker\PhpSvgOptimizer\Console\Input\Stream\AbstractStreamOutput;
use MathiasReker\PhpSvgOptimizer\Console\Input\Stream\MemoryStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\OutputHelper;
use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\Formatter;
use MathiasReker\PhpSvgOptimizer\Type\Command;
use MathiasReker\PhpSvgOptimizer\Type\Option;
use MathiasReker\PhpSvgOptimizer\ValueObject\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommandValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\OptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OutputHelper::class)]
#[CoversClass(AbstractStreamOutput::class)]
#[CoversClass(MemoryStream::class)]
#[CoversClass(Formatter::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(Command::class)]
#[CoversClass(Option::class)]
#[CoversClass(ArgumentOptionValueObject::class)]
#[CoversClass(ExampleCommandValueObject::class)]
#[CoversClass(OptionValueObject::class)]
final class OutputHelperTest extends TestCase
{
    private MemoryStream $memoryStream;
    private OutputHelper $outputHelper;

    /**
     * @throws \RuntimeException
     */
    public function testPrintError(): void
    {
        $this->outputHelper->printError('Something went wrong');
        $output = $this->memoryStream->getContents();

        self::assertSame('Error: Something went wrong' . \PHP_EOL, $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testPrintVersion(): void
    {
        $this->outputHelper->printVersion('PHP SVG Optimizer', '1.2.3', 'Mathias Reker');
        $output = $this->memoryStream->getContents();

        $expected = 'PHP SVG Optimizer v1.2.3 by Mathias Reker and contributors' . \PHP_EOL .
            'PHP runtime: ' . \PHP_VERSION . \PHP_EOL;

        self::assertSame($expected, $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testPrintOptimizationResult(): void
    {
        $this->outputHelper->printOptimizationResult('file.svg', 42.567_89);
        $output = $this->memoryStream->getContents();

        self::assertSame('file.svg (42.57%)' . \PHP_EOL, $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testPrintHelp(): void
    {
        $this->outputHelper->printHelp();
        $output = $this->memoryStream->getContents();

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
    public function testPrintHelpIncludesOptions(): void
    {
        $this->outputHelper->printHelp();
        $output = $this->memoryStream->getContents();

        self::assertStringContainsString('Options:', $output);
        self::assertMatchesRegularExpression('/\s+-h\s+--help\s+.+/', $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testPrintHelpIncludesCommands(): void
    {
        $this->outputHelper->printHelp();
        $output = $this->memoryStream->getContents();

        self::assertStringContainsString('Commands:', $output);
        self::assertMatchesRegularExpression('/\s+process\s+.+/', $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testPrintTotalSummary(): void
    {
        $this->outputHelper->printTotalSummary(
            3,
            10_240,
            5_120,
            5_120,
            50.0
        );
        $output = $this->memoryStream->getContents();

        self::assertStringContainsString('Summary:', $output);
        self::assertStringContainsString('Files optimized:      3', $output);
        self::assertStringContainsString('Original total size:', $output);
        self::assertStringContainsString('Optimized total size:', $output);
        self::assertStringContainsString('Space saved:', $output);
        self::assertStringContainsString('(50.00%)', $output);
    }

    /**
     * @throws \RuntimeException
     */
    protected function setUp(): void
    {
        $this->memoryStream = new MemoryStream();
        $this->outputHelper = new OutputHelper($this->memoryStream);
    }
}
