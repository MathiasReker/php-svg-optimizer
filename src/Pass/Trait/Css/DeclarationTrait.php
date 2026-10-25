<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Css;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait DeclarationTrait
{
    use AsciiCaseTrait;
    use SelectorTrait;

    private const string CSS_ALL_PROPERTIES = 'all';

    private const string CSS_IMPORTANT_MARKER = '!';

    private const string CSS_PLAIN_RULE_REGEX = '/([^{}]+)\{([^}]+)}/';

    private const string CSS_COMMENT_START = '/*';

    private const array CSS_SHORTHAND_PROPERTIES = ['font', 'marker', 'mask', 'overflow'];

    /**
     * @param string $block A declaration block without braces, such as the value of a style attribute
     *
     * @return bool True if every string and parenthesis in the block is closed, so the block can be split safely
     */
    private function isSplittableDeclarationBlock(string $block): bool
    {
        return 1 === preg_match('/^(?:' . self::CSS_PROTECTED_TOKEN . '|[^"\'()\\\])*$/s', $block);
    }

    /**
     * @param string $css the CSS content of a `<style>` element
     *
     * @return bool true if the CSS consists of plain rules only, without comments, at-rules or nesting, and with
     *              declaration blocks that can be split safely
     */
    private function isPlainRuleList(string $css): bool
    {
        preg_match_all(self::CSS_PLAIN_RULE_REGEX, $css, $matches);

        return !str_contains($css, self::CSS_COMMENT_START)
            && '' === trim((string) preg_replace(self::CSS_PLAIN_RULE_REGEX, '', $css))
            && [] === array_filter($matches[2], fn (string $declarations): bool => !$this->isSplittableDeclarationBlock($declarations));
    }

    /**
     * @param string $block A declaration block that passes isSplittableDeclarationBlock()
     *
     * @return list<string> The declarations, without the separating semicolons; empty declarations are left out
     */
    private function splitDeclarations(string $block): array
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
        return $this->declaresAnyProperty($css, [preg_quote($property, '/'), ...$this->relatedPropertyPatterns($property)]);
    }

    /**
     * @param string $css      the CSS to search
     * @param string $property a lowercase property name
     *
     * @return bool true if the CSS may declare `all`, a shorthand that sets the property, or a longhand that it sets
     */
    private function declaresRelatedProperty(string $css, string $property): bool
    {
        return $this->declaresAnyProperty($css, $this->relatedPropertyPatterns($property));
    }

    /**
     * @param string $property a lowercase property name
     *
     * @return non-empty-list<string> the patterns of `all` and of the shorthands and longhands of the property
     */
    private function relatedPropertyPatterns(string $property): array
    {
        $properties = [self::CSS_ALL_PROPERTIES];

        foreach (self::CSS_SHORTHAND_PROPERTIES as $shorthand) {
            if (str_starts_with($property, $shorthand . '-')) {
                $properties[] = $shorthand;
            }
        }

        if (\in_array($property, self::CSS_SHORTHAND_PROPERTIES, true)) {
            $properties[] = $property . '-[a-z-]+';
        }

        return $properties;
    }

    /**
     * @param string                 $css      the CSS to search
     * @param non-empty-list<string> $patterns the property name patterns
     */
    private function declaresAnyProperty(string $css, array $patterns): bool
    {
        return 1 === preg_match('/[{;\s](?:-[a-z]+-)?(?:' . implode('|', $patterns) . ')\s*:/i', $css);
    }

    /**
     * @param string $block      a declaration block that passes isSplittableDeclarationBlock()
     * @param string $otherCss   the CSS that stays CSS
     * @param string $relatedCss the CSS whose shorthands, longhands and `all` may override a converted declaration
     *
     * @return array<string, true> the lowercase properties of the block that have to stay CSS, as they are declared
     *                             more than once, important, set or selected by the other CSS, or overridden by a
     *                             related property
     */
    private function propertiesThatStayCss(string $block, string $otherCss, string $relatedCss): array
    {
        return $this->repeatedOrImportantProperties($block)
            + $this->propertiesOverriddenByCss($this->propertyNames($block), $otherCss, $relatedCss);
    }

    /**
     * @param string $block a declaration block that passes isSplittableDeclarationBlock()
     *
     * @return array<string, true> the lowercase properties of the block that are declared more than once or important
     */
    private function repeatedOrImportantProperties(string $block): array
    {
        $properties = $this->propertyNames($block);
        $counts = array_count_values($properties);
        $pinned = [];

        foreach ($this->parseDeclarations($block) as $index => [, $value]) {
            if ($counts[$properties[$index]] > 1 || str_contains($value, self::CSS_IMPORTANT_MARKER)) {
                $pinned[$properties[$index]] = true;
            }
        }

        return $pinned;
    }

    /**
     * @param list<string> $properties lowercase property names
     * @param string       $otherCss   the CSS that stays CSS
     * @param string       $relatedCss the CSS whose shorthands, longhands and `all` may override a converted declaration
     *
     * @return array<string, true> the properties that the other CSS sets or selects, or that a related property overrides
     */
    private function propertiesOverriddenByCss(array $properties, string $otherCss, string $relatedCss): array
    {
        $pinned = [];

        foreach ($properties as $property) {
            if ($this->isAffectedByOtherCss($otherCss, $property) || $this->declaresRelatedProperty($relatedCss, $property)) {
                $pinned[$property] = true;
            }
        }

        return $pinned;
    }

    /**
     * @param string $block a declaration block that passes isSplittableDeclarationBlock()
     *
     * @return list<string> the lowercase property name of each declaration with a colon
     */
    private function propertyNames(string $block): array
    {
        return array_map(
            fn (array $declaration): string => $this->asciiLowercase($declaration[0]),
            $this->parseDeclarations($block),
        );
    }

    /**
     * @param string $block A declaration block that passes isSplittableDeclarationBlock()
     *
     * @return list<array{string, string}> the property name and value of each declaration, both trimmed; declarations
     *                                     without a colon are left out
     */
    private function parseDeclarations(string $block): array
    {
        $declarations = [];

        foreach ($this->splitDeclarations($block) as $declaration) {
            if (str_contains($declaration, ':')) {
                [$property, $value] = explode(':', $declaration, 2);
                $declarations[] = [trim($property), trim($value)];
            }
        }

        return $declarations;
    }

    /**
     * @param string            $block    a declaration block that passes isSplittableDeclarationBlock()
     * @param list<\DOMElement> $elements the elements that the declarations would become attributes of
     *
     * @return array<string, true> the lowercase properties that an element already sets as an attribute with another
     *                             value, which the declaration may not override if its value is invalid
     */
    private function differentlySetProperties(string $block, array $elements): array
    {
        $properties = [];

        foreach ($this->parseDeclarations($block) as [$property, $value]) {
            $property = $this->asciiLowercase($property);

            foreach ($elements as $element) {
                if ($element->hasAttribute($property) && $element->getAttribute($property) !== $value) {
                    $properties[$property] = true;
                }
            }
        }

        return $properties;
    }
}
