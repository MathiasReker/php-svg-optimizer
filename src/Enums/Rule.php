<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Enums;

enum Rule: string
{
    case CONVERT_COLORS_TO_HEX = 'convertColorsToHex';
    case FLATTEN_GROUPS = 'flattenGroups';
    case MINIFY_SVG_COORDINATES = 'minifySvgCoordinates';
    case MINIFY_TRANSFORMATIONS = 'minifyTransformations';
    case REMOVE_COMMENTS = 'removeComments';
    case REMOVE_DEFAULT_ATTRIBUTES = 'removeDefaultAttributes';
    case REMOVE_DEPRECATED_ATTRIBUTES = 'removeDeprecatedAttributes';
    case REMOVE_DOCTYPE = 'removeDoctype';
    case REMOVE_EMPTY_ATTRIBUTES = 'removeEmptyAttributes';
    case REMOVE_METADATA = 'removeMetadata';
    case REMOVE_TITLE_AND_DESC = 'removeTitleAndDesc';
    case REMOVE_UNNECESSARY_WHITESPACE = 'removeUnnecessaryWhitespace';
    case SORT_ATTRIBUTES = 'sortAttributes';

    /**
     * Get the default value for each rule.
     */
    public function defaultValue(): bool
    {
        return match ($this) {
            self::CONVERT_COLORS_TO_HEX,
            self::MINIFY_TRANSFORMATIONS,
            self::FLATTEN_GROUPS,
            self::MINIFY_SVG_COORDINATES,
            self::REMOVE_COMMENTS,
            self::REMOVE_DEFAULT_ATTRIBUTES,
            self::REMOVE_DEPRECATED_ATTRIBUTES,
            self::REMOVE_DOCTYPE,
            self::REMOVE_EMPTY_ATTRIBUTES,
            self::REMOVE_METADATA,
            self::REMOVE_TITLE_AND_DESC,
            self::SORT_ATTRIBUTES,
            self::REMOVE_UNNECESSARY_WHITESPACE => true,
        };
    }
}
