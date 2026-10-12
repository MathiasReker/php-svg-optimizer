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
 * Splits a CSS declaration block on the semicolons that separate declarations. A semicolon inside a string, inside
 * parentheses such as `url(data:image/png;base64,...)`, or escaped with a backslash is part of a value.
 *
 * @internal
 *
 * @no-named-arguments
 */
trait CssDeclarationTrait
{
    /**
     * A string, a parenthesized group (which may nest and contain strings) or an escaped character. The named group
     * recurses for nested parentheses.
     */
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
}
