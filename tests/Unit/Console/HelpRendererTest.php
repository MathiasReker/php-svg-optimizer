<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console;

use MathiasReker\PhpSvgOptimizer\Console\ExitCode;
use MathiasReker\PhpSvgOptimizer\Console\HelpRenderer;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLine;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLineParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionScanner;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleNameTrait;
use MathiasReker\PhpSvgOptimizer\Console\Output\Style;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(HelpRenderer::class)]
#[UsesClass(CommandLine::class)]
#[UsesClass(CommandLineParser::class)]
#[UsesClass(ExitCode::class)]
#[UsesClass(Option::class)]
#[UsesClass(OptionScanner::class)]
#[UsesClass(RuleSet::class)]
#[UsesClass(Style::class)]
#[UsesTrait(RuleNameTrait::class)]
final class HelpRendererTest extends TestCase
{
    #[Test]
    public function rendersTheUsageTheOptionsTheExitCodesAndExamples(): void
    {
        $help = (new HelpRenderer())->render(false);

        self::assertStringStartsWith('svg-optimizer' . \PHP_EOL . \PHP_EOL . 'Usage:' . \PHP_EOL . '  svg-optimizer [options] [--] <path>...' . \PHP_EOL . \PHP_EOL . 'Options:' . \PHP_EOL, $help);
        self::assertStringContainsString(\PHP_EOL . 'Exit codes:' . \PHP_EOL . '  0  Success' . \PHP_EOL . '  1  A file failed' . \PHP_EOL . '  2  Invalid usage or configuration' . \PHP_EOL . '  3  --check found a file that would change' . \PHP_EOL, $help);
        self::assertStringContainsString(\PHP_EOL . 'Examples:' . \PHP_EOL . '  svg-optimizer icons' . \PHP_EOL, $help);
        self::assertStringEndsWith(\PHP_EOL, $help);
        self::assertStringNotContainsString("\033", $help);
    }

    #[Test]
    public function listsEveryOptionWithItsDescriptionInAlignedColumns(): void
    {
        $lines = explode(\PHP_EOL, (new HelpRenderer())->render(false));
        $optionLines = \array_slice($lines, 6, \count(Option::cases()));
        $column = null;

        foreach (Option::cases() as $index => $option) {
            $line = $optionLines[$index];
            $column ??= mb_strlen($line) - mb_strlen($option->getDescription());

            self::assertStringEndsWith('  ' . $option->getDescription(), $line);
            self::assertSame($column, mb_strlen($line) - mb_strlen($option->getDescription()));
            self::assertStringContainsString('--' . $option->value, $line);
        }
    }

    #[Test]
    public function writesTheShortNameAndTheValueOfAnOption(): void
    {
        $help = (new HelpRenderer())->render(false);

        self::assertMatchesRegularExpression('/^  -c, --config=<file\|json> +Configuration file or inline JSON\. Defaults to svg-optimizer\.json in the working directory\.\r?$/m', $help);
        self::assertMatchesRegularExpression('/^      --check +Write nothing and exit with code 3 if a file would change\.\r?$/m', $help);
        self::assertMatchesRegularExpression('/^  -V, --version +Show the version\.\r?$/m', $help);
        self::assertMatchesRegularExpression('/^  -h, --help +Show this help\.\r?$/m', $help);
    }

    #[Test]
    public function stylesTheHeadingsAndTheOptionsWhenAnsiIsEnabled(): void
    {
        $help = (new HelpRenderer())->render(true);

        self::assertStringContainsString("\033[1mUsage:\033[0m", $help);
        self::assertStringContainsString("\033[1mOptions:\033[0m", $help);
        self::assertStringContainsString("\033[1mExit codes:\033[0m", $help);
        self::assertStringContainsString("\033[1mExamples:\033[0m", $help);
        self::assertStringContainsString("  \033[36m-c, --config=<file|json>\033[0m", $help);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function everyExampleIsAValidCommandLine(): void
    {
        $examples = array_filter(explode(\PHP_EOL, (new HelpRenderer())->render(false)), static fn (string $line): bool => str_starts_with($line, '  svg-optimizer '));

        self::assertNotSame([], $examples);

        foreach ($examples as $example) {
            $arguments = explode(' ', trim($example));
            $commandLine = (new CommandLineParser())->parse(\array_slice($arguments, 1));

            self::assertNotSame([], $commandLine->getPaths(), $example);
        }
    }
}
