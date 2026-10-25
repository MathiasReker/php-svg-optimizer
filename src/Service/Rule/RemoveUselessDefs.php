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
final readonly class RemoveUselessDefs implements SvgOptimizerRuleInterface
{
    use DefinitionElementTrait;
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
        if ($this->hasSiblingSensitiveStyle($domDocument)) {
            return;
        }

        foreach ($this->elementsInDocument($domDocument) as $domElement) {
            if ($this->isUselessDefinition($domElement)) {
                $domElement->remove();
            }
        }

        $this->removeEmptyDefs($domDocument);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isUselessDefinition(\DOMElement $domElement): bool
    {
        $localName = $domElement->localName ?? '';
        $parent = $domElement->parentNode;
        $isDefsChild = $parent instanceof \DOMElement && SvgTag::Defs->value === $parent->localName;

        $isCandidate = ($this->isReferenceOnlyTag($localName) && $this->isSvgElement($domElement))
            || ($isDefsChild && !$this->isEffectiveTag($localName));

        return $isCandidate && !$this->hasIdInSubtree($domElement) && !$this->hasEffectiveElementInSubtree($domElement);
    }
}
