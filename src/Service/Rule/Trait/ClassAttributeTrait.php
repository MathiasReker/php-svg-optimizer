<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ClassAttributeTrait
{
    private const string CLASS_SEPARATOR_REGEX = '/\s+/';

    private const string CLASS_ATTRIBUTE_SELECTOR_REGEX = '/\[\s*(?:[\w*-]*\|)?class\b/i';

    /**
     * @param \DOMElement $domElement the element to read the classes of
     *
     * @return list<string> the classes of the element, which are separated by any whitespace
     */
    private function classList(\DOMElement $domElement): array
    {
        $classes = preg_split(
            self::CLASS_SEPARATOR_REGEX,
            $domElement->getAttribute(SvgAttribute::Class_->value),
            -1,
            \PREG_SPLIT_NO_EMPTY
        );

        return false === $classes ? [] : $classes;
    }

    /**
     * @param string $css   the style sheet to search
     * @param string $class a class name consisting of letters, digits, `_` and `-` only
     *
     * @return bool true if the style sheet may match the class, through a class selector or an attribute selector on
     *              `class`, such as `[class="a"]`, which matches the whole class list
     */
    private function cssMatchesClass(string $css, string $class): bool
    {
        return 1 === preg_match(self::CLASS_ATTRIBUTE_SELECTOR_REGEX, $css)
            || 1 === preg_match('/\.' . $class . '(?![\w-])/', $css);
    }

    /**
     * @param \DOMElement $domElement the element to update
     * @param string      $class      the class to remove
     */
    private function removeClass(\DOMElement $domElement, string $class): void
    {
        $classes = array_filter($this->classList($domElement), static fn (string $c): bool => $c !== $class);

        if ([] !== $classes) {
            $domElement->setAttribute(SvgAttribute::Class_->value, implode(' ', $classes));
        } else {
            $domElement->removeAttribute(SvgAttribute::Class_->value);
        }
    }
}
