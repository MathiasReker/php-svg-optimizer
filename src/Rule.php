<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer;

use MathiasReker\PhpSvgOptimizer\Pass\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertEllipsesToCircles;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertOneStopGradientsToColors;
use MathiasReker\PhpSvgOptimizer\Pass\ConvertShapesToPaths;
use MathiasReker\PhpSvgOptimizer\Pass\FixAttributeNames;
use MathiasReker\PhpSvgOptimizer\Pass\FlattenGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MergeDuplicateGradients;
use MathiasReker\PhpSvgOptimizer\Pass\MergeStyleSheets;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\MigrateToSvg2;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyAttributeWhitespace;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyColors;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumberLists;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyStyleSheets;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyTransforms;
use MathiasReker\PhpSvgOptimizer\Pass\MoveCommonAttributesToGroups;
use MathiasReker\PhpSvgOptimizer\Pass\MoveGroupTransformsToChildren;
use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveAriaAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDataAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultTypeAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultXmlSpace;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDescriptions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEditorMarkup;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyContainers;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEnableBackground;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveForeignContent;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveHiddenElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveGroupAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveStrokeAndFill;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveIneffectiveTextAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveNonStandardElements;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveScripts;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveTitles;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeMarkup;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeStyles;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnsafeUrls;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedIds;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUseBombs;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveZeroSizeShapes;
use MathiasReker\PhpSvgOptimizer\Pass\ScopeIdsAndClasses;
use MathiasReker\PhpSvgOptimizer\Pass\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\SortDefinitions;

/**
 * @api
 *
 * @no-named-arguments
 */
enum Rule: string
{
    case ConvertCssClassesToAttributes = 'convertCssClassesToAttributes';

    case ConvertEllipsesToCircles = 'convertEllipsesToCircles';

    case ConvertInlineStylesToAttributes = 'convertInlineStylesToAttributes';

    case ConvertOneStopGradientsToColors = 'convertOneStopGradientsToColors';

    case ConvertShapesToPaths = 'convertShapesToPaths';

    case FixAttributeNames = 'fixAttributeNames';

    case FlattenGroups = 'flattenGroups';

    case MergeDuplicateGradients = 'mergeDuplicateGradients';

    case MergeStyleSheets = 'mergeStyleSheets';

    case MigrateToSvg2 = 'migrateToSvg2';

    case MinifyAttributeWhitespace = 'minifyAttributeWhitespace';

    case MinifyColors = 'minifyColors';

    case MinifyNumericValues = 'minifyNumericValues';

    case MinifyPathData = 'minifyPathData';

    case MinifyStyleSheets = 'minifyStyleSheets';

    case MinifyTransforms = 'minifyTransforms';

    case MoveCommonAttributesToGroups = 'moveCommonAttributesToGroups';

    case MoveGroupTransformsToChildren = 'moveGroupTransformsToChildren';

    case ScopeIdsAndClasses = 'scopeIdsAndClasses';

    case RemoveAriaAttributes = 'removeAriaAttributes';

    case RemoveComments = 'removeComments';

    case RemoveDataAttributes = 'removeDataAttributes';

    case RemoveDefaultAttributes = 'removeDefaultAttributes';

    case RemoveDescriptions = 'removeDescriptions';

    case RemoveDuplicateElements = 'removeDuplicateElements';

    case RemoveEditorMarkup = 'removeEditorMarkup';

    case RemoveEmptyAttributes = 'removeEmptyAttributes';

    case RemoveEmptyElements = 'removeEmptyElements';

    case RemoveForeignContent = 'removeForeignContent';

    case RemoveHiddenElements = 'removeHiddenElements';

    case RemoveIneffectiveAttributes = 'removeIneffectiveAttributes';

    case RemoveIneffectiveDefinitions = 'removeIneffectiveDefinitions';

    case RemoveInvisibleCharacters = 'removeInvisibleCharacters';

    case RemoveMetadata = 'removeMetadata';

    case RemoveNonStandardAttributes = 'removeNonStandardAttributes';

    case RemoveNonStandardElements = 'removeNonStandardElements';

    case RemoveScripts = 'removeScripts';

    case RemoveTitles = 'removeTitles';

    case RemoveUnsafeMarkup = 'removeUnsafeMarkup';

    case RemoveUnsafeStyles = 'removeUnsafeStyles';

    case RemoveUnsafeUrls = 'removeUnsafeUrls';

    case RemoveUnusedDefinitions = 'removeUnusedDefinitions';

