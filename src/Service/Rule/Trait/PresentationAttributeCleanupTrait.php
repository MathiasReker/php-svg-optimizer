<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait PresentationAttributeCleanupTrait
{
    use ElementTrait;

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return list<\DOMElement> the SVG elements outside foreign content, in document order
     */
    private function svgElements(\DOMDocument $domDocument): array
    {
        $elements = [];
        $foreignObjectScope = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $parent = $domNodeList->parentNode;
            $inForeignObject = $parent instanceof \DOMElement && \array_key_exists(spl_object_id($parent), $foreignObjectScope);

            if ($inForeignObject || SvgTag::ForeignObject->value === $domNodeList->localName) {
                $foreignObjectScope[spl_object_id($domNodeList)] = $domNodeList;
            }

            if (!$inForeignObject && $this->isSvgElement($domNodeList)) {
                $elements[] = $domNodeList;
            }
        }

        return $elements;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if an attribute of the element, its inline style included, uses `inherit`
     */
    private function hasInheritValue(\DOMElement $domElement): bool
    {
        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if ($this->mayUseParentValue($attribute->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement         $domElement the element to clean
     * @param array<int, string>  $names      the attribute names to remove, unless the inline style sets the same
     *                                        property, which would then become an attribute in another optimization
     * @param array<string, true> $animated   the names of all attributes targeted by animation elements
     */
    private function removeUnanimatedAttributes(\DOMElement $domElement, array $names, array $animated): void
    {
        foreach ($names as $name) {
            if (!\array_key_exists($name, $animated) && !$this->styleSetsProperty($domElement, $name)) {
                $domElement->removeAttribute($name);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $property   the property name
     *
     * @return bool true if the inline style of the element may set the property
     */
    private function styleSetsProperty(\DOMElement $domElement, string $property): bool
    {
        return $this->styleDeclares($domElement, $property . '|all');
    }
}
