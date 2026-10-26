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
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait AnimationTrait
{
    use IdTrait;

    private const string XPATH_ANIMATED_ATTRIBUTE_NAMES = '//@attributeName';

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<string, true> the names of all attributes targeted by animation elements
     */
    private function animatedAttributeNames(\DOMDocument $domDocument): array
    {
        $names = [];

        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = (new \DOMXPath($domDocument))->query(self::XPATH_ANIMATED_ATTRIBUTE_NAMES, null, false);

        foreach ($attributes as $attribute) {
            $names[trim($attribute->value)] = true;
        }

        return $names;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is an animation element, in any ASCII case
     */
    private function isAnimationElement(\DOMElement $domElement): bool
    {
        return \in_array(mb_strtolower((string) $domElement->localName), array_map(mb_strtolower(...), Element::animationNames()), true);
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<string, true> the ids targeted by an animation element
     */
    private function animationTargetIds(\DOMDocument $domDocument): array
    {
        $ids = [];

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if (!$this->isAnimationElement($element)) {
                continue;
            }

            $ids += $this->hrefTargetIds($element);
        }

        return $ids;
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return list<\DOMElement> the elements whose `id` or `xml:id` is targeted by an animation element
     */
    private function animationTargets(\DOMDocument $domDocument): array
    {
        $targetIds = $this->animationTargetIds($domDocument);
        $elements = [];

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            if ($this->isReferencedElement($domElement, $targetIds)) {
                $elements[] = $domElement;
            }
        }

        return $elements;
    }

    /**
     * @param \DOMElement $domElement the referencing element
     *
     * @return array<string, true> the ids its `href` and `xlink:href` point to
     */
    private function hrefTargetIds(\DOMElement $domElement): array
    {
        $ids = [];

        foreach ([
            $domElement->getAttribute(Attribute::Href->value),
            $domElement->getAttributeNS(NamespaceUri::Xlink->value, Attribute::Href->value),
        ] as $href) {
            $href = trim($href);

            if (str_starts_with($href, '#')) {
                $ids[mb_substr($href, 1)] = true;
            }
        }

        return $ids;
    }
}
