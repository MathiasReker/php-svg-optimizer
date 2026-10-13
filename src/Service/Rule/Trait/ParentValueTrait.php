<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ParentValueTrait
{
    private const string INHERIT_VALUE = 'inherit';

    private const array PARENT_RELATIVE_ATTRIBUTES = [
        SvgAttribute::FontSize->value,
        SvgAttribute::FontStretch->value,
        SvgAttribute::FontWeight->value,
    ];

    private const string FONT_RELATIVE_UNIT_REGEX = '/[\d.](?:em|ex|ch)\b/i';

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the inline style of the element or an animation inside it uses `inherit`, which takes the
     *              value of the parent
     */
    private function usesInheritValue(\DOMElement $domElement): bool
    {
        if (str_contains(mb_strtolower($domElement->getAttribute(SvgAttribute::Style->value)), self::INHERIT_VALUE)) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if (!\in_array(mb_strtolower((string) $domNodeList->localName), SvgTag::animation(), true)) {
                continue;
            }

            foreach ([SvgAttribute::By, SvgAttribute::From, SvgAttribute::To, SvgAttribute::Values] as $svgAttribute) {
                if (str_contains(mb_strtolower($domNodeList->getAttribute($svgAttribute->value)), self::INHERIT_VALUE)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $parent the parent element
     * @param \DOMElement $child  the child element
     */
    private function dependsOnParentFont(\DOMElement $parent, \DOMElement $child): bool
    {
        foreach (self::PARENT_RELATIVE_ATTRIBUTES as $attributeName) {
            if ($parent->hasAttribute($attributeName) && $child->hasAttribute($attributeName)) {
                return true;
            }
        }

        if (!$child->hasAttribute(SvgAttribute::FontSize->value)
            && !str_contains(mb_strtolower($child->getAttribute(SvgAttribute::Style->value)), SvgAttribute::FontSize->value)
        ) {
            return false;
        }

        /** @var \DOMAttr $attribute */
        foreach ($parent->attributes as $attribute) {
            if (1 === preg_match(self::FONT_RELATIVE_UNIT_REGEX, $attribute->value)) {
                return true;
            }
        }

        return false;
    }
}
