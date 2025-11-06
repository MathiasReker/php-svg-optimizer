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
final readonly class FlattenGroups implements SvgOptimizerRuleInterface
{
    /**
     * Optimize the SVG document by flattening groups.
     *
     * This method processes all group elements in the SVG document, applying their attributes
     * to their child elements and removing the group elements. It also combines transforms
     * from the group and its children.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        /** @var \DOMNodeList<\DOMElement> $groups */
        $groups = $domXPath->query('//svg:g');

        foreach ($groups as $group) {
            $this->applyGroupAttributesToChildren($group);
            $this->flattenGroup($group);
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return true;
    }

    /**
     * Apply attributes from the group to its child elements.
     *
     * @param \DOMElement $domElement The group element whose attributes will be applied to its children
     */
    private function applyGroupAttributesToChildren(\DOMElement $domElement): void
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->applyAttributesToChild($domElement, $child);
            }
        }
    }

    /**
     * Apply attributes from the parent group to the child element.
     *
     * @param \DOMElement $parent The parent group element
     * @param \DOMElement $child  The child element to which attributes will be applied
     */
    private function applyAttributesToChild(\DOMElement $parent, \DOMElement $child): void
    {
        /** @var \DOMAttr $attribute */
        foreach ($parent->attributes ?? [] as $attribute) {
            $this->setAttributeIfNotExists($child, $attribute);
        }
    }

    /**
     * Set an attribute on the child element if it does not already exist.
     *
     * @param \DOMElement $domElement The child element to set the attribute on
     * @param \DOMAttr    $domAttr    The attribute to set
     */
    private function setAttributeIfNotExists(\DOMElement $domElement, \DOMAttr $domAttr): void
    {
        if (!$domElement->hasAttribute($domAttr->nodeName) && \is_string($domAttr->nodeValue)) {
            $domElement->setAttribute($domAttr->nodeName, $domAttr->nodeValue);
        }
    }

    /**
     * Flatten the group by moving its children to the parent node and removing the group.
     *
     * @param \DOMElement $domElement The group element to be flattened
     */
    private function flattenGroup(\DOMElement $domElement): void
    {
        $parentNode = $domElement->parentNode;

        if ($parentNode instanceof \DOMElement) {
            $transform = $domElement->getAttribute('transform');

            $this->applyTransformsToChildren($domElement, $transform);
            $this->moveChildrenUp($domElement, $parentNode);
            $parentNode->removeChild($domElement);
        }
    }

    /**
     * Apply the combined transform from the group to each child element.
     */
    private function applyTransformsToChildren(\DOMElement $domElement, string $transform): void
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $childTransform = $child->getAttribute('transform');
                $newTransform = $this->combineTransforms($transform, $childTransform);

                if ('' !== $newTransform) {
                    $child->setAttribute('transform', $newTransform);
                }
            }
        }
    }

    /**
     * Combine two transform strings, returning the concatenated result only if necessary.
     */
    private function combineTransforms(string $transform1, string $transform2): string
    {
        if ($transform1 === $transform2) {
            return $transform1;
        }

        return \sprintf('%s %s', $transform1, $transform2);
    }

    /**
     * Move all children of the group up to the parent node.
     */
    private function moveChildrenUp(\DOMElement $domElement, \DOMElement $parentNode): void
    {
        $children = iterator_to_array($domElement->childNodes, false);
        foreach ($children as $child) {
            $parentNode->insertBefore($child, $domElement);
        }
    }
}
