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
trait RemoveElementsByTagNameTrait
{
    use DefinitionElementTrait;
    use ElementTrait;

    /**
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     * @param string       $tagName     The tag name of the elements to be removed
     */
    private function removeElementsByTagName(\DOMDocument $domDocument, string $tagName): void
    {
        if ($this->hasSiblingSensitiveStyle($domDocument)) {
            return;
        }

        foreach ($this->elementsNamed($domDocument, $tagName) as $domElement) {
            if ($this->isRemovableUnrenderedElement($domElement, [])) {
                $domElement->remove();
            }
        }
    }

    /**
     * @param \DOMElement  $domElement   the element to check
     * @param list<string> $removedNames the names of the descendants that are removed wherever they are
     *
     * @return bool true if the element is SVG content outside a `<switch>` and no other descendant can be referenced
     *              by id or has an effect wherever it is
     */
    private function isRemovableUnrenderedElement(\DOMElement $domElement, array $removedNames): bool
    {
        $parent = $domElement->parentNode;

        return $parent instanceof \DOMElement
            && SvgTag::Switch->value !== $parent->localName
            && !$this->isForeignContent($domElement)
            && !$this->hasReferenceableDescendant($domElement, $removedNames);
    }

    /**
     * @param \DOMElement  $domElement   the element to check
     * @param list<string> $removedNames the names of the descendants that are removed wherever they are
     *
     * @return bool true if another descendant can be referenced by id or has an effect wherever it is, such as a
     *              style sheet, a script or an animation
     */
    private function hasReferenceableDescendant(\DOMElement $domElement, array $removedNames): bool
    {
        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            $localName = (string) $domNodeList->localName;

            if (!\in_array($localName, $removedNames, true)
                && ($this->hasId($domNodeList) || $this->isEffectiveTag($localName))
            ) {
                return true;
            }
        }

        return false;
    }
}
