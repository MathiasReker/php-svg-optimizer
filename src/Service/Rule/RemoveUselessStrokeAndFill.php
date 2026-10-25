<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PresentationAttributeCleanupTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUselessStrokeAndFill implements SvgOptimizerRuleInterface
{
    use PresentationAttributeCleanupTrait;

    private const string NONE = 'none';

    private const string UNKNOWN = '';

    private const array INITIAL_VALUES = [
        SvgAttribute::Fill->value => 'black',
        SvgAttribute::Stroke->value => self::NONE,
        SvgAttribute::StrokeWidth->value => '1',
    ];

    private const array STROKE_DETAILS = [
        SvgAttribute::StrokeDasharray->value,
        SvgAttribute::StrokeDashoffset->value,
        SvgAttribute::StrokeLinecap->value,
        SvgAttribute::StrokeLinejoin->value,
        SvgAttribute::StrokeMiterlimit->value,
        SvgAttribute::StrokeOpacity->value,
    ];

    private const array STROKE_DISABLERS = [
        SvgAttribute::Stroke->value,
        SvgAttribute::StrokeWidth->value,
    ];

    private const array FILL_DETAILS = [
        SvgAttribute::FillOpacity->value,
        SvgAttribute::FillRule->value,
    ];

    private const string HIT_TESTING_OR_MARKER_REGEX = '/marker|pointer-events/i';

    private const string ZERO_REGEX = '/^[+-]?(?:0+\.?0*|\.0+)(?:e[+-]?\d+)?$/i';

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument) || $this->usesMarkersOrHitTesting($domDocument)) {
            return;
        }

        $animated = $this->animatedAttributeNames($domDocument);
        $domElements = $this->cleanableElements($domDocument);

        if (!$this->isAnimated($animated, [...self::STROKE_DISABLERS, ...self::STROKE_DETAILS])) {
            foreach ($domElements as $domElement) {
                $this->cleanStroke($domElement);
            }
        }

        if (!$this->isAnimated($animated, [SvgAttribute::Fill->value, ...self::FILL_DETAILS])) {
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
            $this->svgElements($domDocument),
            fn (\DOMElement $domElement): bool => !$this->hasInheritValue($domElement)
                && !$this->usesReferencedContent($domElement),
        );
    }

    /**
     * @param \DOMElement $domElement the element to clean
     */
    private function cleanFill(\DOMElement $domElement): void
    {
        if ($this->isDisabled($domElement, SvgAttribute::Fill->value)) {
            $this->removeUnanimatedAttributes($domElement, self::FILL_DETAILS, []);
        }
    }

    /**
     * @param \DOMElement $domElement the element to clean
     */
    private function cleanStroke(\DOMElement $domElement): void
    {
        $names = match (true) {
            $this->isDisabled($domElement, SvgAttribute::Stroke->value) => [SvgAttribute::StrokeWidth->value, ...self::STROKE_DETAILS],
            $this->isDisabled($domElement, SvgAttribute::StrokeWidth->value) => self::STROKE_DETAILS,
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
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            /** @var \DOMAttr $attribute */
            foreach ($domNodeList->attributes as $attribute) {
                $subject = SvgAttribute::Style->value === $attribute->name ? $attribute->value : $attribute->name;

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
        if (SvgTag::Use->value === $domElement->localName) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if (SvgTag::Use->value === $domNodeList->localName) {
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
        $value = $this->effectiveValue($domElement, $property);

        $disables = SvgAttribute::StrokeWidth->value === $property
            ? 1 === preg_match(self::ZERO_REGEX, $value)
            : self::NONE === $value;

        return $disables && !$this->isOverriddenBelow($domElement, $property);
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $property   the inherited property
     *
     * @return string the trimmed value the element uses, or an empty string if it cannot be known
     */
    private function effectiveValue(\DOMElement $domElement, string $property): string
    {
        $node = $domElement;

        while (!$this->styleSetsProperty($node, $property)) {
            if ($node->hasAttribute($property)) {
                return trim($node->getAttribute($property));
            }

            $parent = $node->parentNode;
            if (!$parent instanceof \DOMElement) {
                return self::INITIAL_VALUES[$property];
            }

            if ($this->hasId($node)) {
                return self::UNKNOWN;
            }

            $node = $parent;
        }

        return self::UNKNOWN;
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $property   the inherited property
     *
     * @return bool true if a descendant sets the property itself
     */
    private function isOverriddenBelow(\DOMElement $domElement, string $property): bool
    {
        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if ($domNodeList->hasAttribute($property) || $this->styleSetsProperty($domNodeList, $property)) {
                return true;
            }
        }

        return false;
    }
}
