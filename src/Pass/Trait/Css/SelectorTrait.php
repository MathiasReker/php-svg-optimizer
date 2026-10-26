<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Css;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait SelectorTrait
{
    private const string CSS_PROTECTED_TOKEN = '"(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\'|(?<parens>\((?:[^()"\'\\\]|\\\.|"(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\'|(?&parens))*\))|\\\.';

    /**
     * @param string $selectors a selector list
     *
     * @return list<string> the trimmed selectors, split at the commas outside strings and parentheses, such as those of
     *                      `:is(a, b)`; the whole list if it cannot be split safely
     */
    private function splitSelectorList(string $selectors): array
    {
        $parts = $this->isSplittable($selectors) ? preg_split('/(?:' . self::CSS_PROTECTED_TOKEN . ')(*SKIP)(*FAIL)|,/s', $selectors) : false;

        return array_map(trim(...), false === $parts ? [$selectors] : $parts);
    }

    /**
     * @param string $css CSS without braces, such as a selector list or the value of a style attribute
     *
     * @return bool true if every string and parenthesis in the CSS is closed, so it can be split at its commas or
     *              semicolons safely
     */
    private function isSplittable(string $css): bool
    {
        return 1 === preg_match('/^(?:' . self::CSS_PROTECTED_TOKEN . '|[^"\'()\\\])*$/s', $css);
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
}
