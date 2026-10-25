<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Type;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertOneStopGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertShapesToPath;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MergeDuplicateGradients;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MergeStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MigrateToSvg2;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveCommonAttributesToGroup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveGroupAttributesToElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveAriaAndRole;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDescriptions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveForeignContent;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonInheritableGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardTags;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveStyleTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeUrls;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedTextAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUseBombs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveXmlSpaceDefault;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SanitizeStyleSheets;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ScopeSvgStyles;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortDefsChildren;

/**
 * @api
 *
 * @no-named-arguments
 */
enum Rule: string
{
    case ConvertColorsToHex = ConvertColorsToHex::class;

    case ConvertCssClassesToAttributes = ConvertCssClassesToAttributes::class;

    case ConvertEllipseToCircle = ConvertEllipseToCircle::class;

    case ConvertInlineStylesToAttributes = ConvertInlineStylesToAttributes::class;

    case ConvertOneStopGradients = ConvertOneStopGradients::class;

    case ConvertShapesToPath = ConvertShapesToPath::class;

    case FixAttributeNames = FixAttributeNames::class;

    case FlattenGroups = FlattenGroups::class;

    case MergeDuplicateGradients = MergeDuplicateGradients::class;

    case MergeStyles = MergeStyles::class;

    case MigrateToSvg2 = MigrateToSvg2::class;

    case MinifyNumericValues = MinifyNumericValues::class;

    case MinifyPathData = MinifyPathData::class;

    case MinifyStyles = MinifyStyles::class;

    case MinifyTransformations = MinifyTransformations::class;

    case MoveCommonAttributesToGroup = MoveCommonAttributesToGroup::class;

    case MoveGroupAttributesToElements = MoveGroupAttributesToElements::class;

    case RemoveAriaAndRole = RemoveAriaAndRole::class;

    case RemoveComments = RemoveComments::class;

    case RemoveDataAttributes = RemoveDataAttributes::class;

    case RemoveDefaultAttributes = RemoveDefaultAttributes::class;

    case RemoveDescriptions = RemoveDescriptions::class;

    case RemoveDuplicateElements = RemoveDuplicateElements::class;

    case RemoveEmptyAttributes = RemoveEmptyAttributes::class;

    case RemoveEmptyElements = RemoveEmptyContainers::class;

    case RemoveForeignContent = RemoveForeignContent::class;

    case RemoveHiddenElements = RemoveHiddenElements::class;

    case RemoveIneffectiveAttributes = RemoveUselessStrokeAndFill::class;

    case RemoveInkscapeFootprints = RemoveInkscapeFootprints::class;

    case RemoveInvisibleCharacters = RemoveInvisibleCharacters::class;

    case RemoveMetadata = RemoveMetadata::class;

    case RemoveNonStandardAttributes = RemoveNonStandardAttributes::class;

    case RemoveNonStandardTags = RemoveNonStandardTags::class;

    case RemoveScripts = RemoveScripts::class;

    case RemoveTitles = RemoveTitles::class;

    case RemoveUnnecessaryWhitespace = RemoveUnnecessaryWhitespace::class;

    case RemoveUnsafeMarkup = RemoveUnsafeMarkup::class;

    case RemoveUnsafeUrls = RemoveUnsafeUrls::class;

    case RemoveUnusedDefinitions = RemoveUnusedDefinitions::class;

    case RemoveUnusedIds = RemoveUnusedIds::class;

    case RemoveUnusedNamespaces = RemoveUnusedNamespaces::class;

    case RemoveUseBombs = RemoveUseBombs::class;

    case RemoveUselessDefs = RemoveUselessDefs::class;

    case RemoveWidthHeightAttributes = RemoveWidthHeightAttributes::class;

    case SanitizeStyleSheets = SanitizeStyleSheets::class;

    case ScopeSvgStyles = ScopeSvgStyles::class;

    case SortAttributes = SortAttributes::class;

    case SortDefsChildren = SortDefsChildren::class;

    public function configKey(): string
    {
        return lcfirst($this->name);
    }

    /**
     * @return bool True if the rule may change how the document renders or is used, so it only runs when risky rules
     *              are allowed
     */
    public function isRisky(): bool
    {
        return match ($this) {
            self::FixAttributeNames,
            self::RemoveDataAttributes,
            self::RemoveWidthHeightAttributes,
            self::RemoveUnusedIds,
            self::RemoveUnusedDefinitions,
            self::ConvertOneStopGradients,
            self::MergeDuplicateGradients,
            self::ScopeSvgStyles => true,
            self::ConvertColorsToHex,
            self::ConvertCssClassesToAttributes,
            self::ConvertEllipseToCircle,
            self::ConvertInlineStylesToAttributes,
            self::ConvertShapesToPath,
            self::FlattenGroups,
            self::MergeStyles,
            self::MigrateToSvg2,
            self::MinifyNumericValues,
            self::MinifyPathData,
            self::MinifyStyles,
            self::MinifyTransformations,
            self::MoveCommonAttributesToGroup,
            self::MoveGroupAttributesToElements,
            self::RemoveAriaAndRole,
            self::RemoveComments,
            self::RemoveDefaultAttributes,
            self::RemoveDescriptions,
            self::RemoveDuplicateElements,
            self::RemoveEmptyAttributes,
            self::RemoveEmptyElements,
            self::RemoveForeignContent,
            self::RemoveHiddenElements,
            self::RemoveIneffectiveAttributes,
            self::RemoveInkscapeFootprints,
            self::RemoveInvisibleCharacters,
            self::RemoveMetadata,
            self::RemoveNonStandardAttributes,
            self::RemoveNonStandardTags,
            self::RemoveScripts,
            self::RemoveTitles,
            self::RemoveUnnecessaryWhitespace,
            self::RemoveUnsafeMarkup,
            self::RemoveUnsafeUrls,
            self::RemoveUnusedNamespaces,
            self::RemoveUseBombs,
            self::RemoveUselessDefs,
            self::SanitizeStyleSheets,
            self::SortAttributes,
            self::SortDefsChildren => false,
        };
    }

