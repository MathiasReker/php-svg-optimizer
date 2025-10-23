<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Type;

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
    case CONVERT_COLORS_TO_HEX = 'convertColorsToHex';
    case CONVERT_CSS_CLASSES_TO_ATTRIBUTES = 'convertCssClassesToAttributes';
    case CONVERT_EMPTY_TAGS_TO_SELF_CLOSING = 'convertEmptyTagsToSelfClosing';
    case CONVERT_INLINE_STYLES_TO_ATTRIBUTES = 'convertInlineStylesToAttributes';
    case FLATTEN_GROUPS = 'flattenGroups';
    case MINIFY_SVG_COORDINATES = 'minifySvgCoordinates';
    case MINIFY_TRANSFORMATIONS = 'minifyTransformations';
    case REMOVE_COMMENTS = 'removeComments';
    case REMOVE_DEFAULT_ATTRIBUTES = 'removeDefaultAttributes';
    case REMOVE_DEPRECATED_ATTRIBUTES = 'removeDeprecatedAttributes';
    case REMOVE_DOCTYPE = 'removeDoctype';
    case REMOVE_ENABLE_BACKGROUND_ATTRIBUTE = 'removeEnableBackgroundAttribute';
    case REMOVE_EMPTY_ATTRIBUTES = 'removeEmptyAttributes';
    case REMOVE_INKSCAPE_FOOTPRINTS = 'removeInkscapeFootprints';
    case REMOVE_INVISIBLE_CHARACTERS = 'removeInvisibleCharacters';
    case REMOVE_METADATA = 'removeMetadata';
    case REMOVE_TITLE_AND_DESC = 'removeTitleAndDesc';
    case REMOVE_UNNECESSARY_WHITESPACE = 'removeUnnecessaryWhitespace';
    case REMOVE_UNSAFE_ELEMENTS = 'removeUnsafeElements';
    case REMOVE_UNUSED_MASKS = 'removeUnusedMasks';
    case REMOVE_UNUSED_NAMESPACES = 'removeUnusedNamespaces';
    case REMOVE_WIDTH_HEIGHT_ATTRIBUTES = 'removeWidthHeightAttributes';
    case SORT_ATTRIBUTES = 'sortAttributes';

    /**
     * Returns the default activation state for each optimization rule.
     *
     * @return bool true if the rule is enabled by default, false otherwise
     */
    public function defaultValue(): bool
    {
        return match ($this->value) {
            self::CONVERT_COLORS_TO_HEX->value,
            self::CONVERT_CSS_CLASSES_TO_ATTRIBUTES->value,
            self::CONVERT_EMPTY_TAGS_TO_SELF_CLOSING->value,
            self::CONVERT_INLINE_STYLES_TO_ATTRIBUTES->value,
            self::MINIFY_SVG_COORDINATES->value,
            self::MINIFY_TRANSFORMATIONS->value,
            self::REMOVE_COMMENTS->value,
            self::REMOVE_DEFAULT_ATTRIBUTES->value,
            self::REMOVE_DEPRECATED_ATTRIBUTES->value,
            self::REMOVE_DOCTYPE->value,
            self::REMOVE_ENABLE_BACKGROUND_ATTRIBUTE->value,
            self::REMOVE_EMPTY_ATTRIBUTES->value,
            self::REMOVE_INKSCAPE_FOOTPRINTS->value,
            self::REMOVE_INVISIBLE_CHARACTERS->value,
            self::REMOVE_METADATA->value,
            self::REMOVE_TITLE_AND_DESC->value,
            self::REMOVE_UNNECESSARY_WHITESPACE->value,
            self::REMOVE_UNUSED_MASKS->value,
            self::REMOVE_UNUSED_NAMESPACES->value,
            self::SORT_ATTRIBUTES->value => true,

            self::FLATTEN_GROUPS->value,
            self::REMOVE_WIDTH_HEIGHT_ATTRIBUTES->value,
            self::REMOVE_UNSAFE_ELEMENTS->value => false,
        };
    }
}
