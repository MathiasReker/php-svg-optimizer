<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output;

use MathiasReker\PhpSvgOptimizer\Console\ExitCode;
use MathiasReker\PhpSvgOptimizer\Console\HelpRenderer;
use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\Console\Output\ByteSizeFormatter;
use MathiasReker\PhpSvgOptimizer\Console\Output\Printer;
use MathiasReker\PhpSvgOptimizer\Console\Output\Style;
use MathiasReker\PhpSvgOptimizer\Console\RunSummary;
use MathiasReker\PhpSvgOptimizer\Metrics;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\Tests\Double\MemoryStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Printer::class)]
#[UsesClass(ByteSizeFormatter::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(ExitCode::class)]
#[UsesClass(HelpRenderer::class)]
#[UsesClass(Metrics::class)]
#[UsesClass(Option::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleSet::class)]
#[UsesClass(RunSummary::class)]
#[UsesClass(Style::class)]
final class PrinterTest extends TestCase
{
    private MemoryStream $output;

    private MemoryStream $error;

    #[\Override]
    protected function setUp(): void
    {
        $this->output = new MemoryStream(false);
        $this->error = new MemoryStream(false);
    }

    #[Test]
    public function printErrorWritesToTheErrorStreamOnly(): void
    {
        $this->createPrinter(false, false)->printError('It failed.');

        self::assertSame('Error: It failed.' . \PHP_EOL, $this->error->getContent());
        self::assertSame('', $this->output->getContent());
    }

    #[Test]
    public function printErrorStylesTheErrorStreamIndependentlyOfTheOutputStream(): void
    {
        $this->createPrinter(false, true)->printError('It failed.');

        self::assertSame("\033[31mError: It failed.\033[0m" . \PHP_EOL, $this->error->getContent());
    }

    #[Test]
    public function printErrorEscapesControlCharactersSoAFileNameCannotDriveTheTerminal(): void
    {
        $this->createPrinter(false, false)->printError("\"a\033]0;x\007\u{9B}b.svg\" is not a valid SVG file.");

        self::assertSame('Error: "a\x1B]0;x\x07\u{9B}b.svg" is not a valid SVG file.' . \PHP_EOL, $this->error->getContent());
    }

    #[Test]
    public function printFileResultEscapesControlCharactersInThePath(): void
    {
        $this->createPrinter(false, false)->printFileResult("a\033[2Jb.svg", 10.0);

        self::assertSame('a\x1B[2Jb.svg (10.00%)' . \PHP_EOL, $this->output->getContent());
    }

    #[Test]
    public function printSkippedFileEscapesControlCharactersInThePath(): void
    {
        $this->createPrinter(false, false)->printSkippedFile("a\rb.svg");

        self::assertSame('a\x0Db.svg (skipped, no gain)' . \PHP_EOL, $this->output->getContent());
    }

    #[Test]
    public function printNoticeWritesToTheOutputStream(): void
    {
        $this->createPrinter(false, false)->printNotice('No SVG files found.');

        self::assertSame('No SVG files found.' . \PHP_EOL, $this->output->getContent());
        self::assertSame('', $this->error->getContent());
    }

    #[Test]
    public function printHelpWritesTheHelp(): void
    {
        $this->createPrinter(false, false)->printHelp(new HelpRenderer());

        self::assertSame((new HelpRenderer())->render(false), $this->output->getContent());
    }

    #[Test]
    public function printHelpStylesTheHelpWhenTheOutputIsStyled(): void
    {
        $this->createPrinter(true, false)->printHelp(new HelpRenderer());

        self::assertSame((new HelpRenderer())->render(true), $this->output->getContent());
    }

    #[Test]
    public function printVersionWritesTheNameTheVersionTheAuthorAndThePhpVersion(): void
    {
        $this->createPrinter(false, false)->printVersion('PHP SVG Optimizer', '10.0.0', 'Mathias Reker');

        self::assertSame(
            'PHP SVG Optimizer 10.0.0 by Mathias Reker and contributors' . \PHP_EOL . 'PHP runtime: ' . \PHP_VERSION . \PHP_EOL,
            $this->output->getContent(),
        );
    }

    #[Test]
    public function printFileResultWritesThePathAndThePercentage(): void
    {
        $this->createPrinter(false, false)->printFileResult('icons/a.svg', 12.345);

        self::assertSame('icons/a.svg (12.35%)' . \PHP_EOL, $this->output->getContent());
    }

    #[Test]
    public function printFileResultStylesAPositivePercentage(): void
    {
        $this->createPrinter(true, false)->printFileResult('a.svg', 50.0);

        self::assertSame("a.svg (\033[32m50.00%\033[0m)" . \PHP_EOL, $this->output->getContent());
    }

