<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console;

use Composer\InstalledVersions;
use MathiasReker\PhpSvgOptimizer\Console\Application;
use MathiasReker\PhpSvgOptimizer\Console\ExitCode;
use MathiasReker\PhpSvgOptimizer\Console\HelpRenderer;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLine;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLineParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionScanner;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleNameTrait;
use MathiasReker\PhpSvgOptimizer\Console\Input\SuggestionTrait;
use MathiasReker\PhpSvgOptimizer\Console\Output\Printer;
use MathiasReker\PhpSvgOptimizer\Console\Output\Style;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\Tests\Double\MemoryStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Application::class)]
#[UsesClass(CommandLine::class)]
#[UsesClass(CommandLineParser::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(HelpRenderer::class)]
#[UsesClass(Option::class)]
#[UsesClass(OptionScanner::class)]
#[UsesClass(Printer::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleSet::class)]
#[UsesClass(Style::class)]
#[UsesTrait(RuleNameTrait::class)]
#[UsesTrait(SuggestionTrait::class)]
final class ApplicationTest extends TestCase
{
    /**
     * @param list<string> $arguments
     */
    #[DataProvider('provideTheHelpIsShownCases')]
    #[Test]
    public function theHelpIsShown(array $arguments): void
    {
        $result = $this->execute($arguments);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame((new HelpRenderer())->render(false), $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function provideTheHelpIsShownCases(): iterable
    {
        yield 'without arguments' => [[]];

        yield 'with --help' => [['--help']];

        yield 'with -h' => [['-h']];

        yield 'with --help and other options' => [['--quiet', '--check', 'missing.svg', '--help']];
    }

    #[Test]
    public function theVersionIsShown(): void
    {
        $result = $this->execute(['--version']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertSame(
            'PHP SVG Optimizer ' . ltrim((string) InstalledVersions::getPrettyVersion('mathiasreker/php-svg-optimizer'), 'v') . ' by Mathias Reker and contributors' . \PHP_EOL . 'PHP runtime: ' . \PHP_VERSION . \PHP_EOL,
            $result['stdout'],
        );
    }

    #[Test]
    public function theVersionIgnoresQuietAndTakesPrecedenceOverTheList(): void
    {
        $result = $this->execute(['-qV', '--list-rules']);

        self::assertStringStartsWith('PHP SVG Optimizer ', $result['stdout']);
        self::assertStringNotContainsString('removeComments', $result['stdout']);
    }

    #[Test]
    public function theRulesAreListed(): void
    {
        $result = $this->execute(['--list-rules', '--quiet']);

        self::assertSame(ExitCode::Success, $result['exitCode']);
        self::assertStringStartsWith('Rule ', $result['stdout']);
        self::assertStringContainsString('removeComments', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    #[Test]
    public function aUsageErrorIsPrintedToTheErrorStream(): void
    {
        $result = $this->execute(['--nope']);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertSame('Error: Unknown option "--nope". Run with --help to see valid options.' . \PHP_EOL, $result['stderr']);
    }

    #[Test]
    public function aUsageErrorIsPrintedEvenWithQuiet(): void
    {
        $result = $this->execute(['--quiet', '--rules=banana', 'a.svg']);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertStringContainsString('Unknown rule "banana".', $result['stderr']);
    }

    #[Test]
    public function aMissingPathIsAUsageError(): void
    {
        $result = $this->execute(['--dry-run']);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertSame('Error: No path given. Run with --help to see the usage.' . \PHP_EOL, $result['stderr']);
        self::assertSame('', $result['stdout']);
    }

    #[Test]
    public function theMissingPathIsReportedBeforeTheConfigurationIsRead(): void
    {
        $result = $this->execute(['--config={']);

        self::assertSame(ExitCode::InvalidUsage, $result['exitCode']);
        self::assertSame('Error: No path given. Run with --help to see the usage.' . \PHP_EOL, $result['stderr']);
    }

    #[Test]
    public function errorsAreStyledPerStream(): void
    {
        $result = $this->execute(['--nope'], false, true);

        self::assertSame("\033[31mError: Unknown option \"--nope\". Run with --help to see valid options.\033[0m" . \PHP_EOL, $result['stderr']);
    }

    #[Test]
    public function theHelpIsStyledWhenTheOutputSupportsColor(): void
    {
        $result = $this->execute(['--help'], true, false);

        self::assertSame((new HelpRenderer())->render(true), $result['stdout']);
    }

    #[Test]
    public function ansiForcesStylingOnStreamsWithoutColorSupport(): void
    {
        $result = $this->execute(['--ansi', '--help']);

        self::assertSame((new HelpRenderer())->render(true), $result['stdout']);
    }

    #[Test]
    public function noAnsiDisablesStylingEverywhere(): void
    {
        $help = $this->execute(['--no-ansi', '--help'], true, true);
        $error = $this->execute(['--no-ansi', '--nope'], true, true);

        self::assertSame((new HelpRenderer())->render(false), $help['stdout']);
        self::assertStringNotContainsString("\033", $error['stderr']);
    }

    #[Test]
    public function noAnsiWinsOverAnsi(): void
    {
        $result = $this->execute(['--ansi', '--no-ansi', '--help']);

        self::assertSame((new HelpRenderer())->render(false), $result['stdout']);
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{exitCode: ExitCode, stdout: string, stderr: string}
     */
    private function execute(array $arguments, bool $outputColor = false, bool $errorColor = false): array
    {
        $output = new MemoryStream($outputColor);
        $error = new MemoryStream($errorColor);

        $exitCode = (new Application($output, $error))->run($arguments);

        return ['exitCode' => $exitCode, 'stdout' => $output->getContent(), 'stderr' => $error->getContent()];
    }
}
