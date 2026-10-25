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

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ParentValueTrait
{
    use ElementTrait;
    use NamedColorTrait;
    use NumberFormatterTrait;

    private const string CURRENT_COLOR = 'currentcolor';

    private const array KEYWORD_VALUES = [
        SvgAttribute::ClipRule->value => ['evenodd', 'nonzero'],
        SvgAttribute::Fill->value => ['none'],
        SvgAttribute::FillRule->value => ['evenodd', 'nonzero'],
        SvgAttribute::Stroke->value => ['none'],
        SvgAttribute::StrokeLinecap->value => ['butt', 'round', 'square'],
        SvgAttribute::StrokeLinejoin->value => ['bevel', 'miter', 'round'],
        SvgAttribute::TextAnchor->value => ['end', 'middle', 'start'],
        SvgAttribute::Visibility->value => ['collapse', 'hidden', 'visible'],
    ];

    private const array NON_NEGATIVE_NUMBER_ATTRIBUTES = [
        SvgAttribute::FillOpacity->value,
        SvgAttribute::StrokeOpacity->value,
        SvgAttribute::StrokeWidth->value,
    ];

    private const array PAINT_ATTRIBUTES = [
        SvgAttribute::Fill->value,
        SvgAttribute::Stroke->value,
    ];

    private const string HEX_COLOR_REGEX = '/^#(?:[\da-fA-F]{3,4}|[\da-fA-F]{6}|[\da-fA-F]{8})$/';

    private const string PAINT_URL_REGEX = '/^url\(#[\w.-]+\)$/';

    private const string NUMBER_VALUE_REGEX = '/^' . self::NUMBER_PATTERN . '$/';

    private const array PARENT_RELATIVE_ATTRIBUTES = [
        SvgAttribute::FontSize->value,
        SvgAttribute::FontStretch->value,
        SvgAttribute::FontWeight->value,
    ];

    private const string FONT_RELATIVE_UNIT_REGEX = '/[\d.]r?(?:em|ex|ch|cap|ic|lh)\b/i';

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
        if ($this->mayUseParentValue($domElement->getAttribute(SvgAttribute::Style->value))) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if (!$this->isAnimationElement($domNodeList)) {
                continue;
            }

            foreach ([SvgAttribute::By, SvgAttribute::From, SvgAttribute::To, SvgAttribute::Values] as $svgAttribute) {
                if ($this->mayUseParentValue($domNodeList->getAttribute($svgAttribute->value))) {
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
     * @param \DOMElement $parent the parent element
     * @param \DOMElement $child  the child element
     *
     * @return bool true if the child overrides an inherited attribute of the parent with a value that the browser may
     *              reject, which then falls back to the value of the parent
     */
    private function mayFallBackToParentValue(\DOMElement $parent, \DOMElement $child): bool
    {
        /** @var \DOMAttr $attribute */
        foreach ($parent->attributes as $attribute) {
            $ownValue = $child->getAttribute($attribute->nodeName);

            if (\in_array($attribute->nodeName, SvgAttribute::inherited(), true)
                && $child->hasAttribute($attribute->nodeName)
                && $ownValue !== $attribute->value
                && !$this->isInheritKeyword($ownValue)
                && !$this->isKnownValidValue($attribute->nodeName, $ownValue)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $attributeName the presentation attribute
     * @param string $value         the attribute value
     *
     * @return bool true if the browser is known to accept the value, so that the element never falls back to the
     *              value of its parent
     */
    private function isKnownValidValue(string $attributeName, string $value): bool
    {
        if (\in_array($value, self::KEYWORD_VALUES[$attributeName] ?? [], true)) {
            return true;
        }

        return match (true) {
            \in_array($attributeName, self::NON_NEGATIVE_NUMBER_ATTRIBUTES, true) => 1 === preg_match(self::NUMBER_VALUE_REGEX, $value) && !str_starts_with($value, '-'),
            \in_array($attributeName, self::PAINT_ATTRIBUTES, true) => $this->isColorValue($value)
                || self::CURRENT_COLOR === $this->asciiLowercase($value)
                || 1 === preg_match(self::PAINT_URL_REGEX, $value),
            SvgAttribute::Color->value === $attributeName => $this->isColorValue($value),
            default => false,
        };
    }

    /**
     * @param string $value the attribute value
     *
     * @return bool true if the value is a hex color or a color name
     */
    private function isColorValue(string $value): bool
    {
        return 1 === preg_match(self::HEX_COLOR_REGEX, $value) || $this->isNamedColor($value);
    }
}
