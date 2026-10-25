<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Security;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait UnsafeAttributeTrait
{
    use ElementTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeUnsafeAttributes(\DOMDocument $domDocument): void
    {
        $arrayObject = new \ArrayObject();

        foreach ($this->elementsByQualifiedName($domDocument, '*') as $domElement) {
            $this->removeUnsafeAttributesOf($domElement, $arrayObject);
        }
    }

    /**
     * @param \DOMElement                $domElement  the element to clean
     * @param \ArrayObject<string, bool> $arrayObject the verdicts for attributes checked before, by namespace, name and value
     */
    private function removeUnsafeAttributesOf(\DOMElement $domElement, \ArrayObject $arrayObject): void
    {
        foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
            $key = serialize([$domAttr->namespaceURI, $domAttr->nodeName, $domAttr->value]);

            if (!$arrayObject->offsetExists($key)) {
                $arrayObject[$key] = $this->isUnsafeAttribute($domAttr);
            }

            if (true === $arrayObject[$key]) {
                $domElement->removeAttributeNode($domAttr);
            }
        }
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the attribute is unsafe
     */
    abstract private function isUnsafeAttribute(\DOMAttr $domAttr): bool;
}
