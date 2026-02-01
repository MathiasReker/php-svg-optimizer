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

/**
 * @no-named-arguments
 */
final readonly class RemoveEnableBackgroundAttribute implements SvgOptimizerRuleInterface
{
    /**
     * Regular expression to match the "enable-background" value format.
     * The format is: 'new 0 0 width height'.
     *
     * @see https://regex101.com/r/h0AgKK/1
     */
    private const string ENABLE_BACKGROUND_REGEX = '/^new\s0\s0\s([-+]?\d*\.?\d+([eE][-+]?\d+)?)\s([-+]?\d*\.?\d+([eE][-+]?\d+)?)$/';

    #[\Override]
    public static function isRisky(): bool
    {
        return true;
    }

    /**
     * Optimizes the given SVG document by removing or cleaning up the `enable-background` attribute.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $this->processEnableBackgroundAttributes($domXPath);
        $this->processStyleAttributes($domXPath);
    }

    /**
     * Processes the `enable-background` attribute on SVG, mask, and pattern elements.
     *
     * @param \DOMXPath $domXPath The \DOMXPath instance used to query the SVG elements
     */
    private function processEnableBackgroundAttributes(\DOMXPath $domXPath): void
    {
        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query('//*[@enable-background]');

        foreach ($elements as $element) {
            $enableBackgroundValue = $element->getAttribute(SvgAttribute::EnableBackground->value);
            $width = $element->getAttribute(SvgAttribute::Width->value);
            $height = $element->getAttribute(SvgAttribute::Height->value);
            $cleanedValue = $this->cleanupEnableBackgroundValue($enableBackgroundValue, $width, $height);

            if ('' === trim($cleanedValue)) {
                $element->removeAttribute(SvgAttribute::EnableBackground->value);
            } else {
                $element->setAttribute(SvgAttribute::EnableBackground->value, $cleanedValue);
            }
        }
    }

    /**
     * Cleans up the "enable-background" value by checking if it matches the width/height.
     *
     * @param string $value  The value of the enable-background attribute
     * @param string $width  The width of the element
     * @param string $height The height of the element
     *
     * @return string The cleaned up value, or empty if it is redundant
     */
    private function cleanupEnableBackgroundValue(string $value, string $width, string $height): string
    {
        if (1 !== preg_match(self::ENABLE_BACKGROUND_REGEX, $value, $matches)) {
            return $value;
        }

        return ($matches[1] === $width && $matches[3] === $height)
            ? ''
            : $value;
    }

    /**
     * Processes the `style` attribute and removes the `enable-background` property if present.
     *
     * @param \DOMXPath $domXPath The \DOMXPath instance used to query the SVG elements
     */
    private function processStyleAttributes(\DOMXPath $domXPath): void
    {
        /** @var \DOMNodeList<\DOMElement> $DomNodeList */
        $DomNodeList = $domXPath->query('//*[@style]');

        foreach ($DomNodeList as $domElement) {
            $style = $domElement->getAttribute(SvgAttribute::Style->value);

            if (!$this->hasEnableBackground($style)) {
                continue;
            }

            $cleanedStyle = $this->removeEnableBackgroundFromStyle($style);

            $this->updateStyleAttribute($domElement, $cleanedStyle);
        }
    }

    /**
     * Checks if the style contains the 'enable-background' property.
     *
     * @param string $style The style string
     *
     * @return bool True if 'enable-background' exists, otherwise false
     */
    private function hasEnableBackground(string $style): bool
    {
        return str_contains($style, SvgAttribute::EnableBackground->value);
    }

    /**
     * Removes the 'enable-background' property from the style string.
     *
     * @param string $style The original style string
     *
     * @return string The cleaned style string
     */
    private function removeEnableBackgroundFromStyle(string $style): string
    {
        return preg_replace('/\s*' . SvgAttribute::EnableBackground->value . '\s*:\s*[^;]+;\s*/', '', $style) ?? '';
    }

    /**
     * Updates the style attribute on the element.
     *
     * @param \DOMElement $domElement   The DOM element
     * @param string      $cleanedStyle The cleaned style string
     */
    private function updateStyleAttribute(\DOMElement $domElement, string $cleanedStyle): void
    {
        if ('' === trim($cleanedStyle)) {
            $domElement->removeAttribute(SvgAttribute::Style->value);
        } else {
            $domElement->setAttribute(SvgAttribute::Style->value, $cleanedStyle);
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
