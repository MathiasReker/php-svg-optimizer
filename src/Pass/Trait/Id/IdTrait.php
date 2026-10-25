<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Id;

use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait IdTrait
{
    private const string USER_EXPOSED_ATTRIBUTE_REGEX = '/^(?:role|tabindex|autofocus|aria-.*|on.*)$/i';

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element can be referenced by id
     */
    private function hasId(\DOMElement $domElement): bool
    {
        return $domElement->hasAttribute(Attribute::Id->value)
            || $domElement->hasAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element can be referenced by id, is exposed to assistive technology, can take focus or
     *              handles events
     */
    private function isUserExposed(\DOMElement $domElement): bool
    {
        if ($this->hasId($domElement)) {
            return true;
        }

        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if (null === $attribute->namespaceURI && 1 === preg_match(self::USER_EXPOSED_ATTRIBUTE_REGEX, (string) $attribute->localName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element or a descendant in the SVG namespace has an id
     */
    private function hasSvgElementWithId(\DOMElement $domElement): bool
    {
        foreach ([[$domElement], $domElement->getElementsByTagName('*')] as $elements) {
            foreach ($elements as $element) {
                if ($this->hasId($element) && \in_array($element->namespaceURI, [null, NamespaceUri::Svg->value], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element or a descendant can be referenced by id
     */
    private function hasIdInSubtree(\DOMElement $domElement): bool
    {
        if ($this->hasId($domElement)) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $element) {
            if ($this->hasId($element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to read
     */
    private function xmlId(\DOMElement $domElement): string
    {
        return $domElement->getAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value);
    }
}
