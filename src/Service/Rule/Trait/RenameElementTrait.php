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
 * @no-named-arguments
 */
trait RenameElementTrait
{
    /**
     * Replaces the element with an element of another type, keeping its namespace, attributes and children.
     * The replacement is created without a prefix: on insertion, the serializer reuses the default namespace or a
     * prefix in scope for it, whereas a prefixed replacement would redeclare the namespace.
     * The element is left unchanged if the replacement cannot be created.
     *
     * @param \DOMElement $domElement the element to replace
     * @param string      $localName  the new element name
     */
    private function renameElement(\DOMElement $domElement, string $localName): void
    {
        $document = $domElement->ownerDocument;

        if (!$document instanceof \DOMDocument) {
            return;
        }

        try {
            $replacement = $document->createElementNS($domElement->namespaceURI, $localName);

            foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
                $replacement->setAttributeNS($domAttr->namespaceURI, $domAttr->nodeName, $domAttr->value);
            }
        } catch (\DOMException) {
            return;
        }

        while ($domElement->firstChild instanceof \DOMNode) {
            $replacement->appendChild($domElement->firstChild);
        }

        $domElement->parentNode?->replaceChild($replacement, $domElement);
    }
}
