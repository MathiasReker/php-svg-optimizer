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
use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CommandLine::class)]
#[UsesClass(Option::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleSet::class)]
final class CommandLineTest extends TestCase
{
    #[Test]
    public function exposesWhatItWasCreatedWith(): void
    {
        $commandLine = new CommandLine(
            ['a.svg', 'icons'],
            [Option::Config],
            '{"allowRisky": true}',
            [Rule::MinifyColors],
            [Rule::RemoveComments],
            [RuleSet::Security],
            true,
        );

        self::assertSame(['a.svg', 'icons'], $commandLine->getPaths());
        self::assertSame('{"allowRisky": true}', $commandLine->getConfig());
        self::assertSame([Rule::MinifyColors], $commandLine->getRules());
        self::assertSame([Rule::RemoveComments], $commandLine->getExcludedRules());
        self::assertSame([RuleSet::Security], $commandLine->getRuleSets());
        self::assertTrue($commandLine->isDefaultSelected());
    }

    #[Test]
    public function reportsNoFlagsWhenNoneWasGiven(): void
    {
        self::assertSame(
            ['isRiskyAllowed' => false, 'isDryRun' => false, 'isCheckRequested' => false, 'isRuleListRequested' => false, 'isQuiet' => false, 'isVersionRequested' => false, 'isHelpRequested' => false, 'isColorForced' => false, 'isColorDisabled' => false, 'isDefaultSelected' => false],
            $this->reportFlags(new CommandLine([], [], '', [], [], [], false)),
        );
    }

    #[DataProvider('provideAFlagIsReportedCases')]
    #[Test]
    public function aFlagIsReported(Option $option, string $reported): void
    {
        $expected = array_merge($this->reportFlags(new CommandLine([], [], '', [], [], [], false)), [$reported => true]);

        self::assertSame($expected, $this->reportFlags(new CommandLine([], [$option], '', [], [], [], false)));
    }

    /**
     * @return iterable<string, array{Option, string}>
     */
    public static function provideAFlagIsReportedCases(): iterable
    {
        yield 'allow risky' => [Option::AllowRisky, 'isRiskyAllowed'];

        yield 'dry run' => [Option::DryRun, 'isDryRun'];

        yield 'check' => [Option::Check, 'isCheckRequested'];

        yield 'list rules' => [Option::ListRules, 'isRuleListRequested'];

        yield 'quiet' => [Option::Quiet, 'isQuiet'];

        yield 'version' => [Option::Version, 'isVersionRequested'];

        yield 'help' => [Option::Help, 'isHelpRequested'];

        yield 'ansi' => [Option::Ansi, 'isColorForced'];

        yield 'no ansi' => [Option::NoAnsi, 'isColorDisabled'];
    }

    /**
     * @return array<string, bool>
     */
    private function reportFlags(CommandLine $commandLine): array
    {
        return [
            'isRiskyAllowed' => $commandLine->isRiskyAllowed(),
            'isDryRun' => $commandLine->isDryRun(),
            'isCheckRequested' => $commandLine->isCheckRequested(),
            'isRuleListRequested' => $commandLine->isRuleListRequested(),
            'isQuiet' => $commandLine->isQuiet(),
            'isVersionRequested' => $commandLine->isVersionRequested(),
            'isHelpRequested' => $commandLine->isHelpRequested(),
            'isColorForced' => $commandLine->isColorForced(),
            'isColorDisabled' => $commandLine->isColorDisabled(),
            'isDefaultSelected' => $commandLine->isDefaultSelected(),
        ];
    }
}
