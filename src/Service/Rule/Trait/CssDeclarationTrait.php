<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait CssDeclarationTrait
{
    private const string CSS_ALL_PROPERTIES = 'all';

    private const array CSS_SHORTHAND_PROPERTIES = ['font', 'marker', 'mask', 'overflow'];

    private const string CSS_PROTECTED_TOKEN = '"(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\'|(?<parens>\((?:[^()"\'\\\]|\\\.|"(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\'|(?&parens))*\))|\\\.';

    /**
     * @param string $block A declaration block without braces, such as the value of a style attribute
     *
     * @return bool True if every string and parenthesis in the block is closed, so the block can be split safely
     */
    private function isSplittableCssDeclarationBlock(string $block): bool
    {
        return 1 === preg_match('/^(?:' . self::CSS_PROTECTED_TOKEN . '|[^"\'()\\\])*$/s', $block);
    }

    /**
     * @param string $css           the CSS to search
     * @param string $attributeName the attribute name
     *
     * @return bool true if the CSS may contain an attribute selector for the attribute, such as `[fill]` or `[fill=red]`
     */
    private function hasAttributeSelector(string $css, string $attributeName): bool
    {
        return 1 === preg_match('/\[\s*(?:[\w*-]*\|)?' . preg_quote($attributeName, '/') . '(?![\w-])/i', $css);
    }

    /**
     * @param string $block A declaration block that passes isSplittableCssDeclarationBlock()
     *
     * @return list<string> The declarations, without the separating semicolons; empty declarations are left out
     */
    private function splitCssDeclarations(string $block): array
    {
        $declarations = preg_split('/(?:' . self::CSS_PROTECTED_TOKEN . ')(*SKIP)(*FAIL)|;/s', $block);

        if (false === $declarations) {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), $declarations),
            static fn (string $declaration): bool => '' !== $declaration,
        ));
    }

    /**
     * @param string $css      the CSS that stays CSS
     * @param string $property a lowercase property name
     *
     * @return bool true if the CSS may set the property, or may select the presentation attribute it is converted to
     */
    private function isAffectedByOtherCss(string $css, string $property): bool
    {
        return $this->declaresProperty($css, $property) || $this->hasAttributeSelector($css, $property);
    }

    /**
     * @param string $css      the CSS to search
     * @param string $property a lowercase property name
     *
     * @return bool true if the CSS may declare the property, a shorthand that sets it, or a longhand that it sets
     */
    private function declaresProperty(string $css, string $property): bool
    {
        $properties = [preg_quote($property, '/'), self::CSS_ALL_PROPERTIES];

        foreach (self::CSS_SHORTHAND_PROPERTIES as $shorthand) {
            if (str_starts_with($property, $shorthand . '-')) {
                $properties[] = $shorthand;
            }
        }

        if (\in_array($property, self::CSS_SHORTHAND_PROPERTIES, true)) {
            $properties[] = $property . '-[a-z-]+';
        }

        return 1 === preg_match('/[{;\s](?:-[a-z]+-)?(?:' . implode('|', $properties) . ')\s*:/i', $css);
    }
}
