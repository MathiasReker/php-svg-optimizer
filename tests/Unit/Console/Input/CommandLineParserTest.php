<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLine;
use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLineParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionScanner;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleNameTrait;
use MathiasReker\PhpSvgOptimizer\Console\Input\SuggestionTrait;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandLineParser::class)]
#[CoversTrait(RuleNameTrait::class)]
#[CoversTrait(SuggestionTrait::class)]
#[UsesClass(CommandLine::class)]
#[UsesClass(Option::class)]
#[UsesClass(OptionScanner::class)]
final class CommandLineParserTest extends TestCase
{
    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function parsesAnEmptyCommandLine(): void
    {
        $commandLine = $this->parse([]);

        self::assertSame([], $commandLine->getPaths());
        self::assertSame('', $commandLine->getConfig());
        self::assertSame([], $commandLine->getRules());
        self::assertSame([], $commandLine->getExcludedRules());
        self::assertSame([], $commandLine->getRuleSets());
        self::assertFalse($commandLine->isDefaultSelected());
        self::assertFalse($commandLine->isDryRun());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function parsesPathsAndEveryOption(): void
    {
        $commandLine = $this->parse([
            '-c', 'svg-optimizer.json',
            '--sets=default,security',
            '-r', 'minifyColors',
            '--without-rules=removeComments',
            '--allow-risky',
            '-d',
            '--check',
            '--list-rules',
            '-q',
            '-V',
            '-h',
            '--ansi',
            '--no-ansi',
            'icons',
            'logo.svg',
        ]);

        self::assertSame(['icons', 'logo.svg'], $commandLine->getPaths());
        self::assertSame('svg-optimizer.json', $commandLine->getConfig());
        self::assertSame([RuleSet::Security], $commandLine->getRuleSets());
        self::assertTrue($commandLine->isDefaultSelected());
        self::assertSame([Rule::MinifyColors], $commandLine->getRules());
        self::assertSame([Rule::RemoveComments], $commandLine->getExcludedRules());
        self::assertTrue($commandLine->isRiskyAllowed());
        self::assertTrue($commandLine->isDryRun());
        self::assertTrue($commandLine->isCheckRequested());
        self::assertTrue($commandLine->isRuleListRequested());
        self::assertTrue($commandLine->isQuiet());
        self::assertTrue($commandLine->isVersionRequested());
        self::assertTrue($commandLine->isHelpRequested());
        self::assertTrue($commandLine->isColorForced());
        self::assertTrue($commandLine->isColorDisabled());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function joinsTheValuesOfARepeatedOptionAndTrimsTheItems(): void
    {
        $commandLine = $this->parse(['--rules=minifyColors, removeComments', '-r', 'sortAttributes', '-s', 'security', '--sets=cleanup']);

        self::assertSame([Rule::MinifyColors, Rule::RemoveComments, Rule::SortAttributes], $commandLine->getRules());
        self::assertSame([RuleSet::Security, RuleSet::Cleanup], $commandLine->getRuleSets());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function repeatedFlagsAreReportedOnce(): void
    {
        $commandLine = $this->parse(['-d', '--dry-run', '-dd']);

        self::assertTrue($commandLine->isDryRun());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function keepsEverythingAfterTheEndOfOptionsAsPaths(): void
    {
        $commandLine = $this->parse(['--dry-run', '--', '--check', '-x', 'a.svg']);

        self::assertTrue($commandLine->isDryRun());
        self::assertFalse($commandLine->isCheckRequested());
        self::assertSame(['--check', '-x', 'a.svg'], $commandLine->getPaths());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function acceptsOptionsAfterThePaths(): void
    {
        $commandLine = $this->parse(['a.svg', '--sets', 'minification', 'b.svg']);

        self::assertSame(['a.svg', 'b.svg'], $commandLine->getPaths());
        self::assertSame([RuleSet::Minification], $commandLine->getRuleSets());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function selectsDefaultWithoutAnyOtherSet(): void
    {
        $commandLine = $this->parse(['--sets=default']);

        self::assertTrue($commandLine->isDefaultSelected());
        self::assertSame([], $commandLine->getRuleSets());
    }

    /**
     * @param list<string> $arguments
     *
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideParseRejectsInvalidCommandLinesCases')]
    #[Test]
    public function parseRejectsInvalidCommandLines(array $arguments, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->parse($arguments);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideParseRejectsInvalidCommandLinesCases(): iterable
    {
        yield 'an unknown option' => [['--nope'], 'Unknown option "--nope".'];

        yield 'a repeated config' => [['-c', 'a.json', '--config=b.json'], 'Option "--config" can be given only once.'];

        yield 'a config repeated through the short name' => [['-ca.json', '-cb.json'], 'Option "--config" can be given only once.'];

        yield 'an unknown rule' => [['--rules=banana'], 'Unknown rule "banana".'];

        yield 'a rule of 9.x' => [['--rules=removeInkscapeFootprints'], 'Rule "removeInkscapeFootprints" was renamed to "removeEditorMarkup".'];

        yield 'an unknown excluded rule' => [['--without-rules=minifyColors,banana'], 'Unknown rule "banana".'];

        yield 'an unknown set' => [['--sets=banana'], 'Unknown rule set "banana".'];

        yield 'an empty rule' => [['--rules=minifyColors,'], 'Option "--rules" contains an empty value.'];

        yield 'an empty set between commas' => [['--sets=security,,cleanup'], 'Option "--sets" contains an empty value.'];

        yield 'a blank excluded rule' => [['--without-rules= '], 'Option "--without-rules" contains an empty value.'];

        yield 'an option without a value' => [['-r'], 'Option "-r" requires a value.'];

        yield 'the 9.x short flag for risky rules' => [['-r', 'a.svg'], 'Unknown rule "a.svg".'];
    }

    /**
     * @param list<string> $arguments
     *
     * @throws \InvalidArgumentException
     */
    private function parse(array $arguments): CommandLine
    {
        return (new CommandLineParser())->parse($arguments);
    }
}
