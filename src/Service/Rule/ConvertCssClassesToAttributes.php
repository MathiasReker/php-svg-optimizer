<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @no-named-arguments
 */
final readonly class ConvertCssClassesToAttributes implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern to extract class selectors and their declarations.
     *
     * @see https://regex101.com/r/qOS1io/1
     */
    private const string CLASS_SELECTOR_REGEX = '/\.([a-zA-Z0-9_-]+)\s*\{([^}]+)}/';

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Converts CSS style declarations into inline attributes on SVG elements.
     *
     * This method finds `<style>` blocks, parses class selectors, and applies
     * the corresponding CSS properties as attributes to the elements that use
     * those classes. This can make the SVG more self-contained and can enable
     * further optimizations.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $classMap = $this->buildClassElementMap($domDocument);

        $convertibleLookup = $this->getConvertibleProperties();

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domDocument->getElementsByTagName(SvgTag::Style->value);

        foreach (iterator_to_array($domNodeList, false) as $domElement) {
            $css = $domElement->textContent ?? '';

            if ('' === $css) {
                $domElement->parentNode?->removeChild($domElement);
                continue;
            }

            $remainingCss = $this->processCss($css, $classMap, $convertibleLookup);

            if ('' === $remainingCss) {
                $domElement->parentNode?->removeChild($domElement);
            } else {
                $domElement->textContent = $remainingCss;
            }
        }
    }

    /**
     * Creates a map of class names to the elements that use them.
     *
     * This is an efficient way to look up all elements associated with a
     * particular class without repeatedly querying the DOM.
     *
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return array<string, list<\DOMElement>> a map of class names to element arrays
     */
    private function buildClassElementMap(\DOMDocument $domDocument): array
    {
        $map = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $classes = explode(' ', $domNodeList->getAttribute(SvgAttribute::Class_->value));
            foreach ($classes as $class) {
                $class = trim($class);
                if ('' === $class) {
                    continue;
                }

                $map[$class][] = $domNodeList;
            }
        }

        return $map;
    }

    /**
     * Gets a lookup array of CSS properties that can be converted to attributes.
     *
     * @return array<string, int> a map for quick property lookup
     */
    private function getConvertibleProperties(): array
    {
        /** @var array<string, int>|null $lookup */
        static $lookup = null;

        if (null === $lookup) {
            $lookup = array_flip(SvgInlineStyleProperty::values());
        }

        return $lookup;
    }

    /**
     * Parses a CSS string, applies convertible styles to elements, and returns the remaining CSS.
     *
     * @param string                           $css               the CSS content from a `<style>` block
     * @param array<string, list<\DOMElement>> $classMap          the class-to-element map
     * @param array<string, int>               $convertibleLookup the lookup map for convertible properties
     *
     * @return string the CSS that could not be converted to attributes
     */
    private function processCss(string $css, array $classMap, array $convertibleLookup): string
    {
        preg_match_all(self::CLASS_SELECTOR_REGEX, $css, $matches, \PREG_SET_ORDER);
        $remainingCss = [];

        foreach ($matches as $match) {
            $class = trim($match[1]);
            $declarations = trim($match[2]);

            [$convertible, $nonConvertible] = $this->splitDeclarations($declarations, $convertibleLookup);

            if (\array_key_exists($class, $classMap)) {
                foreach ($classMap[$class] as $element) {
                    foreach ($convertible as $prop => $value) {
                        $element->setAttribute($prop, $value);
                    }

                    $this->updateElementClass($element, $class, $nonConvertible);
                }
            }

            if ([] !== $nonConvertible) {
                $remainingCss[] = $this->rebuildCssRule($class, $nonConvertible);
            }
        }

        return implode('', $remainingCss);
    }

    /**
     * Splits a string of CSS declarations into two groups: those that can be
     * converted to attributes and those that cannot.
     *
     * @param string             $declarations      The CSS declaration block (e.g., "fill:red; font-size:12px").
     * @param array<string, int> $convertibleLookup the lookup map for convertible properties
     *
     * @return array{array<string, string>, array<string, string>} a tuple containing convertible and non-convertible declarations
     */
    private function splitDeclarations(string $declarations, array $convertibleLookup): array
    {
        $convertible = [];
        $nonConvertible = [];

        foreach (explode(';', $declarations) as $decl) {
            $decl = trim($decl);
            if ('' === $decl) {
                continue;
            }

            if (!str_contains($decl, ':')) {
                continue;
            }

            [$prop, $value] = array_map(trim(...), explode(':', $decl, 2));
            $propLower = mb_strtolower($prop);

            if (\array_key_exists($propLower, $convertibleLookup)) {
                $convertible[$propLower] = $value;
            } else {
                $nonConvertible[$propLower] = $value;
            }
        }

        return [$convertible, $nonConvertible];
    }

    /**
     * Updates an element's `class` attribute after its styles have been processed.
     *
     * If all declarations for a class were converted, the class is removed from
     * the element. If some non-convertible declarations remain, the class is kept.
     *
     * @param \DOMElement           $domElement     the element to update
     * @param string                $class          the class that was processed
     * @param array<string, string> $nonConvertible the remaining non-convertible declarations
     */
    private function updateElementClass(\DOMElement $domElement, string $class, array $nonConvertible): void
    {
        if ([] !== $nonConvertible) {
            $domElement->setAttribute(SvgAttribute::Class_->value, $class);

            return;
        }

        $classes = explode(' ', $domElement->getAttribute(SvgAttribute::Class_->value));
        $classes = array_filter($classes, static fn (string $c): bool => $c !== $class);

        if ([] !== $classes) {
            $domElement->setAttribute(SvgAttribute::Class_->value, implode(' ', $classes));
        } else {
            $domElement->removeAttribute(SvgAttribute::Class_->value);
        }
    }

    /**
     * Reconstructs a CSS rule string from a class name and its non-convertible declarations.
     *
     * @param string                $class          the class name
     * @param array<string, string> $nonConvertible the map of non-convertible properties and values
     *
     * @return string the reconstructed CSS rule
     */
    private function rebuildCssRule(string $class, array $nonConvertible): string
    {
        $props = [];
        foreach ($nonConvertible as $prop => $value) {
            $props[] = \sprintf('%s:%s', $prop, $value);
        }

        return \sprintf('.%s{', $class) . implode(';', $props) . '}';
    }
}
