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
            Rule::ConvertEmptyTagsToSelfClosing->name => 'convertEmptyTagsToSelfClosing',
            Rule::ConvertInlineStylesToAttributes->name => 'convertInlineStylesToAttributes',
            Rule::FlattenGroups->name => 'flattenGroups',
            Rule::MinifySvgCoordinates->name => 'minifySvgCoordinates',
            Rule::MinifyTransformations->name => 'minifyTransformations',
            Rule::RemoveAriaAndRole->name => 'removeAriaAndRole',
            Rule::RemoveComments->name => 'removeComments',
            Rule::RemoveDefaultAttributes->name => 'removeDefaultAttributes',
            Rule::RemoveDeprecatedAttributes->name => 'removeDeprecatedAttributes',
            Rule::RemoveDoctype->name => 'removeDoctype',
            Rule::RemoveDuplicateElements->name => 'removeDuplicateElements',
            Rule::RemoveEnableBackgroundAttribute->name => 'removeEnableBackgroundAttribute',
            Rule::RemoveEmptyGroups->name => 'removeEmptyGroups',
            Rule::RemoveEmptyTextElements->name => 'removeEmptyTextElements',
            Rule::RemoveEmptyAttributes->name => 'removeEmptyAttributes',
            Rule::RemoveInkscapeFootprints->name => 'removeInkscapeFootprints',
            Rule::RemoveInvisibleCharacters->name => 'removeInvisibleCharacters',
            Rule::RemoveMetadata->name => 'removeMetadata',
            Rule::RemoveTitleAndDesc->name => 'removeTitleAndDesc',
            Rule::RemoveUnnecessaryWhitespace->name => 'removeUnnecessaryWhitespace',
            Rule::RemoveUnsafeElements->name => 'removeUnsafeElements',
            Rule::RemoveUnusedMasks->name => 'removeUnusedMasks',
            Rule::RemoveUnusedNamespaces->name => 'removeUnusedNamespaces',
            Rule::RemoveWidthHeightAttributes->name => 'removeWidthHeightAttributes',
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
            static fn (Rule $rule) => $rule->value,
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
}
