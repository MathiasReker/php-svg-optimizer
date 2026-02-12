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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;

/**
 * @no-named-arguments
 */
final readonly class FlattenGroups implements SvgOptimizerRuleInterface
{
    /**
     * XPath query to select all group elements.
     */
    private const string XPATH_GROUP_ELEMENTS = '//svg:g';

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
     * Flattens nested SVG group elements (`<g>`).
     *
     * This optimization rule merges the attributes of a group into its child
     * elements and then removes the group, resulting in a flatter and more
     * compact SVG structure.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Svg->prefix(), SvgNamespace::Svg->value);

        /** @var \DOMNodeList<\DOMElement> $domElementList */
        $domElementList = $domXPath->query(self::XPATH_GROUP_ELEMENTS);

        foreach ($domElementList as $domElement) {
            $this->applyGroupAttributesToChildren($domElement);
            $this->flattenGroup($domElement);
        }
    }

    /**
     * Applies the attributes of a group element to its direct children.
     *
     * This method iterates through the children of the given group element and
     * applies the group's attributes to each child that is a `\DOMElement`.
     *
     * @param \DOMElement $domElement the group element
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
     * Applies the attributes of a parent element to a child element.
     *
     * This method iterates through the attributes of the parent element and
     * applies each one to the child element.
     *
     * @param \DOMElement $parent the parent element
     * @param \DOMElement $child  the child element
     */
    private function applyAttributesToChild(\DOMElement $parent, \DOMElement $child): void
    {
        /** @var \DOMAttr $attribute */
        foreach ($parent->attributes as $attribute) {
            $this->setAttributeIfNotExists($child, $attribute);
        }
    }

    /**
     * Sets an attribute on a DOM element if it is not already present.
     *
     * @param \DOMElement $domElement the element to modify
     * @param \DOMAttr    $domAttr    the attribute to set
     */
    private function setAttributeIfNotExists(\DOMElement $domElement, \DOMAttr $domAttr): void
    {
        if (!$domElement->hasAttribute($domAttr->nodeName) && \is_string($domAttr->nodeValue)) {
            $domElement->setAttribute($domAttr->nodeName, $domAttr->nodeValue);
        }
    }

    /**
     * Flattens a group element by moving its children to its parent.
     *
     * This method also applies the group's `transform` attribute to its children
     * before removing the group.
     *
     * @param \DOMElement $domElement the group element to flatten
     */
    private function flattenGroup(\DOMElement $domElement): void
    {
        $parentNode = $domElement->parentNode;

        if ($parentNode instanceof \DOMElement) {
            $transform = $domElement->getAttribute(SvgAttribute::Transform->value);

            $this->applyTransformsToChildren($domElement, $transform);
            $this->moveChildrenUp($domElement, $parentNode);
            $parentNode->removeChild($domElement);
        }
    }

    /**
     * Applies the transform of a group to its children.
     *
     * This method combines the group's transform with the transform of each
     * child element.
     *
     * @param \DOMElement $domElement the group element
     * @param string      $transform  the transform to apply
     */
    private function applyTransformsToChildren(\DOMElement $domElement, string $transform): void
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $childTransform = $child->getAttribute(SvgAttribute::Transform->value);
                $newTransform = $this->combineTransforms($transform, $childTransform);

                if ('' !== $newTransform) {
                    $child->setAttribute(SvgAttribute::Transform->value, $newTransform);
                }
            }
        }
    }

    /**
     * Combines two transform strings.
     *
     * If the transforms are identical, it returns the original transform.
     * Otherwise, it concatenates them.
     *
     * @param string $transform1 the first transform
     * @param string $transform2 the second transform
     *
     * @return string the combined transform
     */
    private function combineTransforms(string $transform1, string $transform2): string
    {
        if ($transform1 === $transform2) {
            return $transform1;
        }

        return \sprintf('%s %s', $transform1, $transform2);
    }

    /**
     * Moves the children of a DOM element to its parent.
     *
     * This method inserts each child before the original element in the parent's
     * child list.
     *
     * @param \DOMElement $domElement the element whose children to move
     * @param \DOMElement $parentNode the parent element
     */
    private function moveChildrenUp(\DOMElement $domElement, \DOMElement $parentNode): void
    {
        $children = iterator_to_array($domElement->childNodes, false);
        foreach ($children as $child) {
            $parentNode->insertBefore($child, $domElement);
        }
    }
}
