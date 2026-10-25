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
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MigrateToSvg2;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonInheritableGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveStyleTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedTextAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveXmlSpaceDefault;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Type\Rule;
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
        Rule::ConvertOneStopGradients,
        Rule::FixAttributeNames,
        Rule::MergeDuplicateGradients,
        Rule::RemoveDataAttributes,
        Rule::RemoveUnusedDefinitions,
        Rule::RemoveUnusedIds,
        Rule::RemoveWidthHeightAttributes,
        Rule::ScopeSvgStyles,
    ];

    #[Test]
    public function configKeysAreCorrect(): void
    {
        $expected = [
            Rule::ConvertColorsToHex->name => 'convertColorsToHex',
            Rule::ConvertCssClassesToAttributes->name => 'convertCssClassesToAttributes',
            Rule::ConvertEllipseToCircle->name => 'convertEllipseToCircle',
            Rule::ConvertInlineStylesToAttributes->name => 'convertInlineStylesToAttributes',
            Rule::ConvertOneStopGradients->name => 'convertOneStopGradients',
            Rule::ConvertShapesToPath->name => 'convertShapesToPath',
            Rule::FixAttributeNames->name => 'fixAttributeNames',
            Rule::FlattenGroups->name => 'flattenGroups',
            Rule::MergeDuplicateGradients->name => 'mergeDuplicateGradients',
            Rule::MergeStyles->name => 'mergeStyles',
            Rule::MigrateToSvg2->name => 'migrateToSvg2',
            Rule::MinifyNumericValues->name => 'minifyNumericValues',
            Rule::MinifyPathData->name => 'minifyPathData',
            Rule::MinifyStyles->name => 'minifyStyles',
            Rule::MinifyTransformations->name => 'minifyTransformations',
            Rule::MoveCommonAttributesToGroup->name => 'moveCommonAttributesToGroup',
            Rule::MoveGroupAttributesToElements->name => 'moveGroupAttributesToElements',
            Rule::RemoveAriaAndRole->name => 'removeAriaAndRole',
            Rule::RemoveComments->name => 'removeComments',
            Rule::RemoveDataAttributes->name => 'removeDataAttributes',
            Rule::RemoveDefaultAttributes->name => 'removeDefaultAttributes',
            Rule::RemoveDescriptions->name => 'removeDescriptions',
            Rule::RemoveDuplicateElements->name => 'removeDuplicateElements',
            Rule::RemoveEmptyAttributes->name => 'removeEmptyAttributes',
            Rule::RemoveEmptyElements->name => 'removeEmptyElements',
            Rule::RemoveForeignContent->name => 'removeForeignContent',
            Rule::RemoveHiddenElements->name => 'removeHiddenElements',
            Rule::RemoveIneffectiveAttributes->name => 'removeIneffectiveAttributes',
            Rule::RemoveInkscapeFootprints->name => 'removeInkscapeFootprints',
            Rule::RemoveInvisibleCharacters->name => 'removeInvisibleCharacters',
            Rule::RemoveMetadata->name => 'removeMetadata',
            Rule::RemoveNonStandardAttributes->name => 'removeNonStandardAttributes',
            Rule::RemoveNonStandardTags->name => 'removeNonStandardTags',
            Rule::RemoveScripts->name => 'removeScripts',
            Rule::RemoveTitles->name => 'removeTitles',
            Rule::RemoveUnnecessaryWhitespace->name => 'removeUnnecessaryWhitespace',
            Rule::RemoveUnsafeMarkup->name => 'removeUnsafeMarkup',
            Rule::RemoveUnsafeUrls->name => 'removeUnsafeUrls',
            Rule::RemoveUnusedDefinitions->name => 'removeUnusedDefinitions',
            Rule::RemoveUnusedIds->name => 'removeUnusedIds',
            Rule::RemoveUnusedNamespaces->name => 'removeUnusedNamespaces',
            Rule::RemoveUseBombs->name => 'removeUseBombs',
            Rule::RemoveUselessDefs->name => 'removeUselessDefs',
            Rule::RemoveWidthHeightAttributes->name => 'removeWidthHeightAttributes',
            Rule::SanitizeStyleSheets->name => 'sanitizeStyleSheets',
            Rule::ScopeSvgStyles->name => 'scopeSvgStyles',
            Rule::SortAttributes->name => 'sortAttributes',
            Rule::SortDefsChildren->name => 'sortDefsChildren',
        ];

        self::assertSameSize($expected, Rule::cases());

        foreach (Rule::cases() as $case) {
            self::assertSame($expected[$case->name], $case->configKey());
        }
    }

    #[Test]
    public function exactlyTheRiskyRulesAreRisky(): void
    {
        foreach (Rule::cases() as $rule) {
            self::assertSame(\in_array($rule, self::RISKY_RULES, true), $rule->isRisky(), $rule->name);
        }
    }

    #[Test]
    public function theValueOfARuleIsItsFirstPass(): void
    {
        foreach (Rule::cases() as $rule) {
            self::assertSame($rule->passes()[0], $rule->value, $rule->name);
        }
    }

    #[Test]
    public function everyPassBelongsToExactlyOneRule(): void
    {
        $passes = array_merge(...array_map(static fn (Rule $rule): array => $rule->passes(), Rule::cases()));
        sort($passes);
        $expected = ExecutionOrder::passes();
        sort($expected);

        self::assertSame($expected, $passes);
    }

    /**
     * @param list<class-string> $passes the passes the rule runs
     */
    #[DataProvider('providePassesCases')]
    #[Test]
    public function passes(Rule $rule, array $passes): void
    {
        self::assertSame($passes, $rule->passes());
    }

    /**
     * @return iterable<string, array{Rule, list<class-string>}>
     */
    public static function providePassesCases(): iterable
    {
        yield 'migrateToSvg2' => [Rule::MigrateToSvg2, [MigrateToSvg2::class, RemoveDeprecatedAttributes::class, RemoveEnableBackgroundAttribute::class]];

        yield 'minifyNumericValues' => [Rule::MinifyNumericValues, [MinifyNumericValues::class, MinifyListValues::class]];

        yield 'removeDefaultAttributes' => [Rule::RemoveDefaultAttributes, [RemoveDefaultAttributes::class, RemoveStyleTypeAttributes::class, RemoveXmlSpaceDefault::class]];

        yield 'removeEmptyElements' => [Rule::RemoveEmptyElements, [RemoveEmptyContainers::class, RemoveEmptyTextElements::class]];

        yield 'removeHiddenElements' => [Rule::RemoveHiddenElements, [RemoveHiddenElements::class, RemoveZeroSizeShapes::class]];

        yield 'removeIneffectiveAttributes' => [Rule::RemoveIneffectiveAttributes, [RemoveUselessStrokeAndFill::class, RemoveNonInheritableGroupAttributes::class, RemoveUnusedTextAttributes::class]];

        yield 'a rule with one pass' => [Rule::ConvertColorsToHex, [ConvertColorsToHex::class]];
    }

    #[Test]
    public function configKeyMatchesNamingConvention(): void
    {
        foreach (Rule::cases() as $case) {
            self::assertMatchesRegularExpression('/^[a-z]+[A-Za-z0-9]*$/', $case->configKey(), $case->name);
        }
    }
}
