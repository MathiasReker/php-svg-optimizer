<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Output;

use MathiasReker\PhpSvgOptimizer\Console\HelpRenderer;
use MathiasReker\PhpSvgOptimizer\Console\RunSummary;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class Printer
{
    private const string NO_RULE_SET = '-';

    private const int COLUMN_GAP = 2;

    /**
     * @param StreamInterface $output      where results and information are written
     * @param StreamInterface $error       where errors are written
     * @param bool            $outputColor whether results and information are styled with ANSI escape sequences
     * @param bool            $errorColor  whether errors are styled with ANSI escape sequences
     */
    public function __construct(
        private StreamInterface $output,
        private StreamInterface $error,
        private bool $outputColor,
        private bool $errorColor,
    ) {}

    /**
     * @param string $message what went wrong
     */
    public function printError(string $message): void
    {
        $this->error->writeLine(Style::Red->apply('Error: ' . $message, $this->errorColor));
    }

    /**
     * @param string $message the information to print
     */
    public function printNotice(string $message): void
    {
        $this->output->writeLine($message);
    }

    public function printHelp(HelpRenderer $helpRenderer): void
    {
        $this->output->write($helpRenderer->render($this->outputColor));
    }

    /**
     * @param string $name    the name of the application
     * @param string $version the version of the application
     * @param string $author  the author of the application
     */
    public function printVersion(string $name, string $version, string $author): void
    {
        $this->output->writeLine(\sprintf('%s %s by %s and contributors', $name, $version, $author));
        $this->output->writeLine('PHP runtime: ' . \PHP_VERSION);
    }

    /**
     * @param string $path            the SVG file
     * @param float  $savedPercentage the saved size as a percentage of the original size
     */
    public function printFileResult(string $path, float $savedPercentage): void
    {
        $this->output->writeLine(\sprintf('%s (%s)', $path, $this->percentage($savedPercentage)));
    }

    /**
     * @param string $path the SVG file whose optimized content is larger than the original
     */
    public function printSkippedFile(string $path): void
    {
        $this->output->writeLine(\sprintf('%s (skipped, no gain)', $path));
    }

    public function printSummary(RunSummary $runSummary): void
    {
        $this->output->writeLine('');
        $this->output->writeLine(Style::Bold->apply('Summary:', $this->outputColor));
        $this->output->writeLine(\sprintf('  Files:             %d', $runSummary->getFileCount()));
        $this->output->writeLine(\sprintf('  Original size:     %s', ByteSizeFormatter::format($runSummary->getOriginalSizeInBytes())));
        $this->output->writeLine(\sprintf('  Optimized size:    %s', ByteSizeFormatter::format($runSummary->getOptimizedSizeInBytes())));
        $this->output->writeLine(\sprintf('  Saved:             %s (%s)', ByteSizeFormatter::format($runSummary->getSavedSizeInBytes()), $this->percentage($runSummary->getSavedPercentage())));
        $this->output->writeLine(\sprintf('  Optimization time: %.4f s', $runSummary->getDurationInSeconds()));
    }

    public function printRuleList(): void
    {
        $rows = [['Rule', 'Set', 'Risky', 'Description']];

        foreach ($this->rulesBySet() as $rule) {
            $rows[] = [$rule->value, $rule->getRuleSet()->value ?? self::NO_RULE_SET, $rule->isRisky() ? 'yes' : 'no', $rule->getDescription()];
        }

        $widths = array_map(static fn (int $column): int => max(array_map(static fn (array $row): int => mb_strlen($row[$column]), $rows)), [0, 1, 2]);

        foreach ($rows as $index => $row) {
            $cells = array_map(static fn (string $cell, int $width): string => $cell . str_repeat(' ', $width - mb_strlen($cell) + self::COLUMN_GAP), \array_slice($row, 0, 3), $widths);
            $line = implode('', $cells) . $row[3];
            $this->output->writeLine(0 === $index ? Style::Bold->apply($line, $this->outputColor) : $line);
        }
    }

    /**
     * @return list<Rule> the rules of every set in the order of the sets and of their execution, then the rules without a set
     */
    private function rulesBySet(): array
    {
        $rules = array_merge(...array_map(static fn (RuleSet $ruleSet): array => $ruleSet->getRules(), RuleSet::cases()));

        return [...$rules, ...array_filter(Rule::cases(), static fn (Rule $rule): bool => !$rule->getRuleSet() instanceof RuleSet)];
    }

    private function percentage(float $percentage): string
    {
        return Style::Green->apply(\sprintf('%.2f%%', $percentage), $percentage > 0.0 && $this->outputColor);
    }
}
