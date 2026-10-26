<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom;

use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ElementTrait
{
    /**
     * @param \DOMDocument $domDocument the document to search
     * @param string       $localName   the element name to look for
     *
     * @return list<\DOMElement> the matching elements in document order
     */
    private function elementsByLocalName(\DOMDocument $domDocument, string $localName): array
    {
        $elements = [];

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            if ($localName === $domElement->localName) {
                $elements[] = $domElement;
            }
        }

        return $elements;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasElementChildren(\DOMElement $domElement): bool
    {
        return $domElement->firstElementChild instanceof \DOMElement;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element has no child elements and no non-whitespace text
     */
    private function isEmptyElement(\DOMElement $domElement): bool
    {
        return !$this->hasElementChildren($domElement) && '' === trim($domElement->textContent);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is outside the SVG namespace or inside a `<foreignObject>`
     */
    private function isForeignContent(\DOMElement $domElement): bool
    {
        if (!$this->isSvgElement($domElement)) {
            return true;
        }

        for ($node = $domElement->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
            if (Element::ForeignObject->value === $node->localName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is in the SVG namespace, or in no namespace in a document whose root element has
     *              no namespace either
     */
    private function isSvgElement(\DOMElement $domElement): bool
    {
        return NamespaceUri::Svg->value === $domElement->namespaceURI
            || (null === $domElement->namespaceURI && null === $domElement->ownerDocument?->documentElement?->namespaceURI);
    }

    /**
     * @param list<\DOMElement> $elements the elements with a class
     */
    private function hasNonSvgElement(array $elements): bool
    {
        foreach ($elements as $element) {
            if (!$this->isSvgElement($element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument the document to search
     * @param string       $tagName     the qualified name of the elements, or `*` for every element
     *
     * @return list<\DOMElement> the matching elements in document order, unaffected by later changes to the document
     */
    private function elementsByQualifiedName(\DOMDocument $domDocument, string $tagName): array
    {
        $elements = [];

        foreach ($domDocument->getElementsByTagName($tagName) as $domElement) {
            $elements[] = $domElement;
        }

        return $elements;
    }

    /**
     * @param \DOMElement $domElement the parent element
     *
     * @return list<\DOMElement> the child elements
     */
    private function childElements(\DOMElement $domElement): array
    {
        $children = [];

        for ($child = $domElement->firstElementChild; $child instanceof \DOMElement; $child = $child->nextElementSibling) {
            $children[] = $child;
        }

        return $children;
    }

    /**
     * @param \DOMElement $domElement the parent element
     *
     * @return list<\DOMElement> the child elements in the SVG namespace
     */
    private function svgChildElements(\DOMElement $domElement): array
    {
        $children = [];

        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement && NamespaceUri::Svg->value === $child->namespaceURI) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * @param \DOMElement                 $domElement the element whose children to check
     * @param \Closure(\DOMElement): bool $matches    the test for a child element
     */
    private function hasChild(\DOMElement $domElement, \Closure $matches): bool
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement && $matches($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement  $domElement the element to check
     * @param list<string> $names      the names of attributes without a namespace
     */
    private function hasAnyAttribute(\DOMElement $domElement, array $names): bool
    {
        foreach ($names as $name) {
            if ($domElement->hasAttribute($name)) {
                return true;
            }
        }

        return false;
    }
}
