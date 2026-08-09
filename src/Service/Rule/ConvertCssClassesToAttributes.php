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
     * @see https://regex101.com/r/qOS1io/1
     */
    private const string CSS_RULE_REGEX = '/([^{}]+)\{([^}]+)}/';

    /**
     * @see https://regex101.com/r/qOS1io/2
     */
    private const string SIMPLE_CLASS_SELECTOR_REGEX = '/^\.([a-zA-Z0-9_-]+)$/';

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
     * @param string                           $css               the CSS content from a `<style>` block
     * @param array<string, list<\DOMElement>> $classMap          the class-to-element map
     * @param array<string, int>               $convertibleLookup the lookup map for convertible properties
     *
     * @return string the CSS that could not be converted to attributes
     */
    private function processCss(string $css, array $classMap, array $convertibleLookup): string
    {
        preg_match_all(self::CSS_RULE_REGEX, $css, $matches, \PREG_SET_ORDER);
        $remainingCss = [];

        foreach ($matches as $match) {
            $declarations = trim($match[2]);
            $selectors = array_map(trim(...), explode(',', trim($match[1])));

            $unconvertibleSelectors = [];

            foreach ($selectors as $selector) {
                if (1 !== preg_match(self::SIMPLE_CLASS_SELECTOR_REGEX, $selector, $selectorMatch)) {
                    $unconvertibleSelectors[] = $selector;

                    continue;
                }

                $class = $selectorMatch[1];

                [$convertible, $nonConvertible] = $this->splitDeclarations($declarations, $convertibleLookup);

                $this->applyStylesToElements($class, $classMap, $convertible, $nonConvertible);

                if ([] !== $nonConvertible) {
                    $remainingCss[] = $this->rebuildCssRule($class, $nonConvertible);
                }
            }

            if ([] !== $unconvertibleSelectors) {
                $remainingCss[] = \sprintf('%s{%s}', implode(',', $unconvertibleSelectors), $declarations);
            }
        }

        return implode('', $remainingCss);
    }

    /**
     * @param array<string, list<\DOMElement>> $classMap
     * @param array<string, string>            $convertible
     * @param array<string, string>            $nonConvertible
     */
    private function applyStylesToElements(string $class, array $classMap, array $convertible, array $nonConvertible): void
    {
        if (!\array_key_exists($class, $classMap)) {
            return;
        }

        foreach ($classMap[$class] as $element) {
            foreach ($convertible as $prop => $value) {
                $element->setAttribute($prop, $value);
            }

            $this->updateElementClass($element, $class, $nonConvertible);
        }
    }

    /**
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
