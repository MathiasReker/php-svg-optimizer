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

    private const string FONT_SHORTHAND_PROPERTIES = 'font|all';

    private const array TRANSFORM_ORIGIN_ATTRIBUTES = [
        SvgAttribute::TransformOrigin->value,
        'transform-box',
    ];

    private const string TRANSFORM_PROPERTIES = 'transform(?:-origin|-box)?|translate|rotate|scale|offset(?:-[a-z]+)?|all';

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
            if ($parent->hasAttribute($attributeName) && $this->setsFontProperty($child, $attributeName)) {
                return true;
            }
        }

        if (!$this->setsFontProperty($child, SvgAttribute::FontSize->value)) {
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

    /**
     * @param \DOMElement $parent the parent element
     * @param \DOMElement $child  the child element
     *
     * @return bool true if the child positions its own transform in a way that the transform of the parent cannot be
     *              merged into
     */
    private function dependsOnParentTransform(\DOMElement $parent, \DOMElement $child): bool
    {
        if (!$parent->hasAttribute(SvgAttribute::Transform->value)) {
            return false;
        }

        foreach (self::TRANSFORM_ORIGIN_ATTRIBUTES as $attributeName) {
            if ($child->hasAttribute($attributeName)) {
                return true;
            }
        }

        return $this->styleDeclares($child, self::TRANSFORM_PROPERTIES);
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $property   the font property
     *
     * @return bool true if the element sets the property as an attribute or in its inline style, also through a
     *              shorthand
     */
    private function setsFontProperty(\DOMElement $domElement, string $property): bool
    {
        return $domElement->hasAttribute($property)
            || $this->styleDeclares($domElement, $property . '|' . self::FONT_SHORTHAND_PROPERTIES);
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $properties the property names as regular expression alternatives
     *
     * @return bool true if the inline style of the element declares one of the properties, also with a vendor prefix
     */
    private function styleDeclares(\DOMElement $domElement, string $properties): bool
    {
        return 1 === preg_match(
            '/(?<![\w-])(?:-[a-z]+-)?(?:' . $properties . ')\s*:/i',
            $domElement->getAttribute(SvgAttribute::Style->value),
        );
    }
}
