<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ElementRenameTrait
{
    use AttributeNodeTrait;

    /**
     * @param \DOMElement $domElement the element to replace
     * @param string      $localName  the new element name
     */
    private function renameElement(\DOMDocument $domDocument, \DOMElement $domElement, string $localName): void
    {
        $replacement = $domDocument->createElementNS($domElement->namespaceURI, $localName);

        $this->moveAttributeNodes($domElement, $replacement);

        while ($domElement->firstChild instanceof \DOMNode) {
            $replacement->appendChild($domElement->firstChild);
        }

        $domElement->replaceWith($replacement);
    }
}
