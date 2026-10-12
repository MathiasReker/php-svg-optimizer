<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Type;

use MathiasReker\PhpSvgOptimizer\Type\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Rule::class)]
final class RuleTest extends TestCase
{
    #[Test]
    public function enumValuesMatchRuleClasses(): void
    {
        foreach (Rule::cases() as $case) {
            self::assertTrue(
                class_exists($case->value),
                \sprintf('Class %s does not exist for enum case %s', $case->value, $case->name)
            );
        }
    }

    #[Test]
    public function configKeysAreCorrect(): void
    {
        $expected = [
            Rule::ConvertColorsToHex->name => 'convertColorsToHex',
            Rule::ConvertCssClassesToAttributes->name => 'convertCssClassesToAttributes',
            Rule::ConvertEllipseToCircle->name => 'convertEllipseToCircle',
            Rule::ConvertEmptyTagsToSelfClosing->name => 'convertEmptyTagsToSelfClosing',
            Rule::ConvertInlineStylesToAttributes->name => 'convertInlineStylesToAttributes',
            Rule::ConvertOneStopGradients->name => 'convertOneStopGradients',
            Rule::ConvertShapesToPath->name => 'convertShapesToPath',
            Rule::FixAttributeNames->name => 'fixAttributeNames',
            Rule::FlattenGroups->name => 'flattenGroups',
            Rule::MinifyListValues->name => 'minifyListValues',
            Rule::MinifyNumericValues->name => 'minifyNumericValues',
            Rule::MinifyPathData->name => 'minifyPathData',
            Rule::MinifySvgCoordinates->name => 'minifySvgCoordinates',
            Rule::MinifyTransformations->name => 'minifyTransformations',
            Rule::RemoveAriaAndRole->name => 'removeAriaAndRole',
            Rule::RemoveComments->name => 'removeComments',
            Rule::RemoveDataAttributes->name => 'removeDataAttributes',
            Rule::RemoveDefaultAttributes->name => 'removeDefaultAttributes',
            Rule::RemoveDeprecatedAttributes->name => 'removeDeprecatedAttributes',
            Rule::RemoveDoctype->name => 'removeDoctype',
            Rule::RemoveDuplicateElements->name => 'removeDuplicateElements',
            Rule::RemoveEnableBackgroundAttribute->name => 'removeEnableBackgroundAttribute',
            Rule::RemoveEmptyGroups->name => 'removeEmptyGroups',
            Rule::RemoveEmptyTextElements->name => 'removeEmptyTextElements',
            Rule::RemoveEmptyAttributes->name => 'removeEmptyAttributes',
            Rule::RemoveEmptyContainers->name => 'removeEmptyContainers',
            Rule::RemoveInkscapeFootprints->name => 'removeInkscapeFootprints',
            Rule::RemoveInvisibleCharacters->name => 'removeInvisibleCharacters',
            Rule::RemoveMetadata->name => 'removeMetadata',
            Rule::RemoveNonStandardAttributes->name => 'removeNonStandardAttributes',
            Rule::RemoveNonStandardTags->name => 'removeNonStandardTags',
            Rule::RemoveStyleTypeAttributes->name => 'removeStyleTypeAttributes',
            Rule::RemoveTitleAndDesc->name => 'removeTitleAndDesc',
            Rule::RemoveUnnecessaryWhitespace->name => 'removeUnnecessaryWhitespace',
            Rule::RemoveUnsafeElements->name => 'removeUnsafeElements',
            Rule::RemoveUnusedIds->name => 'removeUnusedIds',
            Rule::RemoveUnusedMasks->name => 'removeUnusedMasks',
            Rule::RemoveUnusedNamespaces->name => 'removeUnusedNamespaces',
            Rule::RemoveUselessDefs->name => 'removeUselessDefs',
            Rule::RemoveWidthHeightAttributes->name => 'removeWidthHeightAttributes',
            Rule::RemoveXmlSpaceDefault->name => 'removeXmlSpaceDefault',
            Rule::RemoveZeroSizeShapes->name => 'removeZeroSizeShapes',
            Rule::ScopeSvgStyles->name => 'scopeSvgStyles',
            Rule::SortAttributes->name => 'sortAttributes',
        ];

        foreach (Rule::cases() as $case) {
            self::assertSame(
                $expected[$case->name],
                $case->configKey(),
                \sprintf('Config key mismatch for %s', $case->name)
            );
        }
    }

    #[Test]
    public function allConfigKeysAreUnique(): void
    {
        $keys = array_map(
            static fn (Rule $rule): string => $rule->configKey(),
            Rule::cases()
        );

        $duplicateKeys = array_diff_key($keys, array_unique($keys));

        self::assertSame(
            [],
            $duplicateKeys,
            'Duplicate config keys detected: ' . implode(', ', $duplicateKeys)
        );
    }

    #[Test]
    public function allEnumValuesAreUnique(): void
    {
        $values = array_map(
            static fn (Rule $rule): string => $rule->value,
            Rule::cases()
        );

        self::assertSame(
            $values,
            array_unique($values),
            'Duplicate enum values detected (duplicate rule classes).'
        );
    }

    #[Test]
    public function configKeyMatchesNamingConvention(): void
    {
        foreach (Rule::cases() as $case) {
            $key = $case->configKey();

            self::assertMatchesRegularExpression(
                '/^[a-z]+[A-Za-z0-9]*$/',
                $key,
                \sprintf('Config key "%s" for %s does not follow camelCase.', $key, $case->name)
            );
        }
    }

