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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\IneffectiveAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveIneffectiveTextAttributes implements PassInterface
{
    use AnimationTrait;
    use ElementTrait;
    use IneffectiveAttributeTrait;
    use InheritanceTrait;
    use StyleSheetTrait;

    private const array TEXT_ATTRIBUTES = [
        Attribute::AlignmentBaseline->value,
        Attribute::BaselineShift->value,
        Attribute::Direction->value,
        Attribute::DominantBaseline->value,
        Attribute::Font->value,
        Attribute::FontFamily->value,
        Attribute::FontSize->value,
        Attribute::FontSizeAdjust->value,
        Attribute::FontStretch->value,
        Attribute::FontStyle->value,
        Attribute::FontVariant->value,
        Attribute::FontWeight->value,
        Attribute::GlyphOrientationHorizontal->value,
        Attribute::GlyphOrientationVertical->value,
        Attribute::Kerning->value,
        Attribute::LetterSpacing->value,
        Attribute::TextAnchor->value,
        Attribute::TextDecoration->value,
        Attribute::TextRendering->value,
        Attribute::UnicodeBidi->value,
        Attribute::WhiteSpace->value,
        Attribute::WordSpacing->value,
        Attribute::WritingMode->value,
    ];

    private const array VIEWPORT_ATTRIBUTES = [
        Attribute::Direction->value,
        Attribute::UnicodeBidi->value,
        Attribute::WritingMode->value,
    ];

    private const array TEXTLESS_ELEMENTS = [
        'a', 'animate', 'animateColor', 'animateMotion', 'animateTransform', 'circle', 'clipPath', 'defs', 'desc',
        'ellipse', 'feBlend', 'feColorMatrix', 'feComponentTransfer', 'feComposite', 'feConvolveMatrix',
        'feDiffuseLighting', 'feDisplacementMap', 'feDistantLight', 'feDropShadow', 'feFlood', 'feFuncA', 'feFuncB',
        'feFuncG', 'feFuncR', 'feGaussianBlur', 'feMerge', 'feMergeNode', 'feMorphology', 'feOffset',
        'fePointLight', 'feSpecularLighting', 'feSpotLight', 'feTile', 'feTurbulence', 'filter', 'g', 'image',
        'line', 'linearGradient', 'marker', 'mask', 'metadata', 'mpath', 'path', 'pattern', 'polygon', 'polyline',
        'radialGradient', 'rect', 'set', 'stop', 'svg', 'switch', 'symbol', 'title', 'view',
    ];

    private const string XPATH_ALL_ATTRIBUTES = '//@*';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument) || $this->usesFontRelativeUnit($domDocument)) {
            return;
        }

        $animated = $this->animatedAttributeNames($domDocument);

        foreach ($this->svgContentElements($domDocument) as $domElement) {
            if ($this->isTextlessSubtree($domElement)) {
                $this->removeUnanimatedAttributes(
                    $domElement,
                    $domElement === $domDocument->documentElement ? array_diff(self::TEXT_ATTRIBUTES, self::VIEWPORT_ATTRIBUTES) : self::TEXT_ATTRIBUTES,
                    $animated,
                );
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return bool true if a length may depend on the font of its element
     */
    private function usesFontRelativeUnit(\DOMDocument $domDocument): bool
    {
        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = (new \DOMXPath($domDocument))->query(self::XPATH_ALL_ATTRIBUTES);

        foreach ($attributes as $attribute) {
            if (1 === preg_match(self::FONT_RELATIVE_UNIT_REGEX, $attribute->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if neither the element nor a descendant can render text or pass its font on
     */
    private function isTextlessSubtree(\DOMElement $domElement): bool
    {
        if (!$this->isTextless($domElement)) {
            return false;
        }

        foreach ($domElement->getElementsByTagName('*') as $element) {
            if (!$this->isTextless($element)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isTextless(\DOMElement $domElement): bool
    {
        return $this->isSvgElement($domElement)
            && \in_array($domElement->localName, self::TEXTLESS_ELEMENTS, true);
    }
}
