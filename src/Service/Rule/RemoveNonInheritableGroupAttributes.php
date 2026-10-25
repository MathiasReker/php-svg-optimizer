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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PresentationAttributeCleanupTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveNonInheritableGroupAttributes implements PassInterface
{
    use PresentationAttributeCleanupTrait;

    private const array GROUP_IGNORED_ATTRIBUTES = [
        SvgAttribute::AlignmentBaseline->value,
        SvgAttribute::BaselineShift->value,
        SvgAttribute::Cx->value,
        SvgAttribute::Cy->value,
        SvgAttribute::D->value,
        SvgAttribute::FloodColor->value,
        SvgAttribute::FloodOpacity->value,
        SvgAttribute::Height->value,
        SvgAttribute::LightingColor->value,
        SvgAttribute::MaskType->value,
        SvgAttribute::R->value,
        SvgAttribute::Rx->value,
        SvgAttribute::Ry->value,
        SvgAttribute::StopColor->value,
        SvgAttribute::StopOpacity->value,
        SvgAttribute::VectorEffect->value,
        SvgAttribute::Width->value,
        SvgAttribute::X->value,
        SvgAttribute::Y->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument)) {
            return;
        }

        $animated = $this->animatedAttributeNames($domDocument);
        $inheritingDescendants = $this->elementsWithInheritingDescendant($domDocument);

        foreach ($this->svgElements($domDocument) as $domElement) {
            if (SvgTag::G->value === $domElement->localName && !\array_key_exists(spl_object_id($domElement), $inheritingDescendants)) {
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
                && (\array_key_exists(spl_object_id($domElement), $elements) || $this->hasInheritValue($domElement))
            ) {
                $elements[spl_object_id($parent)] = $parent;
            }
        }

        return $elements;
    }
}
