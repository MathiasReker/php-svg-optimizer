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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveIneffectiveGroupAttributes implements PassInterface
{
    use AnimationTrait;
    use IneffectiveAttributeTrait;
    use StyleSheetTrait;

    private const array GROUP_IGNORED_ATTRIBUTES = [
        Attribute::AlignmentBaseline->value,
        Attribute::BaselineShift->value,
        Attribute::Cx->value,
        Attribute::Cy->value,
        Attribute::D->value,
        Attribute::FloodColor->value,
        Attribute::FloodOpacity->value,
        Attribute::Height->value,
        Attribute::LightingColor->value,
        Attribute::MaskType->value,
        Attribute::R->value,
        Attribute::Rx->value,
        Attribute::Ry->value,
        Attribute::StopColor->value,
        Attribute::StopOpacity->value,
        Attribute::VectorEffect->value,
        Attribute::Width->value,
        Attribute::X->value,
        Attribute::Y->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument)) {
            return;
        }

        $animated = $this->animatedAttributeNames($domDocument);
        $inheritingDescendants = $this->elementsWithInheritingDescendant($domDocument);

        foreach ($this->svgContentElements($domDocument) as $domElement) {
            if (Element::G->value === $domElement->localName && !\array_key_exists(spl_object_id($domElement), $inheritingDescendants)) {
                $this->removeUnanimatedAttributes($domElement, self::GROUP_IGNORED_ATTRIBUTES, $animated);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<int, \DOMElement> the elements with a descendant that uses `inherit`, which also takes non-inherited
     *                                 values from them, by object id
     */
    private function elementsWithInheritingDescendant(\DOMDocument $domDocument): array
    {
        $elements = [];

        foreach (array_reverse(iterator_to_array($domDocument->getElementsByTagName('*'), false)) as $domElement) {
            $parent = $domElement->parentNode;

            if ($parent instanceof \DOMElement
                && (\array_key_exists(spl_object_id($domElement), $elements) || $this->usesInheritInAnyAttribute($domElement))
            ) {
                $elements[spl_object_id($parent)] = $parent;
            }
        }

        return $elements;
    }
}
