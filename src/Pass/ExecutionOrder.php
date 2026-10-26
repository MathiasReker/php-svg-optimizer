<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

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
        RemoveUnsafeStyles::class,
        RemoveUseBombs::class,
        RemoveComments::class,
        RemoveMetadata::class,
        RemoveEditorMarkup::class,
        FixAttributeNames::class,
        ScopeIdsAndClasses::class,
        RemoveUnusedIds::class,
        MigrateToSvg2::class,
        RemoveNonStandardElements::class,
        MigrateDeprecatedAttributes::class,
        RemoveTitles::class,
        RemoveDescriptions::class,
        RemoveNonStandardAttributes::class,
        RemoveDataAttributes::class,
        RemoveAriaAttributes::class,
        RemoveInvisibleCharacters::class,
        RemoveEnableBackground::class,
        RemoveDefaultTypeAttributes::class,
        RemoveDefaultXmlSpace::class,
        RemoveWidthHeightAttributes::class,
        MergeStyleSheets::class,
        MinifyStyleSheets::class,
        ConvertCssClassesToAttributes::class,
        ConvertInlineStylesToAttributes::class,
        RemoveEmptyAttributes::class,
        MinifyNumericValues::class,
        MinifyNumberLists::class,
        MinifyColors::class,
        MergeDuplicateGradients::class,
        ConvertOneStopGradientsToColors::class,
        RemoveIneffectiveGroupAttributes::class,
        RemoveHiddenElements::class,
        RemoveZeroSizeShapes::class,
        RemoveIneffectiveDefinitions::class,
        RemoveDuplicateElements::class,
        RemoveEmptyTextElements::class,
        RemoveIneffectiveTextAttributes::class,
        RemoveEmptyContainers::class,
        RemoveUnusedDefinitions::class,
        FlattenGroups::class,
        MoveGroupTransformsToChildren::class,
        MoveCommonAttributesToGroups::class,
        RemoveIneffectiveStrokeAndFill::class,
        RemoveDefaultAttributes::class,
        MinifyTransforms::class,
        ConvertEllipsesToCircles::class,
        ConvertShapesToPaths::class,
        MinifyPathData::class,
        RemoveUnusedNamespaces::class,
        MinifyAttributeWhitespace::class,
        SortDefinitions::class,
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
        usort($passes, static fn (PassInterface $a, PassInterface $b): int => self::position($a::class) <=> self::position($b::class));

        return $passes;
    }

    /**
     * @param class-string<PassInterface> $passClass the pass to look up
     *
     * @return int the position of the pass in the execution order, or PHP_INT_MAX if it has none
     */
    public static function position(string $passClass): int
    {
        $position = array_search($passClass, self::PASSES, true);

        return false === $position ? \PHP_INT_MAX : $position;
    }
}
