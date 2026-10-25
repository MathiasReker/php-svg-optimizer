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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\FragmentTargetTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;

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

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasSiblingSensitiveStyle($domDocument)
            || $this->mayTargetAnyId($domDocument)
            || $this->hasXmlId($domDocument)) {
            return;
        }

        $referencedIds = $this->collectLiveReferencedIds($domDocument, self::REFERENCE_ONLY_TAGS, [], $this->collectFragmentTargets($domDocument));

        foreach ($this->elementsInDocument($domDocument) as $domElement) {
            if ($this->isUnusedDefinition($domElement, $referencedIds)) {
                $domElement->remove();
            }
        }

        $this->removeEmptyDefs($domDocument);
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if an element has an `xml:id`, whose references are not followed
     */
    private function hasXmlId(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if ($domNodeList->hasAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value)) {
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
            && !$this->hasEffectiveElementInSubtree($domElement);
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

        return $this->isReferenceOnlyTag($localName)
            || ($parent instanceof \DOMElement && SvgTag::Defs->value === $parent->localName && !$this->isEffectiveTag($localName));
    }
}
