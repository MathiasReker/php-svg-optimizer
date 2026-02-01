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

    /**
     * Optimize the SVG by removing duplicate elements.
     *
     * @param \DOMDocument $domDocument the SVG DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeDuplicateElements($domDocument);
    }

    /**
     * Removes duplicate elements from the DOM.
     *
     * An element is considered a duplicate if it shares the same parent and
     * has the same tag name and attributes as another element.
     *
     * @param \DOMDocument $domDocument the \DOMDocument to process
     */
    private function removeDuplicateElements(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query('//*'); // TODO normalize elements / nodes

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
     * Trims whitespace from all attributes of an element.
     *
     * @param \DOMElement $domElement the element to normalize
     */
    private function normalizeAttributes(\DOMElement $domElement): void
    {
        foreach ($domElement->attributes as $attr) {
            $trimmedValue = trim((string) $attr->nodeValue);
            if ($trimmedValue !== $attr->nodeValue) {
                $domElement->setAttribute($attr->nodeName, $trimmedValue);
            }
        }
    }

    /**
     * Builds a deterministic signature string for a given element.
     *
     * This signature is used to detect duplicate elements.
     *
     * @param \DOMElement $domElement the element for which to generate the signature
     *
     * @return string the signature representing the element's tag name and attributes
     */
    private function buildSignature(\DOMElement $domElement): string
    {
        $attrs = [];
        foreach ($domElement->attributes as $attr) {
            $attrs[$attr->nodeName] = trim((string) $attr->nodeValue);
        }

        ksort($attrs);

        $attrString = json_encode($attrs, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        return $domElement->tagName . '|' . $attrString;
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