    case RemoveUnusedIds = 'removeUnusedIds';

    case RemoveUnusedNamespaces = 'removeUnusedNamespaces';

    case RemoveUseBombs = 'removeUseBombs';

    case RemoveWidthHeightAttributes = 'removeWidthHeightAttributes';

    case SortAttributes = 'sortAttributes';

    case SortDefinitions = 'sortDefinitions';

    /**
     * @return bool true if the rule may change how the document renders or is used, so it only runs when risky rules
     *              are allowed
     */
    public function isRisky(): bool
    {
        return match ($this) {
            self::ConvertOneStopGradientsToColors,
            self::FixAttributeNames,
            self::MergeDuplicateGradients,
            self::ScopeIdsAndClasses,
            self::RemoveDataAttributes,
            self::RemoveUnusedDefinitions,
            self::RemoveUnusedIds,
            self::RemoveWidthHeightAttributes => true,
            self::ConvertCssClassesToAttributes,
            self::ConvertEllipsesToCircles,
            self::ConvertInlineStylesToAttributes,
            self::ConvertShapesToPaths,
            self::FlattenGroups,
            self::MergeStyleSheets,
            self::MigrateToSvg2,
            self::MinifyAttributeWhitespace,
            self::MinifyColors,
            self::MinifyNumericValues,
            self::MinifyPathData,
            self::MinifyStyleSheets,
            self::MinifyTransforms,
            self::MoveCommonAttributesToGroups,
            self::MoveGroupTransformsToChildren,
            self::RemoveAriaAttributes,
            self::RemoveComments,
            self::RemoveDefaultAttributes,
            self::RemoveDescriptions,
            self::RemoveDuplicateElements,
            self::RemoveEditorMarkup,
            self::RemoveEmptyAttributes,
            self::RemoveEmptyElements,
            self::RemoveForeignContent,
            self::RemoveHiddenElements,
            self::RemoveIneffectiveAttributes,
            self::RemoveIneffectiveDefinitions,
            self::RemoveInvisibleCharacters,
            self::RemoveMetadata,
            self::RemoveNonStandardAttributes,
            self::RemoveNonStandardElements,
            self::RemoveScripts,
            self::RemoveTitles,
            self::RemoveUnsafeMarkup,
            self::RemoveUnsafeStyles,
            self::RemoveUnsafeUrls,
            self::RemoveUnusedNamespaces,
            self::RemoveUseBombs,
            self::SortAttributes,
            self::SortDefinitions => false,
        };
    }

    /**
     * @return RuleSet|null the set the rule belongs to, or null if no set selects it
     */
    public function getRuleSet(): ?RuleSet
    {
        return match ($this) {
            self::RemoveForeignContent,
            self::RemoveScripts,
            self::RemoveUnsafeMarkup,
            self::RemoveUnsafeStyles,
            self::RemoveUnsafeUrls,
            self::RemoveUseBombs => RuleSet::Security,
            self::FixAttributeNames,
            self::MigrateToSvg2,
            self::RemoveNonStandardAttributes,
            self::RemoveNonStandardElements => RuleSet::Standards,
            self::RemoveComments,
            self::RemoveDataAttributes,
            self::RemoveDefaultAttributes,
            self::RemoveDuplicateElements,
            self::RemoveEditorMarkup,
            self::RemoveEmptyAttributes,
            self::RemoveEmptyElements,
            self::RemoveHiddenElements,
            self::RemoveIneffectiveAttributes,
            self::RemoveIneffectiveDefinitions,
            self::RemoveInvisibleCharacters,
            self::RemoveMetadata,
            self::RemoveUnusedDefinitions,
            self::RemoveUnusedIds,
            self::RemoveUnusedNamespaces,
            self::RemoveWidthHeightAttributes => RuleSet::Cleanup,
            self::ConvertCssClassesToAttributes,
            self::ConvertEllipsesToCircles,
            self::ConvertInlineStylesToAttributes,
            self::ConvertOneStopGradientsToColors,
            self::ConvertShapesToPaths,
            self::MinifyColors => RuleSet::Conversion,
            self::FlattenGroups,
            self::MergeDuplicateGradients,
            self::MergeStyleSheets,
            self::MoveCommonAttributesToGroups,
            self::MoveGroupTransformsToChildren => RuleSet::Structure,
            self::MinifyAttributeWhitespace,
            self::MinifyNumericValues,
            self::MinifyPathData,
            self::MinifyStyleSheets,
            self::MinifyTransforms,
            self::SortAttributes,
            self::SortDefinitions => RuleSet::Minification,
            self::ScopeIdsAndClasses,
            self::RemoveAriaAttributes,
            self::RemoveDescriptions,
            self::RemoveTitles => null,
        };
    }

