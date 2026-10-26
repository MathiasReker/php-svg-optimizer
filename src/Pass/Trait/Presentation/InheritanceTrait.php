<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Color\NamedColorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait InheritanceTrait
{
    use AnimationTrait;
    use AsciiCaseTrait;
    use NamedColorTrait;
    use NumberLiteralTrait;
    use StyleSheetTrait;

    private const string CURRENT_COLOR = 'currentcolor';

    private const array KEYWORD_VALUES = [
        Attribute::ClipRule->value => ['evenodd', 'nonzero'],
        Attribute::Fill->value => ['none'],
        Attribute::FillRule->value => ['evenodd', 'nonzero'],
        Attribute::Stroke->value => ['none'],
        Attribute::StrokeLinecap->value => ['butt', 'round', 'square'],
        Attribute::StrokeLinejoin->value => ['bevel', 'miter', 'round'],
        Attribute::TextAnchor->value => ['end', 'middle', 'start'],
        Attribute::Visibility->value => ['collapse', 'hidden', 'visible'],
    ];

    private const array NON_NEGATIVE_NUMBER_ATTRIBUTES = [
        Attribute::FillOpacity->value,
        Attribute::StrokeOpacity->value,
        Attribute::StrokeWidth->value,
    ];

    private const array PAINT_ATTRIBUTES = [
        Attribute::Fill->value,
        Attribute::Stroke->value,
    ];

    private const string PAINT_URL_REGEX = '/^url\(#[\w.-]+\)$/';

    private const string NUMBER_VALUE_REGEX = '/^' . self::NUMBER_PATTERN . '$/';

    private const array PARENT_RELATIVE_ATTRIBUTES = [
        Attribute::FontSize->value,
        Attribute::FontStretch->value,
        Attribute::FontWeight->value,
    ];

    private const string FONT_RELATIVE_UNIT_REGEX = '/[\d.]r?(?:em|ex|ch|cap|ic|lh)\b/i';

    private const string FONT_SHORTHAND_PROPERTIES = 'font|all';

    private const array TRANSFORM_ORIGIN_ATTRIBUTES = [
        Attribute::TransformOrigin->value,
        'transform-box',
    ];

    private const string TRANSFORM_PROPERTIES = 'transform(?:-origin|-box)?|translate|rotate|scale|offset(?:-[a-z]+)?|all';

    private const string INHERIT_VALUE = 'inherit';

    private const string PARENT_VALUE_REGEX = '/(?<![\w-])(?:inherit|unset|revert(?:-layer)?)(?![\w-])|var\(/i';

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the inline style of the element or an animation inside it uses `inherit`, which takes the
     *              value of the parent
     */
    private function usesInheritInStyleOrAnimation(\DOMElement $domElement): bool
    {
        if ($this->mayUseParentValue($domElement->getAttribute(Attribute::Style->value))) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $element) {
            if (!$this->isAnimationElement($element)) {
                continue;
            }

            foreach ([Attribute::By, Attribute::From, Attribute::To, Attribute::Values] as $animationAttribute) {
                if ($this->mayUseParentValue($element->getAttribute($animationAttribute->value))) {
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

        if (!$this->setsFontProperty($child, Attribute::FontSize->value)) {
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
        if (!$parent->hasAttribute(Attribute::Transform->value)) {
            return false;
        }

        return $this->hasAnyAttribute($child, self::TRANSFORM_ORIGIN_ATTRIBUTES)
            || $this->inlineStyleDeclares($child, self::TRANSFORM_PROPERTIES);
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
            || $this->inlineStyleDeclares($domElement, $property . '|' . self::FONT_SHORTHAND_PROPERTIES);
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

            if (\in_array($attribute->nodeName, Attribute::inheritedNames(), true)
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
            \in_array($attributeName, self::PAINT_ATTRIBUTES, true) => $this->isHexOrNamedColor($value)
                || self::CURRENT_COLOR === $this->asciiLowercase($value)
                || 1 === preg_match(self::PAINT_URL_REGEX, $value),
            Attribute::Color->value === $attributeName => $this->isHexOrNamedColor($value),
            default => false,
        };
    }

    /**
     * @param string $value an attribute value
     *
     * @return bool true if the value is the `inherit` keyword
     */
    private function isInheritKeyword(string $value): bool
    {
        return 0 === strcasecmp(trim($value), self::INHERIT_VALUE);
    }

    /**
     * @param string $value an attribute value, inline style or animation value
     *
     * @return bool true if the value may take the value of the parent, through `inherit`, `unset`, `revert` or a
     *              custom property that may be invalid
     */
    private function mayUseParentValue(string $value): bool
    {
        return 1 === preg_match(self::PARENT_VALUE_REGEX, $value);
    }
}
