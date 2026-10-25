<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEmptyAttributes implements PassInterface
{
    use CssDeclarationTrait;
    use ElementTrait;

    private const array MEANINGFUL_EMPTY_ATTRIBUTES = [
        SvgAttribute::Autofocus->value,
        SvgAttribute::Crossorigin->value,
        SvgAttribute::Download->value,
        SvgAttribute::Href->value,
        SvgAttribute::Lang->value,
        SvgAttribute::RequiredExtensions->value,
        SvgAttribute::RequiredFeatures->value,
        SvgAttribute::SystemLanguage->value,
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

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (!$this->isSvgElement($domNodeList) || $this->isAnimationElement($domNodeList)) {
                continue;
            }

            foreach (array_filter(iterator_to_array($domNodeList->attributes, false), $this->isMeaninglessWhenEmpty(...)) as $domAttr) {
                $candidates[] = [$domNodeList, $domAttr];
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
