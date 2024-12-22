<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Enums;

use MathiasReker\PhpSvgOptimizer\Enums\Rule;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class RuleTest extends TestCase
{
    /**
     * @return iterable<array{Rule, bool}>
     */
    public static function provideDefaultValueCases(): iterable
    {
        yield [Rule::CONVERT_COLORS_TO_HEX, true];
        yield [Rule::FLATTEN_GROUPS, true];
        yield [Rule::MINIFY_SVG_COORDINATES, true];
        yield [Rule::MINIFY_TRANSFORMATIONS, true];
        yield [Rule::REMOVE_COMMENTS, true];
        yield [Rule::REMOVE_DEFAULT_ATTRIBUTES, true];
        yield [Rule::REMOVE_DEPRECATED_ATTRIBUTES, true];
        yield [Rule::REMOVE_DOCTYPE, true];
        yield [Rule::REMOVE_EMPTY_ATTRIBUTES, true];
        yield [Rule::REMOVE_METADATA, true];
        yield [Rule::REMOVE_TITLE_AND_DESC, true];
        yield [Rule::REMOVE_UNNECESSARY_WHITESPACE, true];
        yield [Rule::SORT_ATTRIBUTES, true];
    }

    /**
     * @return iterable<array{Rule, string}>
     */
    public static function provideEnumValuesCases(): iterable
    {
        yield [Rule::CONVERT_COLORS_TO_HEX, 'convertColorsToHex'];
        yield [Rule::FLATTEN_GROUPS, 'flattenGroups'];
        yield [Rule::MINIFY_SVG_COORDINATES, 'minifySvgCoordinates'];
        yield [Rule::MINIFY_TRANSFORMATIONS, 'minifyTransformations'];
        yield [Rule::REMOVE_COMMENTS, 'removeComments'];
        yield [Rule::REMOVE_DEFAULT_ATTRIBUTES, 'removeDefaultAttributes'];
        yield [Rule::REMOVE_DEPRECATED_ATTRIBUTES, 'removeDeprecatedAttributes'];
        yield [Rule::REMOVE_DOCTYPE, 'removeDoctype'];
        yield [Rule::REMOVE_EMPTY_ATTRIBUTES, 'removeEmptyAttributes'];
        yield [Rule::REMOVE_METADATA, 'removeMetadata'];
        yield [Rule::REMOVE_TITLE_AND_DESC, 'removeTitleAndDesc'];
        yield [Rule::REMOVE_UNNECESSARY_WHITESPACE, 'removeUnnecessaryWhitespace'];
        yield [Rule::SORT_ATTRIBUTES, 'sortAttributes'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDefaultValueCases')]
    public function testDefaultValue(Rule $rule, bool $expected): void
    {
        self::assertSame($expected, $rule->defaultValue());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideEnumValuesCases')]
    public function testEnumValues(Rule $rule, string $expectedValue): void
    {
        self::assertSame($expectedValue, $rule->value);
    }
}
