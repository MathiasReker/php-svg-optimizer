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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveIneffectiveDefinitions implements PassInterface
{
    use DefinitionElementTrait;
    use ElementTrait;
    use IdTrait;
    use StyleSheetTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStructureSensitiveStyleSheet($domDocument)) {
            return;
        }

        foreach ($this->elementsByQualifiedName($domDocument, '*') as $domElement) {
            if ($this->isUselessDefinition($domElement)) {
                $domElement->remove();
            }
        }

        $this->removeEmptyDefsElements($domDocument);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isUselessDefinition(\DOMElement $domElement): bool
    {
        $localName = $domElement->localName ?? '';
        $parent = $domElement->parentNode;
        $isDefsChild = $parent instanceof \DOMElement && Element::Defs->value === $parent->localName;

        $isCandidate = ($this->isReferenceOnlyElement($localName) && $this->isSvgElement($domElement))
            || ($isDefsChild && !$this->isAlwaysEffectiveElement($localName));

        return $isCandidate && !$this->hasIdInSubtree($domElement) && !$this->hasAlwaysEffectiveElementInSubtree($domElement);
    }
}
