<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait DefinitionElementTrait
{
    private const array REFERENCE_ONLY_TAGS = [
        'clipPath', 'filter', 'linearGradient', 'marker', 'mask', 'pattern', 'radialGradient', 'symbol',
    ];

    private const array EFFECTIVE_TAGS = [
        'color-profile', 'discard', 'font', 'font-face', 'script', 'style',
    ];

    /**
     * @param string $localName the local name of an element
     *
     * @return bool true if the element only renders when referenced
     */
    private function isReferenceOnlyTag(string $localName): bool
    {
        return \in_array($localName, self::REFERENCE_ONLY_TAGS, true);
    }

    /**
     * @param string $localName the local name of an element
     *
     * @return bool true if the element has an effect wherever it is, such as a style sheet, a script or an animation
     *              that may target an element elsewhere
     */
    private function isEffectiveTag(string $localName): bool
    {
        return \in_array($localName, self::EFFECTIVE_TAGS, true)
            || \in_array(mb_strtolower($localName), SvgTag::animation(), true);
    }

    /**
     * @param \DOMDocument $domDocument the document to read
     *
     * @return list<\DOMElement> every element in document order, unaffected by later changes to the document
     */
    private function elementsInDocument(\DOMDocument $domDocument): array
    {
        $elements = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $elements[] = $domNodeList;
        }

        return $elements;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element or a descendant has an effect wherever it is
     */
    private function hasEffectiveElementInSubtree(\DOMElement $domElement): bool
    {
        if ($this->isEffectiveTag($domElement->localName ?? '')) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if ($this->isEffectiveTag($domNodeList->localName ?? '')) {
                return true;
            }
        }

        return false;
    }
}
