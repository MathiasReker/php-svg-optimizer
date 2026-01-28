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

    /**
     * Optimize the SVG by removing empty <g> elements recursively.
     *
     * @param \DOMDocument $domDocument the SVG DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($domDocument->documentElement instanceof \DOMElement) {
            $this->removeEmptyGroupsRecursive($domDocument->documentElement);
        }
    }

    /**
     * Recursively remove empty <g> elements from the DOM.
     *
     * Traverses the DOM tree in reverse order to safely remove nodes while iterating.
     *
     * @param \DOMNode $domNode the node to traverse
     */
    private function removeEmptyGroupsRecursive(\DOMNode $domNode): void
    {
        if (!$domNode->hasChildNodes()) {
            return;
        }

        for ($i = $domNode->childNodes->length - 1; $i >= 0; --$i) {
            $child = $domNode->childNodes->item($i);
            if (null === $child) {
                continue;
            }

            if (!$child instanceof \DOMElement) {
                $this->removeEmptyGroupsRecursive($child);
                continue;
            }

            if (SvgTag::G->value === $child->tagName) {
                $this->removeEmptyGroupsRecursive($child);
                $this->removeGroupIfEmpty($child);
            } else {
                $this->removeEmptyGroupsRecursive($child);
            }
        }
    }

    /**
     * Remove a group element if it is empty.
     *
     * @param \DOMElement $domElement the <g> element to check and possibly remove
     */
    private function removeGroupIfEmpty(\DOMElement $domElement): void
    {
        if ($this->isEmptyGroup($domElement)) {
            $domElement->parentNode?->removeChild($domElement);
        }
    }

    /**
     * Determines if a <g> element is empty.
     *
     * An empty group has no attributes and contains only empty text nodes
     * or whitespace. Comments do not prevent removal.
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
            if ($child instanceof \DOMElement
                || ($child instanceof \DOMText && '' !== trim($child->wholeText))
                || $child instanceof \DOMComment) {
                return false;
            }
        }

        return true;
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return true;
    }
}
