<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Type;

use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
use MathiasReker\PhpSvgOptimizer\Type\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RuleSet::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Rule::class)]
final class RuleSetTest extends TestCase
{
    private const array RULES_IN_NO_SET = [
        Rule::RemoveTitles,
        Rule::RemoveDescriptions,
        Rule::RemoveAriaAndRole,
        Rule::ScopeSvgStyles,
    ];

    /**
     * @param list<Rule> $rules The exact rules of the set, in execution order
     */
    #[DataProvider('provideRulesCases')]
    #[Test]
    public function rules(RuleSet $ruleSet, string $value, array $rules): void
    {
        self::assertSame($value, $ruleSet->value);
        self::assertSame($rules, $ruleSet->rules());
    }

    /**
     * @return iterable<string, array{RuleSet, string, list<Rule>}>
     */
    public static function provideRulesCases(): iterable
    {
        yield 'security' => [RuleSet::Security, 'security', [
            Rule::RemoveUnsafeMarkup,
            Rule::RemoveScripts,
            Rule::RemoveForeignContent,
            Rule::RemoveUnsafeUrls,
            Rule::SanitizeStyleSheets,
            Rule::RemoveUseBombs,
        ]];

        yield 'standards' => [RuleSet::Standards, 'standards', [
            Rule::FixAttributeNames,
            Rule::MigrateToSvg2,
            Rule::RemoveNonStandardTags,
            Rule::RemoveNonStandardAttributes,
        ]];

        yield 'cleanup' => [RuleSet::Cleanup, 'cleanup', [
            Rule::RemoveComments,
            Rule::RemoveMetadata,
            Rule::RemoveInkscapeFootprints,
            Rule::RemoveUnusedIds,
            Rule::RemoveDataAttributes,
            Rule::RemoveInvisibleCharacters,
            Rule::RemoveDefaultAttributes,
            Rule::RemoveWidthHeightAttributes,
            Rule::RemoveEmptyAttributes,
            Rule::RemoveIneffectiveAttributes,
            Rule::RemoveHiddenElements,
            Rule::RemoveUselessDefs,
            Rule::RemoveDuplicateElements,
            Rule::RemoveEmptyElements,
            Rule::RemoveUnusedDefinitions,
            Rule::RemoveUnusedNamespaces,
        ]];

        yield 'conversion' => [RuleSet::Conversion, 'conversion', [
            Rule::ConvertCssClassesToAttributes,
            Rule::ConvertInlineStylesToAttributes,
            Rule::ConvertColorsToHex,
            Rule::ConvertOneStopGradients,
            Rule::ConvertEllipseToCircle,
            Rule::ConvertShapesToPath,
        ]];

        yield 'structure' => [RuleSet::Structure, 'structure', [
            Rule::MergeStyles,
            Rule::MergeDuplicateGradients,
            Rule::FlattenGroups,
            Rule::MoveGroupAttributesToElements,
            Rule::MoveCommonAttributesToGroup,
        ]];

        yield 'minification' => [RuleSet::Minification, 'minification', [
            Rule::MinifyStyles,
            Rule::MinifyNumericValues,
            Rule::MinifyTransformations,
            Rule::MinifyPathData,
            Rule::RemoveUnnecessaryWhitespace,
            Rule::SortDefsChildren,
            Rule::SortAttributes,
        ]];
    }

    #[Test]
    public function everySetIsTested(): void
    {
        self::assertSame(
            array_map(static fn (array $case): RuleSet => $case[0], iterator_to_array(self::provideRulesCases(), false)),
            RuleSet::cases(),
        );
    }

    #[Test]
    public function theSetsAreDisjoint(): void
    {
        $rules = array_merge(...array_map(static fn (RuleSet $ruleSet): array => $ruleSet->rules(), RuleSet::cases()));

        self::assertSame(array_values(array_unique(array_map(static fn (Rule $rule): string => $rule->name, $rules))), array_map(static fn (Rule $rule): string => $rule->name, $rules));
    }

    #[Test]
    public function everyRuleIsInASetOrDeliberatelyInNone(): void
    {
        $rules = [...self::RULES_IN_NO_SET, ...array_merge(...array_map(static fn (RuleSet $ruleSet): array => $ruleSet->rules(), RuleSet::cases()))];

        $names = array_map(static fn (Rule $rule): string => $rule->name, $rules);
        $expectedNames = array_map(static fn (Rule $rule): string => $rule->name, Rule::cases());
        sort($names);
        sort($expectedNames);

        self::assertSame($expectedNames, $names);
    }

    #[Test]
    public function theRulesOfEverySetAreInTheOrderOfTheirFirstPass(): void
    {
        foreach (RuleSet::cases() as $ruleSet) {
            $priorities = array_map(
                static fn (Rule $rule): int => min(array_map(
                    static fn (string $pass): int => (int) array_search($pass, ExecutionOrder::passes(), true),
                    $rule->passes(),
                )),
                $ruleSet->rules(),
            );
            $sortedPriorities = $priorities;
            sort($sortedPriorities);

            self::assertSame($sortedPriorities, $priorities, $ruleSet->value);
        }
    }

    #[Test]
    public function containsTellsWhetherTheRuleIsInTheSet(): void
    {
        foreach (RuleSet::cases() as $ruleSet) {
            foreach (Rule::cases() as $rule) {
                self::assertSame(\in_array($rule, $ruleSet->rules(), true), $ruleSet->contains($rule), $ruleSet->value . ' ' . $rule->name);
            }
        }

        self::assertTrue(RuleSet::Cleanup->contains(Rule::RemoveComments));
        self::assertFalse(RuleSet::Cleanup->contains(Rule::SortAttributes));
    }

    #[Test]
    public function theRulesInNoSetAreInNoSet(): void
    {
        foreach (RuleSet::cases() as $ruleSet) {
            foreach (self::RULES_IN_NO_SET as $rule) {
                self::assertFalse($ruleSet->contains($rule), $ruleSet->value . ' ' . $rule->name);
            }
        }
    }
}
