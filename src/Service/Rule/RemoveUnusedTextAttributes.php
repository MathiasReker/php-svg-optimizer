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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ParentValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PresentationAttributeCleanupTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedTextAttributes implements SvgOptimizerRuleInterface
{
    use ParentValueTrait;
    use PresentationAttributeCleanupTrait;

    private const array TEXT_ATTRIBUTES = [
        SvgAttribute::AlignmentBaseline->value,
        SvgAttribute::BaselineShift->value,
        SvgAttribute::Direction->value,
        SvgAttribute::DominantBaseline->value,
        SvgAttribute::Font->value,
        SvgAttribute::FontFamily->value,
        SvgAttribute::FontSize->value,
        SvgAttribute::FontSizeAdjust->value,
        SvgAttribute::FontStretch->value,
        SvgAttribute::FontStyle->value,
        SvgAttribute::FontVariant->value,
        SvgAttribute::FontWeight->value,
        SvgAttribute::GlyphOrientationHorizontal->value,
        SvgAttribute::GlyphOrientationVertical->value,
        SvgAttribute::Kerning->value,
        SvgAttribute::LetterSpacing->value,
        SvgAttribute::TextAnchor->value,
        SvgAttribute::TextDecoration->value,
        SvgAttribute::TextRendering->value,
        SvgAttribute::UnicodeBidi->value,
        SvgAttribute::WhiteSpace->value,
        SvgAttribute::WordSpacing->value,
        SvgAttribute::WritingMode->value,
    ];

    private const array VIEWPORT_ATTRIBUTES = [
        SvgAttribute::Direction->value,
        SvgAttribute::UnicodeBidi->value,
        SvgAttribute::WritingMode->value,
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
        if ($this->hasStyleElement($domDocument) || $this->usesFontRelativeUnit($domDocument)) {
            return;
        }

        $animated = $this->animatedAttributeNames($domDocument);

        foreach ($this->svgElements($domDocument) as $domElement) {
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

        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if (!$this->isTextless($domNodeList)) {
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
