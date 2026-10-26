<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait RuleNameTrait
{
    use SuggestionTrait;

    private const string DEFAULT_SET = 'default';

    private const array LEGACY_RULES = [
        'convertColorsToHex' => 'was renamed to "minifyColors"',
        'convertEllipseToCircle' => 'was renamed to "convertEllipsesToCircles"',
        'convertEmptyTagsToSelfClosing' => 'was removed; it had no effect',
        'convertOneStopGradients' => 'was renamed to "convertOneStopGradientsToColors"',
        'convertShapesToPath' => 'was renamed to "convertShapesToPaths"',
        'mergeStyles' => 'was renamed to "mergeStyleSheets"',
        'minifyListValues' => 'was merged into "minifyNumericValues"',
        'minifyStyles' => 'was renamed to "minifyStyleSheets"',
        'minifySvgCoordinates' => 'was removed; "minifyNumericValues" and "minifyPathData" cover it',
        'minifyTransformations' => 'was renamed to "minifyTransforms"',
        'moveCommonAttributesToGroup' => 'was renamed to "moveCommonAttributesToGroups"',
        'moveGroupAttributesToElements' => 'was renamed to "moveGroupTransformsToChildren"',
        'removeAriaAndRole' => 'was renamed to "removeAriaAttributes"',
        'removeDeprecatedAttributes' => 'was merged into "migrateToSvg2"',
        'removeDoctype' => 'was removed; it had no effect',
        'removeEmptyContainers' => 'was merged into "removeEmptyElements"',
        'removeEmptyGroups' => 'was merged into "removeEmptyElements"',
        'removeEmptyTextElements' => 'was merged into "removeEmptyElements"',
        'removeEnableBackgroundAttribute' => 'was merged into "migrateToSvg2"',
        'removeInkscapeFootprints' => 'was renamed to "removeEditorMarkup"',
        'removeNonInheritableGroupAttributes' => 'was merged into "removeIneffectiveAttributes"',
        'removeNonStandardTags' => 'was renamed to "removeNonStandardElements"',
        'removeStyleTypeAttributes' => 'was merged into "removeDefaultAttributes"',
        'removeTitleAndDesc' => 'was split into "removeTitles" and "removeDescriptions"',
        'removeUnnecessaryWhitespace' => 'was renamed to "minifyAttributeWhitespace"',
        'removeUnsafeElements' => 'was split into the rules of the "security" set',
        'removeUnusedMasks' => 'was merged into "removeUnusedDefinitions"',
        'removeUnusedTextAttributes' => 'was merged into "removeIneffectiveAttributes"',
        'removeUselessDefs' => 'was renamed to "removeIneffectiveDefinitions"',
        'removeUselessStrokeAndFill' => 'was merged into "removeIneffectiveAttributes"',
        'removeXmlSpaceDefault' => 'was merged into "removeDefaultAttributes"',
        'removeZeroSizeShapes' => 'was merged into "removeHiddenElements"',
        'sanitizeStyleSheets' => 'was renamed to "removeUnsafeStyles"',
        'scopeSvgStyles' => 'was renamed to "scopeIdsAndClasses"',
        'sortDefsChildren' => 'was renamed to "sortDefinitions"',
    ];

    /**
     * @param list<string> $names the rule names
     *
     * @return list<Rule> the rules
     *
     * @throws \InvalidArgumentException If a name is not the name of a rule
     */
    private static function parseRules(array $names): array
    {
        return array_map(self::parseRule(...), $names);
    }

    /**
     * @param string $name the rule name
     *
     * @throws \InvalidArgumentException If the name is not the name of a rule
     */
    private static function parseRule(string $name): Rule
    {
        return Rule::tryFrom($name) ?? throw new \InvalidArgumentException(\array_key_exists($name, self::LEGACY_RULES) ? \sprintf('Rule "%s" %s.', $name, self::LEGACY_RULES[$name]) : \sprintf('Unknown rule "%s".%s Run svg-optimizer --list-rules to see the rules.', $name, self::suggest($name, array_column(Rule::cases(), 'value'))));
    }

    /**
     * @param string $name a key that may be a rule name of this version or of 9.x
     *
     * @return bool true if the name is a rule name of this version or of 9.x
     */
    private static function isRuleName(string $name): bool
    {
        return null !== Rule::tryFrom($name) || \array_key_exists($name, self::LEGACY_RULES);
    }

    /**
     * @param list<string> $names the rule set names, which may include "default"
     *
     * @return list<RuleSet> the rule sets, without "default"
     *
     * @throws \InvalidArgumentException If a name is neither "default" nor the name of a rule set
     */
    private static function parseRuleSets(array $names): array
    {
        return array_values(array_map(
            self::parseRuleSet(...),
            array_filter($names, static fn (string $name): bool => self::DEFAULT_SET !== $name),
        ));
    }

    /**
     * @param list<string> $names the rule set names, which may include "default"
     *
     * @return bool true if the names include "default"
     */
    private static function includesDefaultSet(array $names): bool
    {
        return \in_array(self::DEFAULT_SET, $names, true);
    }

    /**
     * @param string $name the rule set name
     *
     * @throws \InvalidArgumentException If the name is not the name of a rule set
     */
    private static function parseRuleSet(string $name): RuleSet
    {
        return RuleSet::tryFrom($name) ?? throw new \InvalidArgumentException(\sprintf('Unknown rule set "%s".%s Valid rule sets are: %s.', $name, self::suggest($name, [self::DEFAULT_SET, ...array_column(RuleSet::cases(), 'value')]), implode(', ', [self::DEFAULT_SET, ...array_column(RuleSet::cases(), 'value')])));
    }
}
