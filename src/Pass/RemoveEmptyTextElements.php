<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEmptyTextElements implements PassInterface
{
    use ElementTrait;
    use IdTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $root = $domDocument->documentElement;
        if ($root instanceof \DOMElement && !$this->hasStructureSensitiveStyleSheet($domDocument) && !$this->hasScriptOrEventHandler($domDocument)) {
            $this->removeEmptyTextElementsIn($root);
        }
    }

    /**
     * @param \DOMElement $domElement the element to process
     */
    private function removeEmptyTextElementsIn(\DOMElement $domElement): void
    {
        foreach (iterator_to_array($domElement->childNodes, true) as $domNode) {
            if (!$domNode instanceof \DOMElement) {
                continue;
            }

            $this->removeEmptyTextElementsIn($domNode);

            if ($this->isRemovable($domElement, $domNode)) {
                $domElement->removeChild($domNode);
            }
        }
    }

    /**
     * @param \DOMElement $parent     the parent of the element
     * @param \DOMElement $domElement the element to check
     */
    private function isRemovable(\DOMElement $parent, \DOMElement $domElement): bool
    {
        if (!$this->isSvgElement($domElement)
            || $this->isUserExposed($domElement)
            || Element::Switch->value === $parent->localName
        ) {
            return false;
        }

        $tag = $domElement->localName;

        return (($tag === Element::Text->value || $tag === Element::Tspan->value) && $this->hasNoContent($domElement))
            || ($tag === Element::Tref->value && !$this->hasReference($domElement));
    }

    /**
     * @param \DOMElement $domElement the `<tref>` element to check
     */
    private function hasReference(\DOMElement $domElement): bool
    {
        return '' !== $domElement->getAttributeNS(NamespaceUri::Xlink->value, Attribute::Href->value)
            || '' !== $domElement->getAttribute(Attribute::Href->value);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element has no child elements and no text, not even whitespace, which renders as a space
     *              between the surrounding text
     */
    private function hasNoContent(\DOMElement $domElement): bool
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                return false;
            }

            if ($child instanceof \DOMText && '' !== $child->data) {
                return false;
            }
        }

        return true;
    }
}
