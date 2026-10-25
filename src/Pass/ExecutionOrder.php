<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

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
 * @internal
 *
 * @no-named-arguments
 */
final class ExecutionOrder
{
    private const array PASSES = [
        RemoveUnsafeMarkup::class,
        RemoveScripts::class,
        RemoveForeignContent::class,
        RemoveUnsafeUrls::class,
        SanitizeStyleSheets::class,
        RemoveUseBombs::class,
        RemoveComments::class,
        RemoveMetadata::class,
        RemoveInkscapeFootprints::class,
        FixAttributeNames::class,
        ScopeSvgStyles::class,
        RemoveUnusedIds::class,
        MigrateToSvg2::class,
        RemoveNonStandardTags::class,
        RemoveDeprecatedAttributes::class,
        RemoveTitles::class,
        RemoveDescriptions::class,
        RemoveNonStandardAttributes::class,
        RemoveDataAttributes::class,
        RemoveAriaAndRole::class,
        RemoveInvisibleCharacters::class,
        RemoveEnableBackgroundAttribute::class,
        RemoveStyleTypeAttributes::class,
        RemoveXmlSpaceDefault::class,
        RemoveWidthHeightAttributes::class,
        MergeStyles::class,
        MinifyStyles::class,
        ConvertCssClassesToAttributes::class,
        ConvertInlineStylesToAttributes::class,
        RemoveEmptyAttributes::class,
        MinifyNumericValues::class,
        MinifyListValues::class,
        ConvertColorsToHex::class,
        MergeDuplicateGradients::class,
        ConvertOneStopGradients::class,
        RemoveUnusedDefinitions::class,
        RemoveNonInheritableGroupAttributes::class,
        RemoveHiddenElements::class,
        RemoveZeroSizeShapes::class,
        RemoveUselessDefs::class,
        RemoveDuplicateElements::class,
        RemoveEmptyTextElements::class,
        RemoveUnusedTextAttributes::class,
        RemoveEmptyContainers::class,
        FlattenGroups::class,
        MoveGroupAttributesToElements::class,
        MoveCommonAttributesToGroup::class,
        RemoveUselessStrokeAndFill::class,
        RemoveDefaultAttributes::class,
        MinifyTransformations::class,
        ConvertEllipseToCircle::class,
        ConvertShapesToPath::class,
        MinifyPathData::class,
        RemoveUnusedNamespaces::class,
        RemoveUnnecessaryWhitespace::class,
        SortDefsChildren::class,
        SortAttributes::class,
    ];

    /**
     * @return list<class-string<PassInterface>> every pass, in the order the passes run
     */
    public static function passes(): array
    {
        return self::PASSES;
    }

    /**
     * @param list<PassInterface> $passes the passes to order
     *
     * @return list<PassInterface> the passes in execution order; passes without a position keep their relative order
     *                             and run last
     */
    public static function sort(array $passes): array
    {
        usort($passes, static fn (PassInterface $a, PassInterface $b): int => self::position($a) <=> self::position($b));

        return $passes;
    }

    private static function position(PassInterface $pass): int
    {
        $position = array_search($pass::class, self::PASSES, true);

        return false === $position ? \PHP_INT_MAX : $position;
    }
}
