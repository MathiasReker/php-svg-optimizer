<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * Element lookups and checks shared by rules that rewrite or remove elements, so they only act where the result
 * renders identically.
 *
 * @no-named-arguments
 */
trait ElementTrait
{
    /**
     * A `<style>` element may target elements by tag name or set properties, so rules that change element types or
     * rely on attribute values bail out when one is present.
     *
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasStyleElement(\DOMDocument $domDocument): bool
    {
        return [] !== $this->elementsNamed($domDocument, SvgTag::Style->value);
    }

    /**
     * @param \DOMDocument $domDocument the document to search
     * @param string       $localName   the element name to look for
     *
     * @return list<\DOMElement> the matching elements in document order
     */
    private function elementsNamed(\DOMDocument $domDocument, string $localName): array
    {
        $elements = [];

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            if ($localName === $domElement->localName) {
                $elements[] = $domElement;
            }
        }

        return $elements;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasElementChildren(\DOMElement $domElement): bool
    {
        return $domElement->firstElementChild instanceof \DOMElement;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element has no child elements and no non-whitespace text
     */
    private function isEmptyElement(\DOMElement $domElement): bool
    {
        return !$this->hasElementChildren($domElement) && '' === trim($domElement->textContent);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element can be referenced by id
     */
    private function hasId(\DOMElement $domElement): bool
    {
        return $domElement->hasAttribute(SvgAttribute::Id->value)
            || $domElement->hasAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element or a descendant can be referenced by id
     */
    private function hasIdInSubtree(\DOMElement $domElement): bool
    {
        if ($this->hasId($domElement)) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $descendant) {
            if ($this->hasId($descendant)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Animation elements may target another element through `href` or `xlink:href` instead of their parent, so rules
     * that change the element type must leave such targets alone.
     *
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<string, true> the ids targeted by an animation element
     */
    private function animationTargetIds(\DOMDocument $domDocument): array
    {
        $ids = [];

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            if (!\in_array(mb_strtolower((string) $domElement->localName), SvgTag::animation(), true)) {
                continue;
            }

            foreach ([
                $domElement->getAttribute(SvgAttribute::Href->value),
                $domElement->getAttributeNS(SvgNamespace::Xlink->value, SvgAttribute::Href->value),
            ] as $href) {
                $href = trim($href);

                if (str_starts_with($href, '#')) {
                    $ids[mb_substr($href, 1)] = true;
                }
            }
        }

        return $ids;
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return list<\DOMElement> the elements whose `id` or `xml:id` is targeted by an animation element
     */
    private function animationTargets(\DOMDocument $domDocument): array
    {
        $targetIds = $this->animationTargetIds($domDocument);
        $elements = [];

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            $ids = [
                $domElement->getAttribute(SvgAttribute::Id->value) => true,
                $domElement->getAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value) => true,
            ];
            unset($ids['']);

            if ([] !== array_intersect_key($ids, $targetIds)) {
                $elements[] = $domElement;
            }
        }

        return $elements;
    }

    /**
     * Removes `<defs>` elements that have no content and cannot be referenced.
     *
     * @param \DOMDocument $domDocument the document to clean
     */
    private function removeEmptyDefs(\DOMDocument $domDocument): void
    {
        foreach ($this->elementsNamed($domDocument, SvgTag::Defs->value) as $defs) {
            if (!$this->hasId($defs) && $this->isEmptyElement($defs)) {
                $defs->parentNode?->removeChild($defs);
            }
        }
    }
}