    /**
     * @return non-empty-string what the rule does
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ConvertCssClassesToAttributes => 'Convert style sheet rules that select a single class to presentation attributes.',
            self::ConvertEllipsesToCircles => 'Convert ellipses with equal radii to circles.',
            self::ConvertInlineStylesToAttributes => 'Convert inline styles to presentation attributes.',
            self::ConvertOneStopGradientsToColors => 'Replace gradients with a single stop by the color of that stop.',
            self::ConvertShapesToPaths => 'Convert rect, line, polyline and polygon elements to paths.',
            self::FixAttributeNames => 'Fix the letter case of attribute names.',
            self::FlattenGroups => 'Flatten nested groups.',
            self::MergeDuplicateGradients => 'Merge identical gradients into one.',
            self::MergeStyleSheets => 'Merge style elements into one.',
            self::MigrateToSvg2 => 'Migrate SVG 1.1 and SVG Tiny markup to SVG 2, including deprecated attributes and enable-background.',
            self::MinifyAttributeWhitespace => 'Remove unnecessary whitespace from attribute values.',
            self::MinifyColors => 'Write each color in its shortest form: a name, #rgb or #rrggbb.',
            self::MinifyNumericValues => 'Shorten the notation of numeric attribute values and number lists.',
            self::MinifyPathData => 'Minify path data without changing coordinates.',
            self::MinifyStyleSheets => 'Remove comments and whitespace from style elements.',
            self::MinifyTransforms => 'Minify transform lists.',
            self::MoveCommonAttributesToGroups => 'Move attributes shared by all children of a group onto the group.',
            self::MoveGroupTransformsToChildren => 'Move the transform of a group onto its children.',
            self::ScopeIdsAndClasses => 'Append a unique suffix to ids and class names so several inlined SVGs do not collide.',
            self::RemoveAriaAttributes => 'Remove role and aria-* attributes.',
            self::RemoveComments => 'Remove comments.',
            self::RemoveDataAttributes => 'Remove data-* attributes.',
            self::RemoveDefaultAttributes => 'Remove attributes set to their default value, default type attributes on style and script elements and xml:space="default" where it has no effect.',
            self::RemoveDescriptions => 'Remove desc elements.',
            self::RemoveDuplicateElements => 'Remove duplicate elements.',
            self::RemoveEditorMarkup => 'Remove the elements, attributes and namespace declarations that editors add in their own namespaces.',
            self::RemoveEmptyAttributes => 'Remove empty attributes.',
            self::RemoveEmptyElements => 'Remove empty containers and empty text elements.',
            self::RemoveForeignContent => 'Remove foreign, embedding and HTML breakout elements and non-canonical names.',
            self::RemoveHiddenElements => 'Remove elements that cannot render anything, including shapes with a zero size.',
            self::RemoveIneffectiveAttributes => 'Remove attributes that have no effect: useless strokes and fills, group attributes that are neither inherited nor apply to groups, and text attributes on elements without text.',
            self::RemoveIneffectiveDefinitions => 'Remove definitions without an id.',
            self::RemoveInvisibleCharacters => 'Remove invisible characters.',
            self::RemoveMetadata => 'Remove metadata elements.',
            self::RemoveNonStandardAttributes => 'Remove attributes that are not part of the SVG specification.',
            self::RemoveNonStandardElements => 'Remove elements that are not part of the SVG specification.',
            self::RemoveScripts => 'Remove scripts, event handlers and animations that rewrite links or event handlers.',
            self::RemoveTitles => 'Remove title elements.',
            self::RemoveUnsafeMarkup => 'Remove processing instructions and unsafe comments.',
            self::RemoveUnsafeStyles => 'Remove unsafe statements and declarations from style sheets and style attributes.',
            self::RemoveUnsafeUrls => 'Remove attributes with URLs that can run script or load external content.',
            self::RemoveUnusedDefinitions => 'Remove definitions that are not referenced.',
            self::RemoveUnusedIds => 'Remove ids that are not referenced.',
            self::RemoveUnusedNamespaces => 'Remove unused namespace declarations.',
            self::RemoveUseBombs => 'Remove use elements that form cycles or render too many instances.',
            self::RemoveWidthHeightAttributes => 'Remove the width and height of the root element when it has a viewBox.',
            self::SortAttributes => 'Sort attributes.',
            self::SortDefinitions => 'Group the children of defs elements by element name.',
        };
    }

    /**
     * @return non-empty-list<class-string<PassInterface>> the passes the rule runs
     *
     * @internal
     */
    public function getPassClasses(): array
    {
        return match ($this) {
            self::MigrateToSvg2 => [MigrateToSvg2::class, MigrateDeprecatedAttributes::class, RemoveEnableBackground::class],
            self::MinifyNumericValues => [MinifyNumericValues::class, MinifyNumberLists::class],
            self::RemoveDefaultAttributes => [RemoveDefaultAttributes::class, RemoveDefaultTypeAttributes::class, RemoveDefaultXmlSpace::class],
            self::RemoveEmptyElements => [RemoveEmptyContainers::class, RemoveEmptyTextElements::class],
            self::RemoveHiddenElements => [RemoveHiddenElements::class, RemoveZeroSizeShapes::class],
            self::RemoveIneffectiveAttributes => [RemoveIneffectiveStrokeAndFill::class, RemoveIneffectiveGroupAttributes::class, RemoveIneffectiveTextAttributes::class],
            self::ConvertCssClassesToAttributes => [ConvertCssClassesToAttributes::class],
            self::ConvertEllipsesToCircles => [ConvertEllipsesToCircles::class],
            self::ConvertInlineStylesToAttributes => [ConvertInlineStylesToAttributes::class],
            self::ConvertOneStopGradientsToColors => [ConvertOneStopGradientsToColors::class],
            self::ConvertShapesToPaths => [ConvertShapesToPaths::class],
            self::FixAttributeNames => [FixAttributeNames::class],
            self::FlattenGroups => [FlattenGroups::class],
            self::MergeDuplicateGradients => [MergeDuplicateGradients::class],
            self::MergeStyleSheets => [MergeStyleSheets::class],
            self::MinifyAttributeWhitespace => [MinifyAttributeWhitespace::class],
            self::MinifyColors => [MinifyColors::class],
            self::MinifyPathData => [MinifyPathData::class],
            self::MinifyStyleSheets => [MinifyStyleSheets::class],
            self::MinifyTransforms => [MinifyTransforms::class],
            self::MoveCommonAttributesToGroups => [MoveCommonAttributesToGroups::class],
            self::MoveGroupTransformsToChildren => [MoveGroupTransformsToChildren::class],
            self::ScopeIdsAndClasses => [ScopeIdsAndClasses::class],
            self::RemoveAriaAttributes => [RemoveAriaAttributes::class],
            self::RemoveComments => [RemoveComments::class],
            self::RemoveDataAttributes => [RemoveDataAttributes::class],
            self::RemoveDescriptions => [RemoveDescriptions::class],
            self::RemoveDuplicateElements => [RemoveDuplicateElements::class],
            self::RemoveEditorMarkup => [RemoveEditorMarkup::class],
            self::RemoveEmptyAttributes => [RemoveEmptyAttributes::class],
            self::RemoveForeignContent => [RemoveForeignContent::class],
            self::RemoveIneffectiveDefinitions => [RemoveIneffectiveDefinitions::class],
            self::RemoveInvisibleCharacters => [RemoveInvisibleCharacters::class],
            self::RemoveMetadata => [RemoveMetadata::class],
            self::RemoveNonStandardAttributes => [RemoveNonStandardAttributes::class],
            self::RemoveNonStandardElements => [RemoveNonStandardElements::class],
            self::RemoveScripts => [RemoveScripts::class],
            self::RemoveTitles => [RemoveTitles::class],
            self::RemoveUnsafeMarkup => [RemoveUnsafeMarkup::class],
            self::RemoveUnsafeStyles => [RemoveUnsafeStyles::class],
            self::RemoveUnsafeUrls => [RemoveUnsafeUrls::class],
            self::RemoveUnusedDefinitions => [RemoveUnusedDefinitions::class],
            self::RemoveUnusedIds => [RemoveUnusedIds::class],
            self::RemoveUnusedNamespaces => [RemoveUnusedNamespaces::class],
            self::RemoveUseBombs => [RemoveUseBombs::class],
            self::RemoveWidthHeightAttributes => [RemoveWidthHeightAttributes::class],
            self::SortAttributes => [SortAttributes::class],
            self::SortDefinitions => [SortDefinitions::class],
        };
    }
}
