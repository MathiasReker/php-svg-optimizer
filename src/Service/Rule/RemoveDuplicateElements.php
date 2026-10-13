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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDuplicateElements implements SvgOptimizerRuleInterface
{
    private const array NON_RENDERING_TAGS = [
        SvgTag::Desc->value,
        SvgTag::Metadata->value,
        SvgTag::Stop->value,
        SvgTag::Title->value,
    ];

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     *
     * @throws \JsonException
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $elements = iterator_to_array($domDocument->getElementsByTagName('*'), false);

        foreach ($elements as $element) {
            if ($this->isRedundant($element)) {
                $element->parentNode?->removeChild($element);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @throws \JsonException
     */
    private function isRedundant(\DOMElement $domElement): bool
    {
        $parent = $domElement->parentNode;
        $previous = $domElement->previousElementSibling;

        if (!$parent instanceof \DOMElement || !$previous instanceof \DOMElement) {
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
        if (\in_array($domElement->localName, self::NON_RENDERING_TAGS, true)) {
            return true;
        }

        return SvgTag::Defs->value === $parent->localName
            && SvgTag::Script->value !== $domElement->localName;
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
                'tag' => $domElement->tagName,
                'attrs' => $attrs,
                'children' => $children,
            ],
            \JSON_THROW_ON_ERROR
        );
    }
}
