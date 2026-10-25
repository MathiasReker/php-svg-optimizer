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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritedValueTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveIneffectiveStrokeAndFill implements PassInterface
{
    use AnimationTrait;
    use IdTrait;
    use IneffectiveAttributeTrait;
    use InheritedValueTrait;
    use StyleSheetTrait;

    private const string NONE = 'none';

    private const array STROKE_DETAILS = [
        Attribute::StrokeDasharray->value,
        Attribute::StrokeDashoffset->value,
        Attribute::StrokeLinecap->value,
        Attribute::StrokeLinejoin->value,
        Attribute::StrokeMiterlimit->value,
        Attribute::StrokeOpacity->value,
    ];

    private const array STROKE_DISABLERS = [
        Attribute::Stroke->value,
        Attribute::StrokeWidth->value,
    ];

    private const array FILL_DETAILS = [
        Attribute::FillOpacity->value,
        Attribute::FillRule->value,
    ];

    private const string HIT_TESTING_OR_MARKER_REGEX = '/marker|pointer-events/i';

    private const string ZERO_REGEX = '/^[+-]?(?:0+\.?0*|\.0+)(?:e[+-]?\d+)?$/i';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument) || $this->usesMarkersOrHitTesting($domDocument)) {
            return;
        }

        $animated = $this->animatedAttributeNames($domDocument);
        $domElements = $this->cleanableElements($domDocument);

        if (!$this->isAnimated($animated, [...self::STROKE_DISABLERS, ...self::STROKE_DETAILS])) {
            foreach ($domElements as $domElement) {
                $this->cleanStroke($domElement);
            }
        }

        if (!$this->isAnimated($animated, [Attribute::Fill->value, ...self::FILL_DETAILS])) {
            foreach ($domElements as $domElement) {
                $this->cleanFill($domElement);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<int, \DOMElement> the SVG elements that neither use `inherit` nor pass their values to referenced content
     */
    private function cleanableElements(\DOMDocument $domDocument): array
    {
        return array_filter(
            $this->svgContentElements($domDocument),
            fn (\DOMElement $domElement): bool => !$this->usesInheritInAnyAttribute($domElement)
                && !$this->usesReferencedContent($domElement),
        );
    }

    /**
     * @param \DOMElement $domElement the element to clean
     */
    private function cleanFill(\DOMElement $domElement): void
    {
        if ($this->isDisabled($domElement, Attribute::Fill->value)) {
            $this->removeUnanimatedAttributes($domElement, self::FILL_DETAILS, []);
        }
    }

    /**
     * @param \DOMElement $domElement the element to clean
     */
    private function cleanStroke(\DOMElement $domElement): void
    {
        $names = match (true) {
            $this->isDisabled($domElement, Attribute::Stroke->value) => [Attribute::StrokeWidth->value, ...self::STROKE_DETAILS],
            $this->isDisabled($domElement, Attribute::StrokeWidth->value) => self::STROKE_DETAILS,
            default => [],
        };

        $this->removeUnanimatedAttributes($domElement, $names, []);
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return bool true if markers, which scale with the stroke width, or explicit hit testing may be used
     */
    private function usesMarkersOrHitTesting(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->getElementsByTagName('*') as $element) {
            /** @var \DOMAttr $attribute */
            foreach ($element->attributes as $attribute) {
                $subject = Attribute::Style->value === $attribute->name ? $attribute->value : $attribute->name;

                if (1 === preg_match(self::HIT_TESTING_OR_MARKER_REGEX, $subject)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, true> $animated the names of all attributes targeted by animation elements
     * @param list<string>        $names    the attribute names to check
     */
    private function isAnimated(array $animated, array $names): bool
    {
        return [] !== array_intersect_key($animated, array_flip($names));
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element or a descendant is a `<use>` element, whose referenced content inherits from it
     */
    private function usesReferencedContent(\DOMElement $domElement): bool
    {
        if (Element::Use->value === $domElement->localName) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $element) {
            if (Element::Use->value === $element->localName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $property   the property that can disable the painting
     *
     * @return bool true if the property disables the painting of the element and of all its descendants
     */
    private function isDisabled(\DOMElement $domElement, string $property): bool
    {
        $value = $this->inheritedValue(
            $domElement,
            $property,
            fn (\DOMElement $domElement): bool => $this->inlineStyleDeclares($domElement, $property . '|all'),
            $this->hasId(...),
        );

        $disables = Attribute::StrokeWidth->value === $property
            ? 1 === preg_match(self::ZERO_REGEX, $value)
            : self::NONE === $value;

        return $disables && !$this->isOverriddenBelow($domElement, $property);
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $property   the inherited property
     *
     * @return bool true if a descendant sets the property itself
     */
    private function isOverriddenBelow(\DOMElement $domElement, string $property): bool
    {
        foreach ($domElement->getElementsByTagName('*') as $element) {
            if ($element->hasAttribute($property) || $this->inlineStyleDeclares($element, $property . '|all')) {
                return true;
            }
        }

        return false;
    }
}
