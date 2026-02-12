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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @no-named-arguments
 */
final readonly class RemoveEmptyTextElements implements SvgOptimizerRuleInterface
{
    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return true;
    }

    /**
     * Removes empty text-related elements from the SVG document.
     *
     * This method recursively traverses the DOM and removes `<text>` and `<tspan>`
     * elements that contain no meaningful content, as well as `<tref>` elements
     * with an empty `xlink:href` attribute.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $root = $domDocument->documentElement;
        if ($root instanceof \DOMElement) {
            $this->removeEmptyTextRecursive($root);
        }
    }

    /**
     * Recursively traverses a DOM element and removes empty text-related elements.
     *
     * @param \DOMElement $domElement the element to process
     */
    private function removeEmptyTextRecursive(\DOMElement $domElement): void
    {
        foreach (array_reverse(iterator_to_array($domElement->childNodes, true)) as $domNode) {
            if (!$domNode instanceof \DOMElement) {
                continue;
            }

            $this->removeEmptyTextRecursive($domNode);
            $this->removeIfEmpty($domNode);
        }
    }

    /**
     * Removes a given element if it is an empty text-related element.
     *
     * @param \DOMElement $domElement the element to check and potentially remove
     */
    private function removeIfEmpty(\DOMElement $domElement): void
    {
        $tag = $domElement->tagName;

        if (($tag === SvgTag::Text->value || $tag === SvgTag::Tspan->value) && $this->isEmptyElement($domElement)) {
            $domElement->parentNode?->removeChild($domElement);

            return;
        }

        if ($tag === SvgTag::Tref->value && '' === $domElement->getAttribute(SvgAttribute::XlinkHref->value)) {
            $domElement->parentNode?->removeChild($domElement);
        }
    }

    /**
     * Determines if an element is effectively empty.
     *
     * An element is considered empty if it has no child elements and no
     * non-whitespace text content.
     *
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is empty, false otherwise
     */
    private function isEmptyElement(\DOMElement $domElement): bool
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                return false;
            }

            if ($child instanceof \DOMText && '' !== trim($child->wholeText)) {
                return false;
            }
        }

        return true;
    }
}
