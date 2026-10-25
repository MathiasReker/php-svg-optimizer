<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Option::class)]
#[UsesClass(RuleSet::class)]
final class OptionTest extends TestCase
{
    #[DataProvider('provideDescribesEveryOptionCases')]
    #[Test]
    public function describesEveryOption(Option $option, string $name, string $shortName, string $valueName, bool $repeatable): void
    {
        self::assertSame($name, $option->value);
        self::assertSame($shortName, $option->getShortName());
        self::assertSame('' !== $valueName, $option->takesValue());
        self::assertSame($valueName, $option->getValueName());
        self::assertSame($repeatable, $option->isRepeatable());
        self::assertMatchesRegularExpression('/^[A-Z].*\.$/', $option->getDescription());
    }

    /**
     * @return iterable<string, array{Option, string, string, string, bool}>
     */
    public static function provideDescribesEveryOptionCases(): iterable
    {
        yield 'config' => [Option::Config, 'config', 'c', 'file|json', false];

        yield 'sets' => [Option::Sets, 'sets', 's', 'sets', true];

        yield 'rules' => [Option::Rules, 'rules', 'r', 'rules', true];

        yield 'without rules' => [Option::WithoutRules, 'without-rules', '', 'rules', true];

        yield 'allow risky' => [Option::AllowRisky, 'allow-risky', '', '', true];

        yield 'dry run' => [Option::DryRun, 'dry-run', 'd', '', true];

        yield 'check' => [Option::Check, 'check', '', '', true];

        yield 'list rules' => [Option::ListRules, 'list-rules', '', '', true];

        yield 'quiet' => [Option::Quiet, 'quiet', 'q', '', true];

        yield 'version' => [Option::Version, 'version', 'V', '', true];

        yield 'help' => [Option::Help, 'help', 'h', '', true];

        yield 'ansi' => [Option::Ansi, 'ansi', '', '', true];

        yield 'no ansi' => [Option::NoAnsi, 'no-ansi', '', '', true];
    }

    #[Test]
    public function coversEveryOption(): void
    {
        self::assertSame(
            array_column(iterator_to_array(self::provideDescribesEveryOptionCases(), false), 0),
            Option::cases(),
        );
    }

    #[Test]
    public function usesEachShortNameOnce(): void
    {
        $shortNames = array_filter(array_map(static fn (Option $option): string => $option->getShortName(), Option::cases()), static fn (string $name): bool => '' !== $name);

        self::assertSame($shortNames, array_unique($shortNames));
    }

    #[Test]
    public function listsTheRuleSetsInTheDescriptionOfTheSetsOption(): void
    {
        $description = Option::Sets->getDescription();

        self::assertStringContainsString('default', $description);

        foreach (RuleSet::cases() as $ruleSet) {
            self::assertStringContainsString($ruleSet->value, $description);
        }
    }
}
