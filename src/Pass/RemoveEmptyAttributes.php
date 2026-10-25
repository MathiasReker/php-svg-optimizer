<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEmptyAttributes implements PassInterface
{
    use AnimationTrait;
    use ElementTrait;
    use SelectorTrait;
    use StyleSheetTrait;

    private const array MEANINGFUL_EMPTY_ATTRIBUTES = [
        Attribute::Autofocus->value,
        Attribute::Crossorigin->value,
        Attribute::Download->value,
        Attribute::Href->value,
        Attribute::Lang->value,
        Attribute::RequiredExtensions->value,
        Attribute::RequiredFeatures->value,
        Attribute::SystemLanguage->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasExternalStyleSheet($domDocument)) {
            return;
        }

        $css = $this->styleSheetText($domDocument);

        $candidates = $this->emptyAttributes($domDocument);
        $selectedNames = [];

        foreach ($candidates as [, $domAttr]) {
            $selectedNames[$domAttr->name] ??= $this->hasAttributeSelector($css, $domAttr->name);
        }

        foreach ($candidates as [$domElement, $domAttr]) {
            if (!$selectedNames[$domAttr->name]) {
                $domElement->removeAttributeNode($domAttr);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return list<array{\DOMElement, \DOMAttr}> the empty attributes of SVG elements that carry no meaning when empty,
     *                                            with their elements
     */
    private function emptyAttributes(\DOMDocument $domDocument): array
    {
        $candidates = [];

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if (!$this->isSvgElement($element) || $this->isAnimationElement($element)) {
                continue;
            }

            foreach (array_filter(iterator_to_array($element->attributes, false), $this->isMeaninglessWhenEmpty(...)) as $domAttr) {
                $candidates[] = [$element, $domAttr];
            }
        }

        return $candidates;
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     */
    private function isMeaninglessWhenEmpty(\DOMAttr $domAttr): bool
    {
        return '' === trim($domAttr->value) && !\in_array($domAttr->localName, self::MEANINGFUL_EMPTY_ATTRIBUTES, true);
    }
}
