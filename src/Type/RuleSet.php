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
 * @api
 *
 * @no-named-arguments
 */
enum RuleSet: string
{
    case Security = 'security';

    case Standards = 'standards';

    case Cleanup = 'cleanup';

    case Conversion = 'conversion';

    case Structure = 'structure';

    case Minification = 'minification';

    /**
     * @return list<Rule> The rules of the set, in the order of their first pass
     */
    public function rules(): array
    {
        return match ($this) {
            self::Security => [
                Rule::RemoveUnsafeMarkup,
                Rule::RemoveScripts,
                Rule::RemoveForeignContent,
                Rule::RemoveUnsafeUrls,
                Rule::SanitizeStyleSheets,
                Rule::RemoveUseBombs,
            ],
            self::Standards => [
                Rule::FixAttributeNames,
                Rule::MigrateToSvg2,
                Rule::RemoveNonStandardTags,
                Rule::RemoveNonStandardAttributes,
            ],
            self::Cleanup => [
                Rule::RemoveComments,
                Rule::RemoveMetadata,
                Rule::RemoveInkscapeFootprints,
                Rule::RemoveUnusedIds,
                Rule::RemoveDataAttributes,
                Rule::RemoveInvisibleCharacters,
                Rule::RemoveDefaultAttributes,
                Rule::RemoveWidthHeightAttributes,
                Rule::RemoveEmptyAttributes,
                Rule::RemoveUnusedDefinitions,
                Rule::RemoveIneffectiveAttributes,
                Rule::RemoveHiddenElements,
                Rule::RemoveUselessDefs,
                Rule::RemoveDuplicateElements,
                Rule::RemoveEmptyElements,
                Rule::RemoveUnusedNamespaces,
            ],
            self::Conversion => [
                Rule::ConvertCssClassesToAttributes,
                Rule::ConvertInlineStylesToAttributes,
                Rule::ConvertColorsToHex,
                Rule::ConvertOneStopGradients,
                Rule::ConvertEllipseToCircle,
                Rule::ConvertShapesToPath,
            ],
            self::Structure => [
                Rule::MergeStyles,
                Rule::MergeDuplicateGradients,
                Rule::FlattenGroups,
                Rule::MoveGroupAttributesToElements,
                Rule::MoveCommonAttributesToGroup,
            ],
            self::Minification => [
                Rule::MinifyStyles,
                Rule::MinifyNumericValues,
                Rule::MinifySvgCoordinates,
                Rule::MinifyTransformations,
                Rule::MinifyPathData,
                Rule::RemoveUnnecessaryWhitespace,
                Rule::SortDefsChildren,
                Rule::SortAttributes,
            ],
        };
    }

    /**
     * @return bool True if the rule belongs to the set, false otherwise
     */
    public function contains(Rule $rule): bool
    {
        return \in_array($rule, $this->rules(), true);
    }
}
