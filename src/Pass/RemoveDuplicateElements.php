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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDuplicateElements implements PassInterface
{
    use DefinitionElementTrait;
    use StyleSheetTrait;

    private const array NEVER_RENDERED_ELEMENTS = [
        Element::Desc->value,
        Element::Metadata->value,
        Element::Stop->value,
        Element::Title->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     *
     * @throws \JsonException
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStructureSensitiveStyleSheet($domDocument)) {
            return;
        }

        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            $parent = $domElement->parentNode;

            if ($parent instanceof \DOMElement && $this->isRedundant($domElement, $parent)) {
                $parent->removeChild($domElement);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param \DOMElement $parent     the parent of the element
     *
     * @throws \JsonException
     */
    private function isRedundant(\DOMElement $domElement, \DOMElement $parent): bool
    {
        $previous = $domElement->previousElementSibling;

        if (!$previous instanceof \DOMElement) {
            return false;
        }

        if (!$this->isNonRendering($domElement, $parent)) {
            return false;
        }

        return $this->buildSignature($previous) === $this->buildSignature($domElement);
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param \DOMElement $parent     the parent of the element
     */
    private function isNonRendering(\DOMElement $domElement, \DOMElement $parent): bool
    {
        return (\in_array($domElement->localName, self::NEVER_RENDERED_ELEMENTS, true) || Element::Defs->value === $parent->localName)
            && !$this->hasAlwaysEffectiveElementInSubtree($domElement);
    }

    /**
     * @throws \JsonException
     */
    private function buildSignature(\DOMElement $domElement): string
    {
        $attrs = [];

        foreach ($domElement->attributes as $attribute) {
            $attrs[$attribute->nodeName] = trim((string) $attribute->nodeValue);
        }

        ksort($attrs);

        $children = [];

        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $this->buildSignature($child);
            } elseif ($child instanceof \DOMText) {
                $children[] = trim((string) $child->nodeValue);
            }
        }

        return json_encode(
            [
                'namespace' => $domElement->namespaceURI,
                'tag' => $domElement->tagName,
                'attrs' => $attrs,
                'children' => $children,
            ],
            \JSON_THROW_ON_ERROR
        );
    }
}
