<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\CommandLineParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleNameTrait;
use MathiasReker\PhpSvgOptimizer\Console\Input\SuggestionTrait;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversTrait(RuleNameTrait::class)]
#[CoversTrait(SuggestionTrait::class)]
#[UsesClass(CommandLineParser::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleSet::class)]
final class RuleNameTraitTest extends TestCase
{
    use RuleNameTrait;

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function parseRulesReturnsTheRulesInTheOrderGiven(): void
    {
        self::assertSame([Rule::MinifyColors, Rule::RemoveComments], self::parseRules(['minifyColors', 'removeComments']));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideParseRulesRejectsAnInvalidNameCases')]
    #[Test]
    public function parseRulesRejectsAnInvalidName(string $name, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        self::parseRules(['minifyColors', $name]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideParseRulesRejectsAnInvalidNameCases(): iterable
    {
        yield 'a renamed rule' => ['convertColorsToHex', 'Rule "convertColorsToHex" was renamed to "minifyColors".'];

        yield 'a merged rule' => ['removeEmptyGroups', 'Rule "removeEmptyGroups" was merged into "removeEmptyElements".'];

        yield 'a split rule' => ['removeTitleAndDesc', 'Rule "removeTitleAndDesc" was split into "removeTitles" and "removeDescriptions".'];

        yield 'a removed rule' => ['removeDoctype', 'Rule "removeDoctype" was removed; it had no effect.'];

        yield 'a misspelled rule' => ['removeComment', 'Unknown rule "removeComment". Did you mean "removeComments"? Run svg-optimizer --list-rules to see the rules.'];

        yield 'an unknown rule' => ['banana', 'Unknown rule "banana". Run svg-optimizer --list-rules to see the rules.'];

        yield 'a rule of another letter case' => ['MinifyColors', 'Unknown rule "MinifyColors". Did you mean "minifyColors"?'];
    }

    #[DataProvider('provideEveryLegacyNameReplacementNamesCurrentRulesCases')]
    #[Test]
    public function everyLegacyNameReplacementNamesCurrentRules(string $old, string $message): void
    {
        self::assertNull(Rule::tryFrom($old), \sprintf('"%s" is still a rule.', $old));
        preg_match_all('/"(\w+)"/', $message, $quoted);

        foreach ($quoted[1] as $name) {
            self::assertTrue(null !== Rule::tryFrom($name) || null !== RuleSet::tryFrom($name), \sprintf('"%s" in the message for "%s" is not a rule or rule set.', $name, $old));
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideEveryLegacyNameReplacementNamesCurrentRulesCases(): iterable
    {
        foreach (self::LEGACY_RULES as $old => $message) {
            yield $old => [$old, $message];
        }
    }

    #[DataProvider('provideIsRuleNameKnowsTheNamesOfThisVersionAndOf9xCases')]
    #[Test]
    public function isRuleNameKnowsTheNamesOfThisVersionAndOf9x(string $name, bool $expected): void
    {
        self::assertSame($expected, self::isRuleName($name));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideIsRuleNameKnowsTheNamesOfThisVersionAndOf9xCases(): iterable
    {
        yield 'a rule' => ['minifyColors', true];

        yield 'a rule of 9.x' => ['convertColorsToHex', true];

        yield 'a key of the configuration' => ['rules', false];

        yield 'anything else' => ['banana', false];
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function parseRuleSetsReturnsTheRuleSetsWithoutDefault(): void
    {
        self::assertSame([RuleSet::Security, RuleSet::Minification], self::parseRuleSets(['security', 'default', 'minification']));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideParseRuleSetsRejectsAnInvalidNameCases')]
    #[Test]
    public function parseRuleSetsRejectsAnInvalidName(string $name, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        self::parseRuleSets([$name]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideParseRuleSetsRejectsAnInvalidNameCases(): iterable
    {
        yield 'an unknown set' => ['banana', 'Unknown rule set "banana". Valid rule sets are: default, security, standards, cleanup, conversion, structure, minification.'];

        yield 'a misspelled set' => ['securty', 'Unknown rule set "securty". Did you mean "security"? Valid rule sets are:'];
    }

    /**
     * @param list<string> $names
     */
    #[DataProvider('provideIncludesDefaultSetFindsTheDefaultAliasCases')]
    #[Test]
    public function includesDefaultSetFindsTheDefaultAlias(array $names, bool $expected): void
    {
        self::assertSame($expected, self::includesDefaultSet($names));
    }

    /**
     * @return iterable<string, array{list<string>, bool}>
     */
    public static function provideIncludesDefaultSetFindsTheDefaultAliasCases(): iterable
    {
        yield 'with default' => [['security', 'default'], true];

        yield 'without default' => [['security'], false];

        yield 'nothing' => [[], false];
    }
}
