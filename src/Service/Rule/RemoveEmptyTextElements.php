<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEmptyTextElements implements SvgOptimizerRuleInterface
{
    use ElementTrait;

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return true;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $root = $domDocument->documentElement;
        if ($root instanceof \DOMElement && !$this->hasSiblingSensitiveStyle($domDocument)) {
            $this->removeEmptyTextRecursive($root);
        }
    }

    /**
     * @param \DOMElement $domElement the element to process
     */
    private function removeEmptyTextRecursive(\DOMElement $domElement): void
    {
        foreach (iterator_to_array($domElement->childNodes, true) as $domNode) {
            if (!$domNode instanceof \DOMElement) {
                continue;
            }

            $this->removeEmptyTextRecursive($domNode);

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
            || SvgTag::Switch->value === $parent->localName
        ) {
            return false;
        }

        $tag = $domElement->localName;

        return (($tag === SvgTag::Text->value || $tag === SvgTag::Tspan->value) && $this->hasNoContent($domElement))
            || ($tag === SvgTag::Tref->value && !$this->hasReference($domElement));
    }

    /**
     * @param \DOMElement $domElement the `<tref>` element to check
     */
    private function hasReference(\DOMElement $domElement): bool
    {
        return '' !== $domElement->getAttributeNS(SvgNamespace::Xlink->value, SvgAttribute::Href->value)
            || '' !== $domElement->getAttribute(SvgAttribute::Href->value);
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
