<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;

final class RemoveTitleAndDesc implements SvgOptimizerRuleInterface
{
    /**
     * Remove the `<title>` and `<desc>` elements from the SVG document.
     *
     * The `<title>` and `<desc>` elements are typically used for accessibility
     * and descriptive purposes but can be removed if not needed to reduce
     * the file size.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeElementsByTagName($domDocument, 'title');
        $this->removeElementsByTagName($domDocument, 'desc');
    }

    /**
     * Remove all elements with the given tag name from the DOMDocument.
     *
     * This method removes all elements with the specified tag name from the
     * DOMDocument. It continues removing elements until none with the given
     * tag name remain in the document.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     * @param string       $tagName     The tag name of the elements to be removed
     */
    private function removeElementsByTagName(\DOMDocument $domDocument, string $tagName): void
    {
        $domNodeList = $domDocument->getElementsByTagName($tagName);

        while ($domNodeList->length > 0) {
            /**
             * @var \DOMElement|null $element
             */
            $element = $domNodeList->item(0);

            if ($element instanceof \DOMElement && $element->parentNode instanceof \DOMNode) {
                $element->parentNode->removeChild($element);
            }
        }
    }
}
