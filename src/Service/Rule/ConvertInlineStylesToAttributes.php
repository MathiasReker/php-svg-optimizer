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
final readonly class ConvertInlineStylesToAttributes implements SvgOptimizerRuleInterface
{
    use SvgPropertiesTrait;

    /**
     * Regular expression pattern for validating SVG/CSS property names.
     *
     * - Must start with a letter or underscore or hyphen.
     * - May contain letters, numbers, underscores, or hyphens.
     *
     * @see https://regex101.com/r/Wlb5xS/1
     */
    private const string PROPERTY_NAME_REGEX = '/^[a-z_-][a-z0-9_-]*$/i';

    /**
     * Optimizes the given \DOMDocument by converting inline styles to SVG attributes.
     *
     * @param \DOMDocument $domDocument the DOM document containing SVG markup
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        foreach ($this->getElementsWithStyle($domDocument) as $domElement) {
            $this->convertStyles($domElement);
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Returns a list of DOM elements that have a `style` attribute.
     *
     * @return list<\DOMElement> list of DOM elements with a `style` attribute
     */
    private function getElementsWithStyle(\DOMDocument $domDocument): array
    {
        $domXPath = new \DOMXPath($domDocument);
        $nodes = $domXPath->query('//*[@style]');
        if (false === $nodes) {
            return [];
        }

        $elements = [];
        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    /**
     * Converts inline CSS styles of a DOM element to SVG attributes, removing any
     * properties that were converted from the `style` attribute.
     *
     * @param \DOMElement $domElement the DOM element to process
     */
    private function convertStyles(\DOMElement $domElement): void
    {
        $style = trim($domElement->getAttribute('style'));
        if ('' === $style || !str_contains($style, ':')) {
            return;
        }

        $remaining = [];
        foreach (explode(';', $style) as $declaration) {
            $remainingEntry = $this->processDeclaration($declaration, $domElement);
            if ('' !== $remainingEntry) {
                $remaining[] = $remainingEntry;
            }
        }

        if ([] === $remaining) {
            $domElement->removeAttribute('style');
        } else {
            $domElement->setAttribute('style', implode('; ', $remaining));
        }
    }

    /**
     * Processes a single CSS declaration.
     * Converts the property to an SVG attribute if possible, otherwise returns
     * the declaration to keep in the style attribute.
     *
     * @param string      $declaration The CSS declaration (e.g., "fill:red").
     * @param \DOMElement $domElement  the DOM element being processed
     *
     * @return string the declaration to keep in the style attribute, or empty string if converted
     */
    private function processDeclaration(string $declaration, \DOMElement $domElement): string
    {
        $declaration = trim($declaration);
        if ('' === $declaration || !str_contains($declaration, ':')) {
            return '';
        }

        [$prop, $value] = array_map(trim(...), explode(':', $declaration, 2));
        if ('' === $prop || '' === $value) {
            return '';
        }

        $prop = mb_strtolower($prop);

        if (!$this->isValidPropertyName($prop)) {
            return \sprintf('%s:%s', $prop, $value);
        }

        if (\in_array($prop, self::SVG_PROPERTIES, true)) {
            if (!$domElement->hasAttribute($prop)) {
                $domElement->setAttribute($prop, $value);
            }

            return '';
        }

        return \sprintf('%s:%s', $prop, $value);
    }

    /**
     * Validates a CSS property name.
     *
     * @param string $prop the property name to validate
     *
     * @return bool true if valid, false otherwise
     */
    private function isValidPropertyName(string $prop): bool
    {
        return 1 === preg_match(self::PROPERTY_NAME_REGEX, $prop);
    }
}
