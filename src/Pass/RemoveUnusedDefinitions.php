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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedDefinitions implements PassInterface
{
    use DefinitionElementTrait;
    use ElementTrait;
    use FragmentTargetTrait;
    use IdReferenceTrait;
    use IdTrait;
    use StyleSheetTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStructureSensitiveStyleSheet($domDocument)
            || $this->maySelectAnyId($domDocument)
            || $this->hasXmlId($domDocument)) {
            return;
        }

        $referencedIds = $this->reachableReferencedIds($domDocument, self::REFERENCE_ONLY_ELEMENTS, [], $this->fragmentTargetIds($domDocument));

        foreach ($this->elementsByQualifiedName($domDocument, '*') as $domElement) {
            if ($this->isUnusedDefinition($domElement, $referencedIds)) {
                $domElement->remove();
            }
        }

        $this->removeEmptyDefsElements($domDocument);
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if an element has an `xml:id`, whose references are not followed
     */
    private function hasXmlId(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if ($element->hasAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement         $domElement    the element to check
     * @param array<string, true> $referencedIds the ids referenced from rendered content
     */
    private function isUnusedDefinition(\DOMElement $domElement, array $referencedIds): bool
    {
        return $this->isDefinition($domElement)
            && $this->hasId($domElement)
            && !$this->isForeignContent($domElement)
            && !$this->hasReferencedElement($domElement, $referencedIds, [])
            && !$this->hasAlwaysEffectiveElementInSubtree($domElement);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element only renders when referenced
     */
    private function isDefinition(\DOMElement $domElement): bool
    {
        $localName = $domElement->localName ?? '';
        $parent = $domElement->parentNode;

        return $this->isReferenceOnlyElement($localName)
            || ($parent instanceof \DOMElement && Element::Defs->value === $parent->localName && !$this->isAlwaysEffectiveElement($localName));
    }
}
