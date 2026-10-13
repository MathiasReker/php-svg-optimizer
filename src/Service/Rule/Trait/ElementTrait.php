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
 * @internal
 *
 * @no-named-arguments
 */
trait ElementTrait
{
    private const string SIBLING_SENSITIVE_CSS_REGEX = '/[+~]|:(?:nth-|first-|last-|only-|empty|has\b)|@import/i';

    private const string XML_STYLESHEET = 'xml-stylesheet';

    /**
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasStyleElement(\DOMDocument $domDocument): bool
    {
        return [] !== $this->elementsNamed($domDocument, SvgTag::Style->value);
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasSiblingSensitiveStyle(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->childNodes as $childNode) {
            if ($childNode instanceof \DOMProcessingInstruction && self::XML_STYLESHEET === $childNode->target) {
                return true;
            }
        }

        foreach ($this->elementsNamed($domDocument, SvgTag::Style->value) as $style) {
            if (1 === preg_match(self::SIBLING_SENSITIVE_CSS_REGEX, $style->textContent)) {
                return true;
            }
        }

        return false;
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

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if ($localName === $domNodeList->localName) {
                $elements[] = $domNodeList;
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
     * @return bool true if the element is in the SVG namespace
     */
    private function isSvgElement(\DOMElement $domElement): bool
    {
        return SvgNamespace::Svg->value === $domElement->namespaceURI;
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

        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if ($this->hasId($domNodeList)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<string, true> the ids targeted by an animation element
     */
    private function animationTargetIds(\DOMDocument $domDocument): array
    {
        $ids = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (!\in_array(mb_strtolower((string) $domNodeList->localName), SvgTag::animation(), true)) {
                continue;
            }

            foreach ([
                $domNodeList->getAttribute(SvgAttribute::Href->value),
                $domNodeList->getAttributeNS(SvgNamespace::Xlink->value, SvgAttribute::Href->value),
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

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $ids = [
                $domNodeList->getAttribute(SvgAttribute::Id->value) => true,
                $domNodeList->getAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value) => true,
            ];
            unset($ids['']);

            if ([] !== array_intersect_key($ids, $targetIds)) {
                $elements[] = $domNodeList;
            }
        }

        return $elements;
    }

    /**
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
