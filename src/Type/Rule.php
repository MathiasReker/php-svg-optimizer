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
use MathiasReker\PhpSvgOptimizer\Service\Rule\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveAriaAndRole;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDataAttributes;
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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardTags;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedMasks;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ScopeSvgStyles;
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
    case ConvertColorsToHex = ConvertColorsToHex::class;

    case ConvertCssClassesToAttributes = ConvertCssClassesToAttributes::class;

    case ConvertEmptyTagsToSelfClosing = ConvertEmptyTagsToSelfClosing::class;

    case ConvertInlineStylesToAttributes = ConvertInlineStylesToAttributes::class;

    case FixAttributeNames = FixAttributeNames::class;

    case FlattenGroups = FlattenGroups::class;

    case MinifySvgCoordinates = MinifySvgCoordinates::class;

    case MinifyTransformations = MinifyTransformations::class;

    case RemoveAriaAndRole = RemoveAriaAndRole::class;

    case RemoveComments = RemoveComments::class;

    case RemoveDataAttributes = RemoveDataAttributes::class;

    case RemoveDefaultAttributes = RemoveDefaultAttributes::class;

    case RemoveDeprecatedAttributes = RemoveDeprecatedAttributes::class;

    case RemoveDoctype = RemoveDoctype::class;

    case RemoveDuplicateElements = RemoveDuplicateElements::class;

    case RemoveEnableBackgroundAttribute = RemoveEnableBackgroundAttribute::class;

    case RemoveEmptyGroups = RemoveEmptyGroups::class;

    case RemoveEmptyTextElements = RemoveEmptyTextElements::class;

    case RemoveEmptyAttributes = RemoveEmptyAttributes::class;

    case RemoveInkscapeFootprints = RemoveInkscapeFootprints::class;

    case RemoveInvisibleCharacters = RemoveInvisibleCharacters::class;

    case RemoveMetadata = RemoveMetadata::class;

    case RemoveNonStandardAttributes = RemoveNonStandardAttributes::class;

    case RemoveNonStandardTags = RemoveNonStandardTags::class;

    case RemoveTitleAndDesc = RemoveTitleAndDesc::class;

    case RemoveUnnecessaryWhitespace = RemoveUnnecessaryWhitespace::class;

    case RemoveUnsafeElements = RemoveUnsafeElements::class;

    case RemoveUnusedMasks = RemoveUnusedMasks::class;

    case RemoveUnusedNamespaces = RemoveUnusedNamespaces::class;

    case RemoveWidthHeightAttributes = RemoveWidthHeightAttributes::class;

    case ScopeSvgStyles = ScopeSvgStyles::class;

    case SortAttributes = SortAttributes::class;

    public function configKey(): string
    {
        return match ($this->value) {
            self::ConvertColorsToHex->value => 'convertColorsToHex',
            self::ConvertCssClassesToAttributes->value => 'convertCssClassesToAttributes',
            self::ConvertEmptyTagsToSelfClosing->value => 'convertEmptyTagsToSelfClosing',
            self::ConvertInlineStylesToAttributes->value => 'convertInlineStylesToAttributes',
            self::FixAttributeNames->value => 'fixAttributeNames',
            self::FlattenGroups->value => 'flattenGroups',
            self::MinifySvgCoordinates->value => 'minifySvgCoordinates',
            self::MinifyTransformations->value => 'minifyTransformations',
            self::RemoveAriaAndRole->value => 'removeAriaAndRole',
            self::RemoveComments->value => 'removeComments',
            self::RemoveDataAttributes->value => 'removeDataAttributes',
            self::RemoveDefaultAttributes->value => 'removeDefaultAttributes',
            self::RemoveDeprecatedAttributes->value => 'removeDeprecatedAttributes',
            self::RemoveDoctype->value => 'removeDoctype',
            self::RemoveDuplicateElements->value => 'removeDuplicateElements',
            self::RemoveEnableBackgroundAttribute->value => 'removeEnableBackgroundAttribute',
            self::RemoveEmptyGroups->value => 'removeEmptyGroups',
            self::RemoveEmptyTextElements->value => 'removeEmptyTextElements',
            self::RemoveEmptyAttributes->value => 'removeEmptyAttributes',
            self::RemoveInkscapeFootprints->value => 'removeInkscapeFootprints',
            self::RemoveInvisibleCharacters->value => 'removeInvisibleCharacters',
            self::RemoveMetadata->value => 'removeMetadata',
            self::RemoveNonStandardAttributes->value => 'removeNonStandardAttributes',
            self::RemoveNonStandardTags->value => 'removeNonStandardTags',
            self::RemoveTitleAndDesc->value => 'removeTitleAndDesc',
            self::RemoveUnnecessaryWhitespace->value => 'removeUnnecessaryWhitespace',
            self::RemoveUnsafeElements->value => 'removeUnsafeElements',
            self::RemoveUnusedMasks->value => 'removeUnusedMasks',
            self::RemoveUnusedNamespaces->value => 'removeUnusedNamespaces',
            self::RemoveWidthHeightAttributes->value => 'removeWidthHeightAttributes',
            self::ScopeSvgStyles->value => 'scopeSvgStyles',
            self::SortAttributes->value => 'sortAttributes',
        };
    }
}
