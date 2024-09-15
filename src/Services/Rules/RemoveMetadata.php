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

final class RemoveMetadata implements SvgOptimizerRuleInterface
{
    /**
     * Remove the metadata elements from the SVG document.
     *
     * This method will find and remove all `<metadata>` elements within the SVG
     * document. Metadata elements typically contain information that is not
     * essential for rendering the SVG image.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeElementsByTagName($domDocument, 'metadata');
    }

    /**
     * Remove all elements with the given tag name from the DOMDocument.
     *
     * This method removes all elements with the specified tag name from the
     * DOMDocument. It repeatedly removes elements until none with the given tag
     * name remain.
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
