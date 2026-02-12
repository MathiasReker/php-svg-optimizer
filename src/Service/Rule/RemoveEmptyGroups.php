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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @no-named-arguments
 */
final readonly class RemoveEmptyGroups implements SvgOptimizerRuleInterface
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
     * Removes empty group (`<g>`) elements from the SVG document.
     *
     * This method recursively traverses the DOM and removes any `<g>` element
     * that has no attributes and contains no meaningful content (i.e., no
     * child elements, non-whitespace text, or comments).
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($domDocument->documentElement instanceof \DOMElement) {
            $this->removeEmptyGroupsRecursive($domDocument->documentElement);
        }
    }

    /**
     * Recursively traverses a DOM element and removes empty group elements.
     *
     * @param \DOMElement $domElement the element to process
     */
    private function removeEmptyGroupsRecursive(\DOMElement $domElement): void
    {
        $children = iterator_to_array($domElement->childNodes, true);

        foreach ($children as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            $this->removeEmptyGroupsRecursive($child);

            if ($child->tagName === SvgTag::G->value && $this->isEmptyGroup($child)) {
                $child->parentNode?->removeChild($child);
            }
        }
    }

    /**
     * Determines if a group element is effectively empty.
     *
     * A group is considered empty if it has no attributes and no child nodes
     * that are elements, non-whitespace text, or comments.
     *
     * @param \DOMElement $domElement the group element to check
     *
     * @return bool true if the group is empty, false otherwise
     */
    private function isEmptyGroup(\DOMElement $domElement): bool
    {
        if ($domElement->attributes->length > 0) {
            return false;
        }

        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                return false;
            }

            if ($child instanceof \DOMText && '' !== trim($child->wholeText)) {
                return false;
            }

            if ($child instanceof \DOMComment) {
                return false;
            }
        }

        return true;
    }
}
