<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;

class ConvertColorsToHex implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for RGB color values.
     *
     * This regular expression matches RGB color values in the format rgb(R, G, B).
     *
     * @see https://regex101.com/r/DUVXtz/1
     *
     * @var string
     */
    private const RGB_REGEX = '/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/';

    /**
     * Regex pattern for HEX color values.
     *
     * This regular expression matches both full (#RRGGBB) and shorthand (#RGB) HEX color values.
     *
     * @see https://regex101.com/r/wg9AQj/1
     *
     * @var string
     */
    private const HEX_REGEX = '/^#([a-fA-F0-9]{3,6})$/';

    /**
     * Minimum valid value for RGB components.
     *
     * @var int
     */
    private const MIN_RGB_VALUE = 0;

    /**
     * Maximum valid value for RGB components.
     *
     * @var int
     */
    private const MAX_RGB_VALUE = 255;

    /**
     * Convert RGB color values to shorthand HEX colors if possible.
     *
     * This method processes the SVG document to find and convert RGB colors to HEX format.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $attributes = ['fill', 'stroke', 'color'];

        foreach ($attributes as $attribute) {
            $nodeList = $domXPath->query('//@' . $attribute);

            if ($nodeList instanceof \DOMNodeList) {
                $this->processNodeList($nodeList);
            }
        }
    }

    /**
     * Process each node in the node list and convert color values.
     *
     * This method updates the node values from RGB to HEX if applicable and ensures HEX values are in lowercase.
     *
     * @param \DOMNodeList<\DOMNode> $domNodeList The node list containing color attributes to be processed
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
     * Determine if a value is a valid RGB color string.
     *
     * @param string $value The value to check
     *
     * @return bool True if the value matches the RGB color pattern, false otherwise
     */
    private function isRgbColor(string $value): bool
    {
        return 1 === preg_match(self::RGB_REGEX, $value);
    }

    /**
     * Convert an RGB color string to a HEX color string.
     *
     * This method also attempts to shorten the HEX color if all RGB components are the same.
     *
     * @param string $rgbValue The RGB color string to convert
     *
     * @return string The corresponding HEX color string
     */
    private function convertRgbToHex(string $rgbValue): string
    {
        preg_match(self::RGB_REGEX, $rgbValue, $matches);
        [$r, $g, $b] = array_map('intval', \array_slice($matches, 1));

        if (!$this->isValidRgbValue($r) || !$this->isValidRgbValue($g) || !$this->isValidRgbValue($b)) {
            return $rgbValue;
        }

        $hex = mb_strtolower(\sprintf('#%02x%02x%02x', $r, $g, $b));

        return $this->canBeShortened($hex)
            ? \sprintf('#%1x%1x%1x', $r >> 4, $g >> 4, $b >> 4)
            : $hex;
    }

    /**
     * Validate if a given value is a valid RGB component (0-255).
     *
     * @param int $value The RGB component value to check
     *
     * @return bool True if the value is within the valid range, false otherwise
     */
    private function isValidRgbValue(int $value): bool
    {
        return $value >= self::MIN_RGB_VALUE && $value <= self::MAX_RGB_VALUE;
    }

    /**
     * Determine if a full #RRGGBB HEX color can be shortened to #RGB.
     *
     * @param string $hex The full #RRGGBB HEX color string to check
     *
     * @return bool True if the HEX color can be shortened, false otherwise
     */
    private function canBeShortened(string $hex): bool
    {
        return $hex[1] === $hex[2] && $hex[3] === $hex[4] && $hex[5] === $hex[6];
    }

    /**
     * Determine if a value is a valid HEX color string.
     *
     * @param string $value The value to check
     *
     * @return bool True if the value matches the HEX color pattern, false otherwise
     */
    private function isHexColor(string $value): bool
    {
        return 1 === preg_match(self::HEX_REGEX, $value);
    }
}
