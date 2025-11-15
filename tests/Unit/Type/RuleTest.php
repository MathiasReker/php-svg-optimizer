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
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Rule::class)]
final class RuleTest extends TestCase
{
    public function testEnumValuesMatchRuleClasses(): void
    {
        foreach (Rule::cases() as $case) {
            // Each enum case should map to a fully-qualified class string
            self::assertTrue(
                class_exists($case->value),
                \sprintf('Class %s does not exist for enum case %s', $case->value, $case->name)
            );
        }
    }

    public function testConfigKeysAreCorrect(): void
    {
        $expected = [
            Rule::CONVERT_COLORS_TO_HEX->name => 'convertColorsToHex',
            Rule::CONVERT_CSS_CLASSES_TO_ATTRIBUTES->name => 'convertCssClassesToAttributes',
            Rule::CONVERT_EMPTY_TAGS_TO_SELF_CLOSING->name => 'convertEmptyTagsToSelfClosing',
            Rule::CONVERT_INLINE_STYLES_TO_ATTRIBUTES->name => 'convertInlineStylesToAttributes',
            Rule::FLATTEN_GROUPS->name => 'flattenGroups',
            Rule::MINIFY_SVG_COORDINATES->name => 'minifySvgCoordinates',
            Rule::MINIFY_TRANSFORMATIONS->name => 'minifyTransformations',
            Rule::REMOVE_COMMENTS->name => 'removeComments',
            Rule::REMOVE_DEFAULT_ATTRIBUTES->name => 'removeDefaultAttributes',
            Rule::REMOVE_DEPRECATED_ATTRIBUTES->name => 'removeDeprecatedAttributes',
            Rule::REMOVE_DOCTYPE->name => 'removeDoctype',
            Rule::REMOVE_DUPLICATE_ELEMENTS->name => 'removeDuplicateElements',
            Rule::REMOVE_ENABLE_BACKGROUND_ATTRIBUTE->name => 'removeEnableBackgroundAttribute',
            Rule::REMOVE_EMPTY_GROUPS->name => 'removeEmptyGroups',
            Rule::REMOVE_EMPTY_TEXT_ELEMENTS->name => 'removeEmptyTextElements',
            Rule::REMOVE_EMPTY_ATTRIBUTES->name => 'removeEmptyAttributes',
            Rule::REMOVE_INKSCAPE_FOOTPRINTS->name => 'removeInkscapeFootprints',
            Rule::REMOVE_INVISIBLE_CHARACTERS->name => 'removeInvisibleCharacters',
            Rule::REMOVE_METADATA->name => 'removeMetadata',
            Rule::REMOVE_TITLE_AND_DESC->name => 'removeTitleAndDesc',
            Rule::REMOVE_UNNECESSARY_WHITESPACE->name => 'removeUnnecessaryWhitespace',
            Rule::REMOVE_UNSAFE_ELEMENTS->name => 'removeUnsafeElements',
            Rule::REMOVE_UNUSED_MASKS->name => 'removeUnusedMasks',
            Rule::REMOVE_UNUSED_NAMESPACES->name => 'removeUnusedNamespaces',
            Rule::REMOVE_WIDTH_HEIGHT_ATTRIBUTES->name => 'removeWidthHeightAttributes',
            Rule::SORT_ATTRIBUTES->name => 'sortAttributes',
        ];

        foreach (Rule::cases() as $case) {
            self::assertSame(
                $expected[$case->name],
                $case->configKey(),
                \sprintf('Config key mismatch for %s', $case->name)
            );
        }
    }

    public function testEnumCaseCountIsCorrect(): void
    {
        self::assertCount(
            26,
            Rule::cases(),
            'The number of Rule enum cases changed. Update the test expectations.'
        );
    }
}
