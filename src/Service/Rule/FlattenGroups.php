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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @no-named-arguments
 */
final readonly class FlattenGroups implements SvgOptimizerRuleInterface
{
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
            if ($domElement->parentNode?->nodeName === SvgTag::Defs->value && $domElement->hasAttribute(SvgAttribute::Id->value)) {
                continue;
            }

            if ($domElement->hasAttribute(SvgAttribute::ClipPath->value)) {
                continue;
            }

            if ($domElement->hasAttribute(SvgAttribute::Mask->value)) {
                continue;
            }

            $this->applyGroupAttributesToChildren($domElement);
            $this->flattenGroup($domElement);
        }
    }

    /**
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