    #[Test]
    public function printFileResultDoesNotStyleAPercentageWithoutSavings(): void
    {
        $this->createPrinter(true, false)->printFileResult('a.svg', 0.0);

        self::assertSame('a.svg (0.00%)' . \PHP_EOL, $this->output->getContent());
    }

    #[Test]
    public function printSkippedFileExplainsWhyTheFileWasLeftAlone(): void
    {
        $this->createPrinter(false, false)->printSkippedFile('a.svg');

        self::assertSame('a.svg (skipped, no gain)' . \PHP_EOL, $this->output->getContent());
    }

    #[Test]
    public function printSummaryWritesTheTotals(): void
    {
        $runSummary = new RunSummary();
        $runSummary->add(new Metrics(2_048, 1_024, 0.5));
        $runSummary->add(new Metrics(1_024, 1_024, 0.25));

        $this->createPrinter(false, false)->printSummary($runSummary);

        self::assertSame(
            \PHP_EOL
            . 'Summary:' . \PHP_EOL
            . '  Files:             2' . \PHP_EOL
            . '  Original size:     3.00 KB' . \PHP_EOL
            . '  Optimized size:    2.00 KB' . \PHP_EOL
            . '  Saved:             1.00 KB (33.33%)' . \PHP_EOL
            . '  Optimization time: 0.7500 s' . \PHP_EOL,
            $this->output->getContent(),
        );
    }

    #[Test]
    public function printSummaryStylesTheHeadingWhenTheOutputIsStyled(): void
    {
        $this->createPrinter(true, false)->printSummary(new RunSummary());

        self::assertStringContainsString("\033[1mSummary:\033[0m" . \PHP_EOL, $this->output->getContent());
    }

    #[Test]
    public function printRuleListWritesAnAlignedTableWithEveryRule(): void
    {
        $this->createPrinter(false, false)->printRuleList();

        $lines = explode(\PHP_EOL, rtrim($this->output->getContent(), \PHP_EOL));

        self::assertCount(\count(Rule::cases()) + 1, $lines);
        self::assertMatchesRegularExpression('/^Rule +Set +Risky +Description$/', $lines[0]);
        $descriptionColumn = (int) mb_strpos($lines[0], 'Description');

        foreach (Rule::cases() as $rule) {
            $line = $this->findLine($lines, $rule->value);
            self::assertSame($descriptionColumn, mb_strlen($line) - mb_strlen($rule->getDescription()));
            self::assertStringEndsWith($rule->getDescription(), $line);
            self::assertStringContainsString($rule->isRisky() ? ' yes ' : ' no ', $line);
            self::assertStringContainsString(' ' . ($rule->getRuleSet()->value ?? '-') . ' ', $line);
        }
    }

    #[Test]
    public function printRuleListWritesTheRulesOfEverySetBeforeTheRulesWithoutASet(): void
    {
        $this->createPrinter(false, false)->printRuleList();

        $lines = explode(\PHP_EOL, rtrim($this->output->getContent(), \PHP_EOL));
        $names = array_map(static fn (string $line): string => explode(' ', $line)[0], \array_slice($lines, 1));
        $expected = [
            ...array_merge(...array_map(static fn (RuleSet $ruleSet): array => array_map(static fn (Rule $rule): string => $rule->value, $ruleSet->getRules()), RuleSet::cases())),
            ...array_map(static fn (Rule $rule): string => $rule->value, array_filter(Rule::cases(), static fn (Rule $rule): bool => !$rule->getRuleSet() instanceof RuleSet)),
        ];

        self::assertSame($expected, $names);
    }

    #[Test]
    public function printRuleListStylesOnlyTheHeader(): void
    {
        $this->createPrinter(true, false)->printRuleList();

        $lines = explode(\PHP_EOL, rtrim($this->output->getContent(), \PHP_EOL));

        self::assertStringStartsWith("\033[1mRule", $lines[0]);
        self::assertStringEndsWith("Description\033[0m", $lines[0]);
        self::assertStringNotContainsString("\033", implode('', \array_slice($lines, 1)));
    }

    private function createPrinter(bool $outputColor, bool $errorColor): Printer
    {
        return new Printer($this->output, $this->error, $outputColor, $errorColor);
    }

    /**
     * @param list<string> $lines
     */
    private function findLine(array $lines, string $name): string
    {
        foreach ($lines as $line) {
            if (str_starts_with($line, $name . ' ')) {
                return $line;
            }
        }

        self::fail(\sprintf('No line for rule "%s".', $name));
    }
}
