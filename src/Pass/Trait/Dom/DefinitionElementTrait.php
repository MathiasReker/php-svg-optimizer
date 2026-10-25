<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait DefinitionElementTrait
{
    use ElementTrait;
    use IdTrait;

    private const array REFERENCE_ONLY_ELEMENTS = [
        'clipPath', 'filter', 'linearGradient', 'marker', 'mask', 'pattern', 'radialGradient', 'symbol',
    ];

    private const array ALWAYS_EFFECTIVE_ELEMENTS = [
        'color-profile', 'discard', 'font', 'font-face', 'script', 'style',
    ];

    /**
     * @param string $localName the local name of an element
     *
     * @return bool true if the element only renders when referenced
     */
    private function isReferenceOnlyElement(string $localName): bool
    {
        return \in_array($localName, self::REFERENCE_ONLY_ELEMENTS, true);
    }

    /**
     * @param string $localName the local name of an element
     *
     * @return bool true if the element has an effect wherever it is, such as a style sheet, a script or an animation
     *              that may target an element elsewhere
     */
    private function isAlwaysEffectiveElement(string $localName): bool
    {
        return \in_array($localName, self::ALWAYS_EFFECTIVE_ELEMENTS, true)
            || \in_array(mb_strtolower($localName), array_map(mb_strtolower(...), Element::animationNames()), true);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element or a descendant has an effect wherever it is
     */
    private function hasAlwaysEffectiveElementInSubtree(\DOMElement $domElement): bool
    {
        if ($this->isAlwaysEffectiveElement($domElement->localName ?? '')) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $element) {
            if ($this->isAlwaysEffectiveElement($element->localName ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument the document to clean
     */
    private function removeEmptyDefsElements(\DOMDocument $domDocument): void
    {
        foreach ($this->elementsByLocalName($domDocument, Element::Defs->value) as $defs) {
            if (!$this->hasId($defs) && $this->isEmptyElement($defs)) {
                $defs->remove();
            }
        }
    }
}
