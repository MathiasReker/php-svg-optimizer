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
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ClassSelectorTrait
{
    use AsciiCaseTrait;
    use DeclarationTrait;
    use SelectorTrait;

    private const string SIMPLE_CLASS_SELECTOR_REGEX = '/^\.(-?[a-zA-Z_][a-zA-Z0-9_-]*)$/';

    private const string CLASS_SEPARATOR_REGEX = '/\s+/';

    private const string CLASS_ATTRIBUTE_SELECTOR_REGEX = '/\[\s*(?:[\w*-]*\|)?class\b/i';

    private const string CSS_ESCAPE = '\\';

    /**
     * @param \DOMElement $domElement the element to read the classes of
     *
     * @return list<string> the classes of the element, which are separated by any whitespace
     */
    private function classList(\DOMElement $domElement): array
    {
        $classes = preg_split(
            self::CLASS_SEPARATOR_REGEX,
            $domElement->getAttribute(Attribute::Class_->value),
            -1,
            \PREG_SPLIT_NO_EMPTY
        );

        return false === $classes ? [] : $classes;
    }

    /**
     * @param string $selectors a selector list
     *
     * @return list<string> the class names, if every selector of the list is a single class selector with a valid
     *                      identifier, or an empty list otherwise, as one invalid selector invalidates the whole rule
     */
    private function classSelectorList(string $selectors): array
    {
        $classes = [];

        foreach ($this->splitSelectorList($selectors) as $selector) {
            if (1 !== preg_match(self::SIMPLE_CLASS_SELECTOR_REGEX, $selector, $selectorMatch)) {
                return [];
            }

            $classes[] = $selectorMatch[1];
        }

        return $classes;
    }

    /**
     * @param string $css plain CSS rules
     *
     * @return string the rules whose selector list is not made of single class selectors
     */
    private function rulesWithOtherSelectors(string $css): string
    {
        return (string) preg_replace_callback(
            self::CSS_PLAIN_RULE_REGEX,
            fn (array $match): string => [] === $this->classSelectorList($match[1]) ? $match[0] : '',
            $css
        );
    }

    /**
     * @param string                           $selectors a selector list
     * @param array<string, list<\DOMElement>> $classMap  the elements by class
     *
     * @return list<\DOMElement> the elements with a class that the selector list selects, if it is made of single class
     *                           selectors
     */
    private function elementsWithClassSelectors(string $selectors, array $classMap): array
    {
        $elements = [];

        foreach ($this->classSelectorList($selectors) as $class) {
            $elements = [...$elements, ...$classMap[$class] ?? []];
        }

        return $elements;
    }

    /**
     * @param list<array<int, string>>         $rules    plain rules, with the selector list and the declaration block
     * @param array<string, list<\DOMElement>> $classMap the elements by class
     *
     * @return array<string, true> the properties that two rules selecting the same element declare with different
     *                             values; as attributes the later value would win even where CSS drops it as invalid
     */
    private function conflictingProperties(array $rules, array $classMap): array
    {
        $values = [];
        $conflicting = [];

        foreach ($this->declaredValues($rules, $classMap) as [$element, $property, $value]) {
            if (($values[$element][$property] ?? $value) !== $value) {
                $conflicting[$property] = true;
            }

            $values[$element][$property] = $value;
        }

        return $conflicting;
    }

    /**
     * @param list<array<int, string>>         $rules    plain rules, with the selector list and the declaration block
     * @param array<string, list<\DOMElement>> $classMap the elements by class
     *
     * @return list<array{int, string, string}> each declaration of a rule for each element the rule selects, as the
     *                                          object id of the element, the lowercase property and the value
     */
    private function declaredValues(array $rules, array $classMap): array
    {
        $declared = [];

        foreach ($rules as $rule) {
            $declarations = $this->parseDeclarations($rule[2]);

            foreach ($this->elementsWithClassSelectors($rule[1], $classMap) as $element) {
                foreach ($declarations as [$property, $value]) {
                    $property = $this->asciiLowercase($property);
                    $declared[] = [spl_object_id($element), $property, $value];
                }
            }
        }

        return $declared;
    }

    /**
     * @param string $css   the style sheet to search
     * @param string $class a class name consisting of letters, digits, `_` and `-` only
     *
     * @return bool true if the style sheet may match the class, through a class selector or an attribute selector on
     *              `class`, such as `[class="a"]`, which matches the whole class list, or through an escape, such as
     *              `.\61`
     */
    private function cssMatchesClass(string $css, string $class): bool
    {
        return str_contains($css, self::CSS_ESCAPE)
            || 1 === preg_match(self::CLASS_ATTRIBUTE_SELECTOR_REGEX, $css)
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
            $domElement->setAttribute(Attribute::Class_->value, implode(' ', $classes));
        } else {
            $domElement->removeAttribute(Attribute::Class_->value);
        }
    }
}
