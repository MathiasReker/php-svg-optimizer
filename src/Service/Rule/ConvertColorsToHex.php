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

/**
 * @no-named-arguments
 */
final readonly class ConvertColorsToHex implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for RGB color values.
     *
     * This regular expression matches RGB color values in the format rgb(R, G, B).
     *
     * @see https://regex101.com/r/DUVXtz/1
     */
    private const string RGB_REGEX = '/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/';

    /**
     * Regex pattern for HEX color values.
     *
     * This regular expression matches both full (#RRGGBB) and shorthand (#RGB) HEX color values.
     *
     * @see https://regex101.com/r/wg9AQj/1
     */
    private const string HEX_REGEX = '/^#([a-fA-F0-9]{3,6})$/';

    /**
     * Minimum valid value for RGB components.
     */
    private const int MIN_RGB_VALUE = 0;

    /**
     * Maximum valid value for RGB components.
     */
    private const int MAX_RGB_VALUE = 255;

    /**
     * List of color attributes to process.
     */
    private const array COLOR_ATTRIBUTES = [
        'fill',
        'stroke',
        'color',
        'stop-color',
        'flood-color',
        'lighting-color',
        'solid-color',
        'background-color',
        'border-color',
    ];

    /**
     * Constant for bitwise shift when converting RGB to shorthand HEX.
     */
    private const int BITWISE_SHIFT = 4;

    /**
     * Convert RGB color values to shorthand HEX colors if possible.
     *
     * This method processes the SVG document to find and convert RGB colors to HEX format.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $nodeList */
        $nodeList = $domXPath->query(
            \sprintf(
                '//*[%s]',
                implode(
                    ' or ',
                    array_map(
                        static fn (string $attribute): string => \sprintf('contains(@style, "%s")', $attribute),
                        self::COLOR_ATTRIBUTES
                    )
                )
            )
        );

        $this->processStyleAttributes($nodeList);

        foreach (self::COLOR_ATTRIBUTES as $attribute) {
            /** @var \DOMNodeList<\DOMElement> $nodeList */
            $nodeList = $domXPath->query('//@' . $attribute);

            $this->processNodeList($nodeList);
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Process style attributes containing color values.
     *
     * This method processes the style attributes of the given \DOMNodeList to find and convert RGB colors to HEX format.
     *
     * @param \DOMNodeList<\DOMElement> $domNodeList The \DOMNodeList instance containing the nodes to be processed
     */
    private function processStyleAttributes(\DOMNodeList $domNodeList): void
    {
        foreach ($domNodeList as $node) {
            $styleValue = $node->getAttribute('style');
            $styleValue = $this->processColorAttributesInStyle($styleValue);

            $styleValue = preg_replace_callback(
                self::HEX_REGEX,
                static fn (array $matches): string => mb_strtolower($matches[0]),
                $styleValue
            );

            if (\is_string($styleValue)) {
                $node->setAttribute('style', $styleValue);
            }
        }
    }

    /**
     * Process color attributes in style values.
     *
     * This method processes the given style value to find and convert RGB colors to HEX format.
     *
     * @param string $styleValue The style value to process
     *
     * @return string The processed style value
     */
    private function processColorAttributesInStyle(string $styleValue): string
    {
        foreach (self::COLOR_ATTRIBUTES as $attribute) {
            if (1 === preg_match('/\b' . preg_quote($attribute, '/') . '\s*:\s*([^;]+)/', $styleValue, $matches)) {
                $colorValue = trim($matches[1]);

                if ($this->isRgbColor($colorValue)) {
                    $convertedColor = $this->convertRgbToHex($colorValue);
                    $styleValue = str_replace($colorValue, $convertedColor, $styleValue);
                }

                $styleValue = str_replace($colorValue, mb_strtolower($colorValue), $styleValue);
            }
        }

        return $styleValue;
    }

    /**
     * Check if the given value is an RGB color.
     *
     * This method checks if the given value is an RGB color in the format rgb(R, G, B).
     *
     * @param string $value The value to check
     *
     * @return bool True if the value is an RGB color, false otherwise
     */
    private function isRgbColor(string $value): bool
    {
        return 1 === preg_match(self::RGB_REGEX, $value);
    }

    /**
     * Convert RGB color values to HEX format.
     *
     * This method converts the given RGB color value to HEX format.
     *
     * @param string $rgbValue The RGB color value to convert
     *
     * @return string The converted HEX color value
     */
    private function convertRgbToHex(string $rgbValue): string
    {
        preg_match(self::RGB_REGEX, $rgbValue, $matches);
        [$r, $g, $b] = array_map(intval(...), \array_slice($matches, 1));

        if (!$this->isValidRgbValue($r) || !$this->isValidRgbValue($g) || !$this->isValidRgbValue($b)) {
            return $rgbValue;
        }

        $hex = \sprintf('#%02x%02x%02x', $r, $g, $b);

        return $this->canBeShortened($hex)
            ? \sprintf('#%1x%1x%1x', $r >> self::BITWISE_SHIFT, $g >> self::BITWISE_SHIFT, $b >> self::BITWISE_SHIFT)
            : mb_strtolower($hex);
    }

    /**
     * Check if the given value is a valid RGB component.
     *
     * This method checks if the given value is a valid RGB component (0-255).
     *
     * @param int $value The value to check
     *
     * @return bool True if the value is a valid RGB component, false otherwise
     */
    private function isValidRgbValue(int $value): bool
    {
        return $value >= self::MIN_RGB_VALUE && $value <= self::MAX_RGB_VALUE;
    }

    /**
     * Check if the given HEX color value can be shortened.
     *
     * This method checks if the given HEX color value can be shortened to shorthand format.
     *
     * @param string $hex The HEX color value to check
     *
     * @return bool True if the HEX color value can be shortened, false otherwise
     */
    private function canBeShortened(string $hex): bool
    {
        return $hex[1] === $hex[2] && $hex[3] === $hex[4] && $hex[5] === $hex[6];
    }

    /**
     * Process nodes containing color values.
     *
     * This method processes the given \DOMNodeList to find and convert RGB colors to HEX format.
     *
     * @param \DOMNodeList<\DOMElement> $domNodeList The \DOMNodeList instance containing the nodes to be processed
     */
    private function processNodeList(\DOMNodeList $domNodeList): void
    {
        foreach ($domNodeList as $node) {
            $value = trim((string) $node->nodeValue);

            if ($this->isRgbColor($value)) {
                $node->nodeValue = $this->convertRgbToHex($value);
            } elseif ($this->isHexColor($value)) {
                $node->nodeValue = mb_strtolower($value);
            }
        }
    }

    /**
     * Check if the given value is a HEX color.
     *
     * This method checks if the given value is a HEX color in the format #RRGGBB.
     *
     * @param string $value The value to check
     *
     * @return bool True if the value is a HEX color, false otherwise
     */
    private function isHexColor(string $value): bool
    {
        return 1 === preg_match(self::HEX_REGEX, $value);
    }
}
