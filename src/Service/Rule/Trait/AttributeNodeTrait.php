<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait AttributeNodeTrait
{
    /**
     * @param \DOMElement $domElement the element to append the attribute to; it must not hold an attribute in a
     *                                namespace with the same local name when the attribute has no namespace
     * @param \DOMAttr    $domAttr    the attribute to move, which keeps its name, namespace and value
     */
    private function appendAttributeNode(\DOMElement $domElement, \DOMAttr $domAttr): void
    {
        $domAttr->ownerElement?->removeAttributeNode($domAttr);

        if (null === $domAttr->namespaceURI) {
            $domElement->setAttributeNode($domAttr);
        } else {
            $domElement->setAttributeNodeNS($domAttr);
        }
    }

    /**
     * @param \DOMElement $domElement the element whose attributes to move
     * @param \DOMElement $target     the element without attributes to move them to, in their order unless an
     *                                attribute without a namespace has to come before one in a namespace
     */
    private function moveAttributeNodes(\DOMElement $domElement, \DOMElement $target): void
    {
        $attributes = iterator_to_array($domElement->attributes, false);

        if ($this->sharesLocalName($attributes)) {
            $attributes = [
                ...array_filter($attributes, static fn (\DOMAttr $domAttr): bool => null === $domAttr->namespaceURI),
                ...array_filter($attributes, static fn (\DOMAttr $domAttr): bool => null !== $domAttr->namespaceURI),
            ];
        }

        foreach ($attributes as $attribute) {
            $this->appendAttributeNode($target, $attribute);
        }
    }

    /**
     * @param list<\DOMAttr> $attributes the attributes of one element
     *
     * @return bool true if an attribute without a namespace shares its local name with an attribute in a namespace
     */
    private function sharesLocalName(array $attributes): bool
    {
        $withoutNamespace = [];
        $withNamespace = [];

        foreach ($attributes as $attribute) {
            if (null === $attribute->namespaceURI) {
                $withoutNamespace[(string) $attribute->localName] = $attribute;
            } else {
                $withNamespace[(string) $attribute->localName] = $attribute;
            }
        }

        return [] !== array_intersect_key($withoutNamespace, $withNamespace);
    }

    /**
     * @param \DOMAttr $domAttr the attribute to change
     * @param string   $value   the new value, taken literally
     */
    private function setAttributeNodeValue(\DOMAttr $domAttr, string $value): void
    {
        $domAttr->textContent = $value;
    }
}
