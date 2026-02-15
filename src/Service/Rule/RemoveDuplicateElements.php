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

/**
 * @no-named-arguments
 */
final readonly class RemoveDuplicateElements implements SvgOptimizerRuleInterface
{
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
     * Removes duplicate elements from the SVG document.
     *
     * An element is considered a duplicate if it has the same parent, tag name,
     * and attributes as another element that has already been processed.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeDuplicateElements($domDocument);
    }

    /**
     * Traverses the document and removes duplicate elements.
     *
     * It builds a signature for each element based on its parent, tag name, and
     * attributes. If an identical signature is encountered again, the element
     * is removed.
     *
     * @param \DOMDocument $domDocument the DOM document to process
     */
    private function removeDuplicateElements(\DOMDocument $domDocument): void
    {
        $elements = iterator_to_array($domDocument->getElementsByTagName('*'), true);

        $seen = [];

        foreach ($elements as $element) {
            $this->normalizeAttributes($element);

            $parent = $element->parentNode;
            if (!$parent instanceof \DOMElement) {
                continue;
            }

            $key = spl_object_hash($parent) . '::' . $this->buildSignature($element);

            if (\array_key_exists($key, $seen)) {
                $parent->removeChild($element);
            } else {
                $seen[$key] = true;
            }
        }
    }

    /**
     * Trims whitespace from the values of all attributes on a given element.
     *
     * This ensures that attributes with functionally identical but textually
     * different values (e.g., `class=" a "` vs. `class="a"`) are treated as
     * the same for the purpose of duplicate detection.
     *
     * @param \DOMElement $domElement the element whose attributes to normalize
     */
    private function normalizeAttributes(\DOMElement $domElement): void
    {
        foreach ($domElement->attributes as $attribute) {
            $trimmedValue = trim((string) $attribute->nodeValue);
            if ($trimmedValue !== $attribute->nodeValue) {
                $domElement->setAttribute($attribute->nodeName, $trimmedValue);
            }
        }
    }

    /**
     * Generates a unique signature for an element based on its tag name and attributes.
     *
     * The signature is created by sorting the attributes alphabetically and then
     * JSON-encoding them, which provides a consistent and comparable representation.
     *
     * @param \DOMElement $domElement the element to generate a signature for
     *
     * @return string the element's signature
     */
    private function buildSignature(\DOMElement $domElement): string
    {
        $attrs = [];
        foreach ($domElement->attributes as $attribute) {
            $attrs[$attribute->nodeName] = trim((string) $attribute->nodeValue);
        }

        ksort($attrs);

        $attrString = json_encode($attrs, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        return $domElement->tagName . '|' . $attrString;
    }
}
