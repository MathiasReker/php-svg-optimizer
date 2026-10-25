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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class SortDefsChildren implements SvgOptimizerRuleInterface
{
    use DefinitionElementTrait;
    use DocumentScriptTrait;
    use ElementTrait;

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
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument)
            || $this->hasScriptOrEventHandler($domDocument)
            || $this->hasDuplicateIds($domDocument)) {
            return;
        }

        foreach ($this->elementsNamed($domDocument, SvgTag::Defs->value) as $domElement) {
            if ($this->isSortable($domElement)) {
                $this->sortChildren($domElement);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if two elements share an id, so the order decides which one a reference resolves to
     */
    private function hasDuplicateIds(\DOMDocument $domDocument): bool
    {
        $ids = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach ([
                $domNodeList->getAttribute(SvgAttribute::Id->value),
                $domNodeList->getAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value),
            ] as $id) {
                if ('' !== $id) {
                    $ids[] = $id;
                }
            }
        }

        return \count($ids) !== \count(array_unique($ids));
    }

    /**
     * @param \DOMElement $domElement the `<defs>` to check
     *
     * @return bool true if the `<defs>` only has element children, so their order cannot affect the rendering
     */
    private function isSortable(\DOMElement $domElement): bool
    {
        if ($this->isForeignContent($domElement)) {
            return false;
        }

        foreach ($domElement->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement) {
                return false;
            }
        }

        return !$this->hasOrderSensitiveDescendant($domElement);
    }

    /**
     * @param \DOMElement $domElement the `<defs>` to check
     *
     * @return bool true if a descendant is outside the SVG namespace, or is a font, color profile, discard or animation
     */
    private function hasOrderSensitiveDescendant(\DOMElement $domElement): bool
    {
        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if (!$this->isSvgElement($domNodeList) || $this->isEffectiveTag($domNodeList->localName ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the `<defs>` to sort
     */
    private function sortChildren(\DOMElement $domElement): void
    {
        $elements = [];

        for ($child = $domElement->firstElementChild; $child instanceof \DOMElement; $child = $child->nextElementSibling) {
            $elements[] = $child;
        }

        $counts = array_count_values(array_map(static fn (\DOMElement $domElement): string => $domElement->localName ?? '', $elements));

        usort($elements, static fn (\DOMElement $a, \DOMElement $b): int => [$counts[$b->localName ?? ''], $a->localName ?? '']
            <=> [$counts[$a->localName ?? ''], $b->localName ?? '']);

        foreach ($elements as $element) {
            $domElement->appendChild($element);
        }
    }
}