    /**
     * @return non-empty-list<class-string<PassInterface>> The passes the rule runs
     *
     * @internal
     */
    public function passes(): array
    {
        return match ($this) {
            self::MigrateToSvg2 => [MigrateToSvg2::class, RemoveDeprecatedAttributes::class, RemoveEnableBackgroundAttribute::class],
            self::MinifyNumericValues => [MinifyNumericValues::class, MinifyListValues::class],
            self::RemoveDefaultAttributes => [RemoveDefaultAttributes::class, RemoveStyleTypeAttributes::class, RemoveXmlSpaceDefault::class],
            self::RemoveEmptyElements => [RemoveEmptyContainers::class, RemoveEmptyTextElements::class],
            self::RemoveHiddenElements => [RemoveHiddenElements::class, RemoveZeroSizeShapes::class],
            self::RemoveIneffectiveAttributes => [RemoveUselessStrokeAndFill::class, RemoveNonInheritableGroupAttributes::class, RemoveUnusedTextAttributes::class],
            self::ConvertColorsToHex => [ConvertColorsToHex::class],
            self::ConvertCssClassesToAttributes => [ConvertCssClassesToAttributes::class],
            self::ConvertEllipseToCircle => [ConvertEllipseToCircle::class],
            self::ConvertInlineStylesToAttributes => [ConvertInlineStylesToAttributes::class],
            self::ConvertOneStopGradients => [ConvertOneStopGradients::class],
            self::ConvertShapesToPath => [ConvertShapesToPath::class],
            self::FixAttributeNames => [FixAttributeNames::class],
            self::FlattenGroups => [FlattenGroups::class],
            self::MergeDuplicateGradients => [MergeDuplicateGradients::class],
            self::MergeStyles => [MergeStyles::class],
            self::MinifyPathData => [MinifyPathData::class],
            self::MinifyStyles => [MinifyStyles::class],
            self::MinifyTransformations => [MinifyTransformations::class],
            self::MoveCommonAttributesToGroup => [MoveCommonAttributesToGroup::class],
            self::MoveGroupAttributesToElements => [MoveGroupAttributesToElements::class],
            self::RemoveAriaAndRole => [RemoveAriaAndRole::class],
            self::RemoveComments => [RemoveComments::class],
            self::RemoveDataAttributes => [RemoveDataAttributes::class],
            self::RemoveDescriptions => [RemoveDescriptions::class],
            self::RemoveDuplicateElements => [RemoveDuplicateElements::class],
            self::RemoveEmptyAttributes => [RemoveEmptyAttributes::class],
            self::RemoveForeignContent => [RemoveForeignContent::class],
            self::RemoveInkscapeFootprints => [RemoveInkscapeFootprints::class],
            self::RemoveInvisibleCharacters => [RemoveInvisibleCharacters::class],
            self::RemoveMetadata => [RemoveMetadata::class],
            self::RemoveNonStandardAttributes => [RemoveNonStandardAttributes::class],
            self::RemoveNonStandardTags => [RemoveNonStandardTags::class],
            self::RemoveScripts => [RemoveScripts::class],
            self::RemoveTitles => [RemoveTitles::class],
            self::RemoveUnnecessaryWhitespace => [RemoveUnnecessaryWhitespace::class],
            self::RemoveUnsafeMarkup => [RemoveUnsafeMarkup::class],
            self::RemoveUnsafeUrls => [RemoveUnsafeUrls::class],
            self::RemoveUnusedDefinitions => [RemoveUnusedDefinitions::class],
            self::RemoveUnusedIds => [RemoveUnusedIds::class],
            self::RemoveUnusedNamespaces => [RemoveUnusedNamespaces::class],
            self::RemoveUseBombs => [RemoveUseBombs::class],
            self::RemoveUselessDefs => [RemoveUselessDefs::class],
            self::RemoveWidthHeightAttributes => [RemoveWidthHeightAttributes::class],
            self::SanitizeStyleSheets => [SanitizeStyleSheets::class],
            self::ScopeSvgStyles => [ScopeSvgStyles::class],
            self::SortAttributes => [SortAttributes::class],
            self::SortDefsChildren => [SortDefsChildren::class],
        };
    }
}
