<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ElementRemovalTrait
{
    use DefinitionElementTrait;
    use ElementTrait;
    use IdTrait;
    use StyleSheetTrait;

    /**
     * @param \DOMDocument                $domDocument the DOM document to clean
     * @param callable(\DOMElement): bool $predicate   returns true for elements to remove
     */
    private function removeElementsWhere(\DOMDocument $domDocument, callable $predicate): void
    {
        foreach ($this->elementsByQualifiedName($domDocument, '*') as $domElement) {
            if ($domElement !== $domDocument->documentElement && $predicate($domElement)) {
                $domElement->remove();
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     * @param string       $tagName     The tag name of the elements to be removed
     */
    private function removeUnrenderedElementsByLocalName(\DOMDocument $domDocument, string $tagName): void
    {
        if ($this->hasStructureSensitiveStyleSheet($domDocument)) {
            return;
        }

        foreach ($this->elementsByLocalName($domDocument, $tagName) as $domElement) {
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
            && Element::Switch->value !== $parent->localName
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
        foreach ($domElement->getElementsByTagName('*') as $element) {
            $localName = (string) $element->localName;

            if (!\in_array($localName, $removedNames, true)
                && ($this->hasId($element) || $this->isAlwaysEffectiveElement($localName))
            ) {
                return true;
            }
        }

        return false;
    }
}
