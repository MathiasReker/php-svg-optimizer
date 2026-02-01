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

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query('//*');

        $seen = [];

        foreach ($domNodeList as $domElement) {
            $this->normalizeAttributes($domElement);

            $parent = $domElement->parentNode;
            if (!$parent instanceof \DOMElement) {
                continue;
            }

            $key = spl_object_hash($parent) . '::' . $this->buildSignature($domElement);
            if (\array_key_exists($key, $seen)) {
                $parent->removeChild($domElement);
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
        foreach ($domElement->attributes as $attribute) {
            $trimmedValue = trim((string) $attribute->nodeValue);
            if ($trimmedValue !== $attribute->nodeValue) {
                $domElement->setAttribute($attribute->nodeName, $trimmedValue);
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
        foreach ($domElement->attributes as $attribute) {
            $attrs[$attribute->nodeName] = trim((string) $attribute->nodeValue);
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
