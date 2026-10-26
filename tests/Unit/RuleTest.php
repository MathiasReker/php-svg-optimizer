<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit;

use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateToSvg2;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyColors;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumberLists;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultXmlSpace;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEnableBackground;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveTextAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(Rule::class)]
#[UsesClass(ExecutionOrder::class)]
final class RuleTest extends TestCase
{
    private const array RISKY_RULES = [
        Rule::ConvertOneStopGradientsToColors,
        Rule::FixAttributeNames,
        Rule::MergeDuplicateGradients,
        Rule::RemoveDataAttributes,
        Rule::RemoveUnusedDefinitions,
        Rule::RemoveUnusedIds,
        Rule::RemoveWidthHeightAttributes,
        Rule::ScopeIdsAndClasses,
    ];

    #[Test]
    public function theValueOfEveryRuleIsItsConfigurationKey(): void
    {
        foreach (Rule::cases() as $rule) {
            self::assertSame(lcfirst($rule->name), $rule->value, $rule->name);
        }
    }

    #[Test]
    public function everyRuleHasADescription(): void
    {
        foreach (Rule::cases() as $rule) {
            self::assertStringEndsWith('.', $rule->getDescription(), $rule->name);
            self::assertMatchesRegularExpression('/^[A-Z]/', $rule->getDescription(), $rule->name);
        }
    }

    #[Test]
    public function getRuleSetIsNullForTheRulesInNoSet(): void
    {
        foreach ([Rule::ScopeIdsAndClasses, Rule::RemoveAriaAttributes, Rule::RemoveTitles, Rule::RemoveDescriptions] as $rule) {
            self::assertNull($rule->getRuleSet(), $rule->name);
        }
    }

    #[Test]
    public function describesTheAriaRuleWithTheRoleAttribute(): void
    {
        self::assertSame('Remove role and aria-* attributes.', Rule::RemoveAriaAttributes->getDescription());
    }

    /**
     * @param list<Rule> $rules the rules of the set
     */
    #[DataProvider('provideEachRuleBelongsToItsSetCases')]
    #[Test]
    public function eachRuleBelongsToItsSet(RuleSet $ruleSet, array $rules): void
    {
        foreach ($rules as $rule) {
            self::assertSame($ruleSet, $rule->getRuleSet(), $rule->name);
        }
    }

    /**
     * @return iterable<string, array{RuleSet, list<Rule>}>
     */
    public static function provideEachRuleBelongsToItsSetCases(): iterable
    {
        yield 'security' => [RuleSet::Security, [Rule::RemoveUnsafeMarkup, Rule::RemoveScripts, Rule::RemoveForeignContent, Rule::RemoveUnsafeUrls, Rule::RemoveUnsafeStyles, Rule::RemoveUseBombs]];

        yield 'standards' => [RuleSet::Standards, [Rule::FixAttributeNames, Rule::MigrateToSvg2, Rule::RemoveNonStandardElements, Rule::RemoveNonStandardAttributes]];

        yield 'cleanup' => [RuleSet::Cleanup, [Rule::RemoveComments, Rule::RemoveMetadata, Rule::RemoveEditorMarkup, Rule::RemoveUnusedIds, Rule::RemoveDataAttributes, Rule::RemoveInvisibleCharacters, Rule::RemoveDefaultAttributes, Rule::RemoveWidthHeightAttributes, Rule::RemoveEmptyAttributes, Rule::RemoveIneffectiveAttributes, Rule::RemoveHiddenElements, Rule::RemoveIneffectiveDefinitions, Rule::RemoveDuplicateElements, Rule::RemoveEmptyElements, Rule::RemoveUnusedDefinitions, Rule::RemoveUnusedNamespaces]];

        yield 'conversion' => [RuleSet::Conversion, [Rule::ConvertCssClassesToAttributes, Rule::ConvertInlineStylesToAttributes, Rule::MinifyColors, Rule::ConvertOneStopGradientsToColors, Rule::ConvertEllipsesToCircles, Rule::ConvertShapesToPaths]];

        yield 'structure' => [RuleSet::Structure, [Rule::MergeStyleSheets, Rule::MergeDuplicateGradients, Rule::FlattenGroups, Rule::MoveGroupTransformsToChildren, Rule::MoveCommonAttributesToGroups]];

        yield 'minification' => [RuleSet::Minification, [Rule::MinifyStyleSheets, Rule::MinifyNumericValues, Rule::MinifyTransforms, Rule::MinifyPathData, Rule::MinifyAttributeWhitespace, Rule::SortDefinitions, Rule::SortAttributes]];
    }

    #[Test]
    public function exactlyTheRiskyRulesAreRisky(): void
    {
        foreach (Rule::cases() as $rule) {
            self::assertSame(\in_array($rule, self::RISKY_RULES, true), $rule->isRisky(), $rule->name);
        }
    }

    #[Test]
    public function everyPassBelongsToExactlyOneRule(): void
    {
        $passes = array_merge(...array_map(static fn (Rule $rule): array => $rule->getPassClasses(), Rule::cases()));
        sort($passes);
        $expected = ExecutionOrder::passes();
        sort($expected);

        self::assertSame($expected, $passes);
    }

    /**
     * @param list<class-string> $passes the passes the rule runs
     */
    #[DataProvider('provideEachRuleRunsItsPassesCases')]
    #[Test]
    public function eachRuleRunsItsPasses(Rule $rule, array $passes): void
    {
        self::assertSame($passes, $rule->getPassClasses());
    }

    /**
     * @return iterable<string, array{Rule, list<class-string>}>
     */
    public static function provideEachRuleRunsItsPassesCases(): iterable
    {
        yield 'migrateToSvg2' => [Rule::MigrateToSvg2, [MigrateToSvg2::class, MigrateDeprecatedAttributes::class, RemoveEnableBackground::class]];

        yield 'minifyNumericValues' => [Rule::MinifyNumericValues, [MinifyNumericValues::class, MinifyNumberLists::class]];

        yield 'removeDefaultAttributes' => [Rule::RemoveDefaultAttributes, [RemoveDefaultAttributes::class, RemoveDefaultTypeAttributes::class, RemoveDefaultXmlSpace::class]];

        yield 'removeEmptyElements' => [Rule::RemoveEmptyElements, [RemoveEmptyContainers::class, RemoveEmptyTextElements::class]];

        yield 'removeHiddenElements' => [Rule::RemoveHiddenElements, [RemoveHiddenElements::class, RemoveZeroSizeShapes::class]];

        yield 'removeIneffectiveAttributes' => [Rule::RemoveIneffectiveAttributes, [RemoveIneffectiveStrokeAndFill::class, RemoveIneffectiveGroupAttributes::class, RemoveIneffectiveTextAttributes::class]];

        yield 'a rule with one pass' => [Rule::MinifyColors, [MinifyColors::class]];
    }
}
