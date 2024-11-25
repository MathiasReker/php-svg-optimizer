<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;

final class FlattenGroups implements SvgOptimizerRuleInterface
{
    /**
     * Flatten groups in the SVG document by applying their attributes to child elements
     * and removing the group elements.
     *
     * This method processes each `<svg:g>` element in the SVG document:
     * - Applies the group's attributes to its child elements if they do not already have them.
     * - Moves the child elements up to the group's parent and removes the group element.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        /**
         * @var \DOMNodeList<\DOMElement> $groups
         */
        $groups = $domXPath->query('//svg:g');

        foreach ($groups as $group) {
            if ($group->hasAttributes()) {
                $this->applyGroupAttributesToChildren($group);
            }

            $this->flattenGroup($group);
        }
    }

    /**
     * Apply the attributes of a group element to its child elements.
     *
     * This method iterates over each child of the group and sets attributes from the group
     * to the child elements, but only if the child does not already have those attributes.
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
     * Apply the attributes of a parent element to a child element.
     *
     * This method iterates over the attributes of the parent element and sets them on the child element,
     * but only if the child does not already have that attribute.
     *
     * @param \DOMElement $parent The parent element whose attributes will be applied to the child
     * @param \DOMElement $child  The child element to which the parent's attributes will be applied
     */
    private function applyAttributesToChild(\DOMElement $parent, \DOMElement $child): void
    {
        /**
         * @var \DOMAttr $attribute
         */
        foreach ($parent->attributes as $attribute) {
            $this->setAttributeIfNotExists($child, $attribute);
        }
    }

    /**
     * Set an attribute on an element if it does not already exist.
     *
     * This method sets an attribute on an element if the element does not already have that attribute.
     *
     * @param \DOMElement $domElement The element to which the attribute will be applied
     * @param \DOMAttr    $domAttr    The attribute to be applied to the element
     */
    private function setAttributeIfNotExists(\DOMElement $domElement, \DOMAttr $domAttr): void
    {
        if (!$domElement->hasAttribute($domAttr->nodeName) && \is_string($domAttr->nodeValue)) {
            $domElement->setAttribute($domAttr->nodeName, $domAttr->nodeValue);
        }
    }

    /**
     * Flatten a group element by moving its child elements up and removing the group.
     *
     * This method moves each child of the group element to the group's parent element
     * and then removes the group element itself from the DOM.
     *
     * @param \DOMElement $domElement The group element to be flattened
     */
    private function flattenGroup(\DOMElement $domElement): void
    {
        $parentNode = $domElement->parentNode;

        if ($parentNode instanceof \DOMElement) {
            $children = iterator_to_array($domElement->childNodes, false);

            foreach ($children as $child) {
                $parentNode->insertBefore($child, $domElement);
            }

            $parentNode->removeChild($domElement);
        }
    }
}
