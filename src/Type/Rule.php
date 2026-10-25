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
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEmptyTagsToSelfClosing;
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
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveCommonAttributesToGroup;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MoveGroupAttributesToElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveAriaAndRole;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDoctype;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyGroups;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonInheritableGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardTags;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveStyleTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveTitleAndDesc;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnnecessaryWhitespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnsafeElements;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedMasks;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedTextAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveXmlSpaceDefault;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveZeroSizeShapes;
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

    case ConvertEmptyTagsToSelfClosing = ConvertEmptyTagsToSelfClosing::class;

    case ConvertInlineStylesToAttributes = ConvertInlineStylesToAttributes::class;

    case ConvertOneStopGradients = ConvertOneStopGradients::class;

    case ConvertShapesToPath = ConvertShapesToPath::class;

    case FixAttributeNames = FixAttributeNames::class;

    case FlattenGroups = FlattenGroups::class;

    case MergeDuplicateGradients = MergeDuplicateGradients::class;

    case MergeStyles = MergeStyles::class;

    case MigrateToSvg2 = MigrateToSvg2::class;

    case MinifyListValues = MinifyListValues::class;

    case MinifyNumericValues = MinifyNumericValues::class;

    case MinifyPathData = MinifyPathData::class;

    case MinifyStyles = MinifyStyles::class;

    case MinifySvgCoordinates = MinifySvgCoordinates::class;

    case MinifyTransformations = MinifyTransformations::class;

    case MoveCommonAttributesToGroup = MoveCommonAttributesToGroup::class;

    case MoveGroupAttributesToElements = MoveGroupAttributesToElements::class;

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

    case RemoveEmptyContainers = RemoveEmptyContainers::class;

    case RemoveHiddenElements = RemoveHiddenElements::class;

    case RemoveInkscapeFootprints = RemoveInkscapeFootprints::class;

    case RemoveInvisibleCharacters = RemoveInvisibleCharacters::class;

    case RemoveMetadata = RemoveMetadata::class;

    case RemoveNonInheritableGroupAttributes = RemoveNonInheritableGroupAttributes::class;

    case RemoveNonStandardAttributes = RemoveNonStandardAttributes::class;

    case RemoveNonStandardTags = RemoveNonStandardTags::class;

    case RemoveStyleTypeAttributes = RemoveStyleTypeAttributes::class;

    case RemoveTitleAndDesc = RemoveTitleAndDesc::class;

    case RemoveUnnecessaryWhitespace = RemoveUnnecessaryWhitespace::class;

    case RemoveUnsafeElements = RemoveUnsafeElements::class;

    case RemoveUnusedDefinitions = RemoveUnusedDefinitions::class;

    case RemoveUnusedIds = RemoveUnusedIds::class;

    case RemoveUnusedMasks = RemoveUnusedMasks::class;

    case RemoveUnusedNamespaces = RemoveUnusedNamespaces::class;

    case RemoveUnusedTextAttributes = RemoveUnusedTextAttributes::class;

    case RemoveUselessDefs = RemoveUselessDefs::class;

    case RemoveUselessStrokeAndFill = RemoveUselessStrokeAndFill::class;

    case RemoveWidthHeightAttributes = RemoveWidthHeightAttributes::class;

    case RemoveXmlSpaceDefault = RemoveXmlSpaceDefault::class;

    case RemoveZeroSizeShapes = RemoveZeroSizeShapes::class;

    case ScopeSvgStyles = ScopeSvgStyles::class;

    case SortAttributes = SortAttributes::class;

    case SortDefsChildren = SortDefsChildren::class;

    public function configKey(): string
    {
        return lcfirst($this->name);
    }

    private const array EXECUTION_ORDER = [
        self::RemoveUnsafeElements,
        self::RemoveDoctype,
        self::RemoveComments,
        self::RemoveMetadata,
        self::RemoveInkscapeFootprints,
        self::FixAttributeNames,
        self::MigrateToSvg2,
        self::RemoveNonStandardTags,
        self::RemoveDeprecatedAttributes,
        self::RemoveTitleAndDesc,
        self::RemoveNonStandardAttributes,
        self::RemoveDataAttributes,
        self::RemoveAriaAndRole,
        self::RemoveInvisibleCharacters,
        self::RemoveEnableBackgroundAttribute,
        self::RemoveStyleTypeAttributes,
        self::RemoveXmlSpaceDefault,
        self::RemoveWidthHeightAttributes,
        self::ScopeSvgStyles,
        self::MergeStyles,
        self::MinifyStyles,
        self::ConvertCssClassesToAttributes,
        self::ConvertInlineStylesToAttributes,
        self::RemoveEmptyAttributes,
        self::MinifyNumericValues,
        self::MinifyListValues,
        self::MinifySvgCoordinates,
        self::ConvertColorsToHex,
        self::MergeDuplicateGradients,
        self::RemoveUnusedIds,
        self::ConvertOneStopGradients,
        self::RemoveUnusedDefinitions,
        self::RemoveNonInheritableGroupAttributes,
        self::RemoveHiddenElements,
        self::RemoveZeroSizeShapes,
        self::RemoveEmptyTextElements,
        self::RemoveUnusedTextAttributes,
        self::RemoveDuplicateElements,
        self::RemoveEmptyGroups,
        self::RemoveEmptyContainers,
        self::RemoveUnusedMasks,
        self::FlattenGroups,
        self::MoveGroupAttributesToElements,
        self::MoveCommonAttributesToGroup,
        self::RemoveUselessStrokeAndFill,
        self::RemoveDefaultAttributes,
        self::RemoveUselessDefs,
        self::MinifyTransformations,
        self::ConvertEllipseToCircle,
        self::ConvertShapesToPath,
        self::MinifyPathData,
        self::RemoveUnusedNamespaces,
        self::RemoveUnnecessaryWhitespace,
        self::ConvertEmptyTagsToSelfClosing,
        self::SortDefsChildren,
        self::SortAttributes,
    ];

    /**
     * @return int The position of the rule in the execution order; rules run in ascending priority
     *
     * @internal
     */
    public function priority(): int
    {
        return (int) array_search($this, self::EXECUTION_ORDER, true);
    }
}
