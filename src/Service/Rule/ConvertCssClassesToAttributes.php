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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgPropertiesTrait;

/**
 * @no-named-arguments
 */
final readonly class ConvertCssClassesToAttributes implements SvgOptimizerRuleInterface
{
    use SvgPropertiesTrait;

    /**
     * Regex pattern to match CSS class selectors and their declaration blocks.
     *
     * This pattern is used to extract class-based style rules
     * from inline <style> blocks in SVG documents.
     *
     * @see https://regex101.com/r/ZEpxvm/1
     */
    private const string CLASS_SELECTOR_PATTERN = '/\.([a-zA-Z0-9_-]+)\s*\{([^}]+)}/';

    /**
     * Optimizes the given \DOMDocument by converting CSS classes to inline SVG attributes.
     *
     * @param \DOMDocument $domDocument the DOM document containing SVG markup
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domNodeList = $domDocument->getElementsByTagName('style');

        foreach (iterator_to_array($domNodeList, false) as $domElement) {
            $css = $domElement->textContent;

            if ('' === $css) {
                $domElement->parentNode?->removeChild($domElement);
                continue;
            }

            $newCss = $this->processCss($css, $domXPath);

            if ('' !== $newCss) {
                $domElement->textContent = $newCss;
            } else {
                $domElement->parentNode?->removeChild($domElement);
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Processes CSS text and converts matching rules to SVG attributes.
     *
     * @param string    $css      the CSS text from a <style> element
     * @param \DOMXPath $domXPath the \DOMXPath instance for querying elements
     *
     * @return string the remaining CSS rules that could not be converted
     */
    private function processCss(string $css, \DOMXPath $domXPath): string
    {
        preg_match_all(self::CLASS_SELECTOR_PATTERN, $css, $matches, \PREG_SET_ORDER);
        $resultCss = [];

        foreach ($matches as $match) {
            $class = trim($match[1]);
            $declarations = trim($match[2]);

            [$convertible, $nonConvertible] = $this->splitDeclarations($declarations);

            if (\count($convertible) > 0) {
                $this->applyAttributesToElements($class, $convertible, $nonConvertible, $domXPath);
            }

            if (\count($nonConvertible) > 0) {
                $resultCss[] = $this->rebuildCssRule($class, $nonConvertible);
            }
        }

        return implode('', $resultCss);
    }

    /**
     * Splits CSS declarations into those that can be converted to SVG attributes and those that cannot.
     *
     * @param string $declarations CSS declarations string (e.g., "fill:red; stroke:blue;").
     *
     * @return array{0: array<string, string>, 1: array<string, string>} Tuple containing:
     *                                                                   - array<string,string> Convertible declarations
     *                                                                   - array<string,string> Non-convertible declarations
     */
    private function splitDeclarations(string $declarations): array
    {
        $convertible = [];
        $nonConvertible = [];

        foreach (explode(';', $declarations) as $declaration) {
            $declaration = trim($declaration);
            if ('' === $declaration) {
                continue;
            }

            if (!str_contains($declaration, ':')) {
                continue;
            }

            [$prop, $value] = array_map(trim(...), explode(':', $declaration, 2));
            $propLower = mb_strtolower($prop);

            if (\in_array($propLower, self::SVG_PROPERTIES, true)) {
                $convertible[$propLower] = $value;
            } else {
                $nonConvertible[$propLower] = $value;
            }
        }

        return [$convertible, $nonConvertible];
    }

    /**
     * Applies SVG attributes to elements matching a given CSS class.
     *
     * @param string                $class          the CSS class to match
     * @param array<string, string> $convertible    attributes to apply
     * @param array<string, string> $nonConvertible attributes that cannot be converted
     * @param \DOMXPath             $domXPath       \DOMXPath instance for querying elements
     */
    private function applyAttributesToElements(
        string $class,
        array $convertible,
        array $nonConvertible,
        \DOMXPath $domXPath,
    ): void {
        $elements = $domXPath->query(
            \sprintf(
                "//*[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
                $class
            )
        );

        if (!($elements instanceof \DOMNodeList)) {
            return;
        }

        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            foreach ($convertible as $prop => $value) {
                $element->setAttribute($prop, $value);
            }

            $this->updateElementClass($element, $class, $nonConvertible);
        }
    }

    /**
     * Updates the class attribute of an element after converting some styles.
     *
     * @param \DOMElement           $domElement     the element to update
     * @param string                $class          the CSS class being processed
     * @param array<string, string> $nonConvertible CSS declarations that were not converted
     */
    private function updateElementClass(\DOMElement $domElement, string $class, array $nonConvertible): void
    {
        if ([] !== $nonConvertible) {
            $domElement->setAttribute('class', $class);
        } else {
            $classes = explode(' ', $domElement->getAttribute('class'));
            $classes = array_filter($classes, static fn (string $c): bool => $c !== $class);

            if ([] !== $classes) {
                $domElement->setAttribute('class', implode(' ', $classes));
            } else {
                $domElement->removeAttribute('class');
            }
        }
    }

    /**
     * Rebuilds a CSS rule string from non-convertible declarations.
     *
     * @param string                $class          the CSS class
     * @param array<string, string> $nonConvertible non-convertible declarations
     *
     * @return string the rebuilt CSS rule string
     */
    private function rebuildCssRule(string $class, array $nonConvertible): string
    {
        $props = [];
        foreach ($nonConvertible as $prop => $value) {
            $props[] = \sprintf('%s:%s', $prop, $value);
        }

        return \sprintf('.%s{%s}', $class, implode(';', $props));
    }
}