    #[Test]
    public function ruleClassIsInstantiable(): void
    {
        foreach (Rule::cases() as $case) {
            $class = $case->value;

            self::assertTrue(
                class_exists($class),
                \sprintf('Rule class "%s" does not exist', $class)
            );

            $reflection = new \ReflectionClass($class);

            self::assertFalse(
                $reflection->isAbstract(),
                \sprintf('Rule class "%s" must not be abstract', $class)
            );
        }
    }

    #[Test]
    public function prioritiesAreUnique(): void
    {
        $priorities = array_map(static fn (Rule $rule): int => $rule->priority(), Rule::cases());

        self::assertSame($priorities, array_values(array_unique($priorities)));
    }

    #[Test]
    public function rulesRunInTheDocumentedOrder(): void
    {
        $rules = Rule::cases();
        usort($rules, static fn (Rule $a, Rule $b): int => $a->priority() <=> $b->priority());

        self::assertSame(
            [
                'RemoveUnsafeElements', 'RemoveDoctype', 'RemoveComments', 'RemoveMetadata', 'RemoveInkscapeFootprints',
                'RemoveNonStandardTags', 'FixAttributeNames', 'RemoveDeprecatedAttributes', 'RemoveTitleAndDesc',
                'RemoveNonStandardAttributes', 'RemoveDataAttributes', 'RemoveAriaAndRole', 'RemoveInvisibleCharacters',
                'RemoveEnableBackgroundAttribute', 'RemoveStyleTypeAttributes', 'RemoveXmlSpaceDefault',
                'RemoveWidthHeightAttributes',
                'ScopeSvgStyles', 'ConvertCssClassesToAttributes', 'ConvertInlineStylesToAttributes',
                'RemoveEmptyAttributes', 'RemoveUnusedMasks',
                'MinifyNumericValues', 'MinifyListValues', 'MinifySvgCoordinates', 'ConvertColorsToHex', 'RemoveUnusedIds',
                'ConvertOneStopGradients', 'RemoveDefaultAttributes',
                'RemoveZeroSizeShapes', 'RemoveEmptyTextElements', 'RemoveDuplicateElements', 'FlattenGroups',
                'RemoveEmptyGroups', 'RemoveEmptyContainers', 'RemoveUselessDefs', 'MinifyTransformations',
                'ConvertEllipseToCircle', 'ConvertShapesToPath', 'MinifyPathData',
                'RemoveUnusedNamespaces', 'RemoveUnnecessaryWhitespace', 'ConvertEmptyTagsToSelfClosing', 'SortAttributes',
            ],
            array_map(static fn (Rule $rule): string => $rule->name, $rules),
        );
    }

    #[Test]
    public function sanitizingRunsFirstAndSortingLast(): void
    {
        $priorities = array_map(static fn (Rule $rule): int => $rule->priority(), Rule::cases());

        self::assertSame(min($priorities), Rule::RemoveUnsafeElements->priority());
        self::assertSame(max($priorities), Rule::SortAttributes->priority());
    }

    /**
     * Each pair is a rule that leaves work for, or unblocks, the rule that must run after it.
     */
    #[DataProvider('provideRuleRunsBeforeCases')]
    #[Test]
    public function ruleRunsBefore(Rule $first, Rule $second): void
    {
        self::assertLessThan($second->priority(), $first->priority());
    }

    /**
     * @return iterable<string, array{Rule, Rule}>
     */
    public static function provideRuleRunsBeforeCases(): iterable
    {
        yield 'attribute names are fixed before unknown attributes are removed' => [Rule::FixAttributeNames, Rule::RemoveNonStandardAttributes];

        yield 'xlink:title becomes a title before titles are removed' => [Rule::RemoveDeprecatedAttributes, Rule::RemoveTitleAndDesc];

        yield 'styles are scoped before class rules are converted' => [Rule::ScopeSvgStyles, Rule::ConvertCssClassesToAttributes];

        yield 'class styles become attributes before colors are minified' => [Rule::ConvertCssClassesToAttributes, Rule::ConvertColorsToHex];

        yield 'inline styles become attributes before defaults are removed' => [Rule::ConvertInlineStylesToAttributes, Rule::RemoveDefaultAttributes];

        yield 'numbers are minified before defaults are compared' => [Rule::MinifyNumericValues, Rule::RemoveDefaultAttributes];

        yield 'stop colors are minified before gradients are inlined' => [Rule::ConvertColorsToHex, Rule::ConvertOneStopGradients];

        yield 'unused stop ids are removed before gradients are inlined' => [Rule::RemoveUnusedIds, Rule::ConvertOneStopGradients];

        yield 'empty attributes are removed before gradients are inlined' => [Rule::RemoveEmptyAttributes, Rule::ConvertOneStopGradients];

        yield 'defaults are removed before groups are flattened' => [Rule::RemoveDefaultAttributes, Rule::FlattenGroups];

        yield 'foreign attributes are removed before groups are flattened' => [Rule::RemoveNonStandardAttributes, Rule::FlattenGroups];

        yield 'groups are flattened before transforms are minified' => [Rule::FlattenGroups, Rule::MinifyTransformations];

        yield 'zero-size shapes are removed before shapes become paths' => [Rule::RemoveZeroSizeShapes, Rule::ConvertShapesToPath];

        yield 'list values are minified before shapes become paths' => [Rule::MinifyListValues, Rule::ConvertShapesToPath];

        yield 'shapes become paths before path data is minified' => [Rule::ConvertShapesToPath, Rule::MinifyPathData];

        yield 'gradients are inlined before useless definitions are removed' => [Rule::ConvertOneStopGradients, Rule::RemoveUselessDefs];

        yield 'elements are removed before unused namespaces are removed' => [Rule::RemoveNonStandardTags, Rule::RemoveUnusedNamespaces];
    }
}
