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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class FlattenGroups implements SvgOptimizerRuleInterface
{
    use ElementTrait;

    private const string XPATH_GROUP_ELEMENTS = '//svg:g';

    private const string INHERIT_KEYWORD = 'inherit';

    private const string XPATH_TRANSFORM_BLOCKING_ELEMENTS = '//svg:g/svg:svg'
        . ' | //*[svg:animateMotion or *[normalize-space(@attributeName) = "transform"]]';

    private const array INHERITED_ATTRIBUTES = [
        SvgAttribute::ClipRule->value,
        SvgAttribute::Color->value,
        SvgAttribute::ColorInterpolation->value,
        SvgAttribute::ColorInterpolationFilters->value,
        SvgAttribute::ColorRendering->value,
        SvgAttribute::Cursor->value,
        SvgAttribute::Direction->value,
        SvgAttribute::Fill->value,
        SvgAttribute::FillOpacity->value,
        SvgAttribute::FillRule->value,
        SvgAttribute::FontFamily->value,
        SvgAttribute::FontSize->value,
        SvgAttribute::FontSizeAdjust->value,
        SvgAttribute::FontStretch->value,
        SvgAttribute::FontStyle->value,
        SvgAttribute::FontVariant->value,
        SvgAttribute::FontWeight->value,
        SvgAttribute::GlyphOrientationHorizontal->value,
        SvgAttribute::GlyphOrientationVertical->value,
        SvgAttribute::ImageRendering->value,
        SvgAttribute::Kerning->value,
        SvgAttribute::LetterSpacing->value,
        SvgAttribute::MarkerEnd->value,
        SvgAttribute::MarkerMid->value,
        SvgAttribute::MarkerStart->value,
        SvgAttribute::PaintOrder->value,
        SvgAttribute::PointerEvents->value,
        SvgAttribute::ShapeRendering->value,
        SvgAttribute::Stroke->value,
        SvgAttribute::StrokeDasharray->value,
        SvgAttribute::StrokeDashoffset->value,
        SvgAttribute::StrokeLinecap->value,
        SvgAttribute::StrokeLinejoin->value,
        SvgAttribute::StrokeMiterlimit->value,
        SvgAttribute::StrokeOpacity->value,
        SvgAttribute::StrokeWidth->value,
        SvgAttribute::TextAnchor->value,
        SvgAttribute::TextRendering->value,
        SvgAttribute::Visibility->value,
        SvgAttribute::WordSpacing->value,
        SvgAttribute::WritingMode->value,
    ];

    private const array PARENT_RELATIVE_ATTRIBUTES = [
        SvgAttribute::FontSize->value,
        SvgAttribute::FontStretch->value,
        SvgAttribute::FontWeight->value,
    ];

    private const string FONT_RELATIVE_UNIT_REGEX = '/[\d.](?:em|ex|ch)\b/i';

    private const array NON_RENDERED_TAGS = [
        SvgTag::ClipPath->value,
        SvgTag::Defs->value,
        SvgTag::Filter->value,
        SvgTag::LinearGradient->value,
        SvgTag::Marker->value,
        SvgTag::Mask->value,
        SvgTag::Pattern->value,
        SvgTag::RadialGradient->value,
        SvgTag::Symbol->value,
    ];

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return true;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Svg->prefix(), SvgNamespace::Svg->value);

        /** @var \DOMNodeList<\DOMElement> $domElementList */
        $domElementList = $domXPath->query(self::XPATH_GROUP_ELEMENTS);
        $transformBlockingElements = $this->transformBlockingElements($domDocument, $domXPath);

        foreach ($domElementList as $domElement) {
            $parentNode = $domElement->parentNode;

            if (!$parentNode instanceof \DOMElement || !$this->canFlatten($domElement, $parentNode, $transformBlockingElements)) {
                continue;
            }

            $this->applyGroupAttributesToChildren($domElement);
            $this->flattenGroup($domElement, $parentNode);
        }
    }

    /**
     * @param \DOMElement       $domElement                the group element
     * @param \DOMElement       $parentNode                the parent of the group element
     * @param list<\DOMElement> $transformBlockingElements the elements that a group transform cannot move onto
     */
    private function canFlatten(\DOMElement $domElement, \DOMElement $parentNode, array $transformBlockingElements): bool
    {
        if (SvgTag::Switch->value === $parentNode->localName) {
            return false;
        }

        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if (!$this->isTransferableAttribute($attribute)) {
                return false;
            }
        }

        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement && $this->dependsOnGroup($domElement, $child, $transformBlockingElements)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMAttr $domAttr the group attribute
     */
    private function isTransferableAttribute(\DOMAttr $domAttr): bool
    {
        return SvgAttribute::Transform->value === $domAttr->nodeName
            || \in_array($domAttr->nodeName, self::INHERITED_ATTRIBUTES, true);
    }

    /**
     * @param \DOMElement       $domElement                the group element
     * @param \DOMElement       $child                     the child element
     * @param list<\DOMElement> $transformBlockingElements the elements that a group transform cannot move onto
     */
    private function dependsOnGroup(\DOMElement $domElement, \DOMElement $child, array $transformBlockingElements): bool
    {
        if (\in_array(mb_strtolower($child->localName ?? ''), SvgTag::animation(), true)) {
            return true;
        }

        if (str_contains(mb_strtolower($child->getAttribute(SvgAttribute::Style->value)), self::INHERIT_KEYWORD)) {
            return true;
        }

        if ($domElement->hasAttribute(SvgAttribute::Transform->value) && \in_array($child, $transformBlockingElements, true)) {
            return true;
        }

        return $this->dependsOnGroupFont($domElement, $child);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     * @param \DOMXPath    $domXPath    the XPath object with the svg prefix registered
     *
     * @return list<\DOMElement> the elements that a group transform cannot move onto
     */
    private function transformBlockingElements(\DOMDocument $domDocument, \DOMXPath $domXPath): array
    {
        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_TRANSFORM_BLOCKING_ELEMENTS);

        return [...iterator_to_array($domNodeList, false), ...$this->animationTargets($domDocument)];
    }

    /**
     * @param \DOMElement $domElement the group element
     * @param \DOMElement $child      the child element
     */
    private function dependsOnGroupFont(\DOMElement $domElement, \DOMElement $child): bool
    {
        foreach (self::PARENT_RELATIVE_ATTRIBUTES as $attributeName) {
            if ($domElement->hasAttribute($attributeName) && $child->hasAttribute($attributeName)) {
                return true;
            }
        }

        if (!$child->hasAttribute(SvgAttribute::FontSize->value)
            && !str_contains(mb_strtolower($child->getAttribute(SvgAttribute::Style->value)), SvgAttribute::FontSize->value)
        ) {
            return false;
        }

        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if (1 === preg_match(self::FONT_RELATIVE_UNIT_REGEX, $attribute->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the group element
     */
    private function applyGroupAttributesToChildren(\DOMElement $domElement): void
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->applyAttributesToChild($domElement, $child);
            }
        }
    }

    /**
     * @param \DOMElement $parent the parent element
     * @param \DOMElement $child  the child element
     */
    private function applyAttributesToChild(\DOMElement $parent, \DOMElement $child): void
    {
        /** @var \DOMAttr $attribute */
        foreach ($parent->attributes as $attribute) {
            if (SvgAttribute::Transform->value === $attribute->nodeName) {
                if (!\in_array($child->localName, self::NON_RENDERED_TAGS, true)) {
                    $this->prependTransform($child, $attribute->value);
                }

                continue;
            }

            $this->setInheritedAttribute($child, $attribute);
        }
    }

    /**
     * @param \DOMElement $domElement the element to modify
     * @param \DOMAttr    $domAttr    the inherited attribute to set
     */
    private function setInheritedAttribute(\DOMElement $domElement, \DOMAttr $domAttr): void
    {
        $ownValue = $domElement->getAttribute($domAttr->nodeName);

        if (!$domElement->hasAttribute($domAttr->nodeName) || self::INHERIT_KEYWORD === mb_strtolower(trim($ownValue))) {
            $domElement->setAttribute($domAttr->nodeName, $domAttr->value);
        }
    }

    /**
     * @param \DOMElement $domElement the child element
     * @param string      $transform  the group transform, applied before the child's own transform
     */
    private function prependTransform(\DOMElement $domElement, string $transform): void
    {
        $newTransform = $this->combineTransforms($transform, $domElement->getAttribute(SvgAttribute::Transform->value));

        if ('' !== $newTransform) {
            $domElement->setAttribute(SvgAttribute::Transform->value, $newTransform);
        }
    }

    /**
     * @param string $groupTransform the transform of the group
     * @param string $childTransform the transform of the child
     *
     * @return string the combined transform
     */
    private function combineTransforms(string $groupTransform, string $childTransform): string
    {
        $transforms = array_filter(
            [trim($groupTransform), trim($childTransform)],
            static fn (string $transform): bool => '' !== $transform,
        );

        return implode(' ', $transforms);
    }

    /**
     * @param \DOMElement $domElement the group element to flatten
     * @param \DOMElement $parentNode the parent element
     */
    private function flattenGroup(\DOMElement $domElement, \DOMElement $parentNode): void
    {
        $children = iterator_to_array($domElement->childNodes, false);
        foreach ($children as $child) {
            $parentNode->insertBefore($child, $domElement);
        }

        $parentNode->removeChild($domElement);
    }
}
