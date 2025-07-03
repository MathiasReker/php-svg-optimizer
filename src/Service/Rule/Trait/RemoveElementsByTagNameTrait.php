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
trait RemoveElementsByTagNameTrait
{
    /**
     * Remove all elements with the given tag name from the \DOMDocument.
     *
     * This method removes all elements with the specified tag name from the
     * \DOMDocument. It repeatedly removes elements until none with the given tag
     * name remain.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     * @param string       $tagName     The tag name of the elements to be removed
     */
    private function removeElementsByTagName(\DOMDocument $domDocument, string $tagName): void
    {
        $domNodeList = $domDocument->getElementsByTagName($tagName);

        while ($domNodeList->length > 0) {
            $element = $domNodeList->item(0);

            if ($element instanceof \DOMElement && $element->parentNode instanceof \DOMNode) {
                $element->parentNode->removeChild($element);
            }
        }
    }
}
