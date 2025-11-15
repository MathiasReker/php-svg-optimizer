<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Type;

use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEmptyTagsToSelfClosing;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDoctype;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedMasks;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;

/**
 * Represents all available optimization rules for the SVG optimizer.
 *
 * Each enum case corresponds to a specific optimization step
 * that can be toggled on or off.
 *
 * @no-named-arguments
 */
enum Rule: string
{
    case CONVERT_COLORS_TO_HEX = ConvertColorsToHex::class;
    case CONVERT_CSS_CLASSES_TO_ATTRIBUTES = ConvertCssClassesToAttributes::class;
    case CONVERT_EMPTY_TAGS_TO_SELF_CLOSING = ConvertEmptyTagsToSelfClosing::class;
    case CONVERT_INLINE_STYLES_TO_ATTRIBUTES = ConvertInlineStylesToAttributes::class;
    case FLATTEN_GROUPS = FlattenGroups::class;
    case MINIFY_SVG_COORDINATES = MinifySvgCoordinates::class;
    case MINIFY_TRANSFORMATIONS = MinifyTransformations::class;
    case REMOVE_COMMENTS = RemoveComments::class;
    case REMOVE_DEFAULT_ATTRIBUTES = RemoveDefaultAttributes::class;
    case REMOVE_DEPRECATED_ATTRIBUTES = RemoveDeprecatedAttributes::class;
    case REMOVE_DOCTYPE = RemoveDoctype::class;
    case REMOVE_DUPLICATE_ELEMENTS = RemoveDuplicateElements::class;
    case REMOVE_ENABLE_BACKGROUND_ATTRIBUTE = RemoveEnableBackgroundAttribute::class;
    case REMOVE_EMPTY_GROUPS = RemoveEmptyGroups::class;
    case REMOVE_EMPTY_TEXT_ELEMENTS = RemoveEmptyTextElements::class;
    case REMOVE_EMPTY_ATTRIBUTES = RemoveEmptyAttributes::class;
    case REMOVE_INKSCAPE_FOOTPRINTS = RemoveInkscapeFootprints::class;
    case REMOVE_INVISIBLE_CHARACTERS = RemoveInvisibleCharacters::class;
    case REMOVE_METADATA = RemoveMetadata::class;
    case REMOVE_TITLE_AND_DESC = RemoveTitleAndDesc::class;
    case REMOVE_UNNECESSARY_WHITESPACE = RemoveUnnecessaryWhitespace::class;
    case REMOVE_UNSAFE_ELEMENTS = RemoveUnsafeElements::class;
    case REMOVE_UNUSED_MASKS = RemoveUnusedMasks::class;
    case REMOVE_UNUSED_NAMESPACES = RemoveUnusedNamespaces::class;
    case REMOVE_WIDTH_HEIGHT_ATTRIBUTES = RemoveWidthHeightAttributes::class;
    case SORT_ATTRIBUTES = SortAttributes::class;

    public function configKey(): string
    {
        return match ($this->value) {
            self::CONVERT_COLORS_TO_HEX->value => 'convertColorsToHex',
            self::CONVERT_CSS_CLASSES_TO_ATTRIBUTES->value => 'convertCssClassesToAttributes',
            self::CONVERT_EMPTY_TAGS_TO_SELF_CLOSING->value => 'convertEmptyTagsToSelfClosing',
            self::CONVERT_INLINE_STYLES_TO_ATTRIBUTES->value => 'convertInlineStylesToAttributes',
            self::FLATTEN_GROUPS->value => 'flattenGroups',
            self::MINIFY_SVG_COORDINATES->value => 'minifySvgCoordinates',
            self::MINIFY_TRANSFORMATIONS->value => 'minifyTransformations',
            self::REMOVE_COMMENTS->value => 'removeComments',
            self::REMOVE_DEFAULT_ATTRIBUTES->value => 'removeDefaultAttributes',
            self::REMOVE_DEPRECATED_ATTRIBUTES->value => 'removeDeprecatedAttributes',
            self::REMOVE_DOCTYPE->value => 'removeDoctype',
            self::REMOVE_DUPLICATE_ELEMENTS->value => 'removeDuplicateElements',
            self::REMOVE_ENABLE_BACKGROUND_ATTRIBUTE->value => 'removeEnableBackgroundAttribute',
            self::REMOVE_EMPTY_GROUPS->value => 'removeEmptyGroups',
            self::REMOVE_EMPTY_TEXT_ELEMENTS->value => 'removeEmptyTextElements',
            self::REMOVE_EMPTY_ATTRIBUTES->value => 'removeEmptyAttributes',
            self::REMOVE_INKSCAPE_FOOTPRINTS->value => 'removeInkscapeFootprints',
            self::REMOVE_INVISIBLE_CHARACTERS->value => 'removeInvisibleCharacters',
            self::REMOVE_METADATA->value => 'removeMetadata',
            self::REMOVE_TITLE_AND_DESC->value => 'removeTitleAndDesc',
            self::REMOVE_UNNECESSARY_WHITESPACE->value => 'removeUnnecessaryWhitespace',
            self::REMOVE_UNSAFE_ELEMENTS->value => 'removeUnsafeElements',
            self::REMOVE_UNUSED_MASKS->value => 'removeUnusedMasks',
            self::REMOVE_UNUSED_NAMESPACES->value => 'removeUnusedNamespaces',
            self::REMOVE_WIDTH_HEIGHT_ATTRIBUTES->value => 'removeWidthHeightAttributes',
            self::SORT_ATTRIBUTES->value => 'sortAttributes',
        };
    }
}
