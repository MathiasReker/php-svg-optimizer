<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveNonStandardTags implements PassInterface
{
    use DefinitionElementTrait;
    use ElementTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasSiblingSensitiveStyle($domDocument)) {
            return;
        }

        $allowed = $this->getAllowedTagLookup();
        $documentNamespace = $domDocument->documentElement?->namespaceURI;

        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            $parent = $domElement->parentNode;

            if ($domElement->isConnected
                && $parent instanceof \DOMElement
                && !\array_key_exists($this->normalizeName($domElement->tagName), $allowed)
                && $domElement->namespaceURI === $documentNamespace
                && $this->isRemovable($domElement, $parent)
            ) {
                $parent->removeChild($domElement);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the unknown element
     * @param \DOMElement $parent     the parent of the element
     *
     * @return bool true if neither the element nor its content renders, can be referenced, or has an effect elsewhere
     */
    private function isRemovable(\DOMElement $domElement, \DOMElement $parent): bool
    {
        return SvgTag::Switch->value !== $parent->localName
            && !$this->isForeignContent($domElement)
            && !$this->hasIdInSubtree($domElement)
            && !$this->hasEffectiveElementInSubtree($domElement);
    }

    /**
     * @return array<string, string> the normalized tag names, by themselves
     */
    private function getAllowedTagLookup(): array
    {
        $lookup = [];

        foreach (SvgTag::cases() as $tag) {
            if (str_starts_with($tag->value, '#')) {
                continue;
            }

            $name = $this->normalizeName($tag->value);
            $lookup[$name] = $name;
        }

        return $lookup;
    }

    /**
     * @param string $name the tag name to normalize
     *
     * @return string the normalized tag name
     */
    private function normalizeName(string $name): string
    {
        $pos = mb_strrpos($name, ':');
        if (false !== $pos) {
            $name = mb_substr($name, $pos + 1);
        }

        return mb_strtolower($name);
    }
}
