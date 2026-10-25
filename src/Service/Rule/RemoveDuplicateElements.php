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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDuplicateElements implements SvgOptimizerRuleInterface
{
    use DefinitionElementTrait;
    use ElementTrait;

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
        if ($this->hasSiblingSensitiveStyle($domDocument)) {
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
        return (\in_array($domElement->localName, self::NON_RENDERING_TAGS, true) || SvgTag::Defs->value === $parent->localName)
            && !$this->hasEffectiveElementInSubtree($domElement);
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
