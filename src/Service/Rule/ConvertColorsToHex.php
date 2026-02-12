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
     * Converts color values to a more compact hexadecimal format.
     *
     * This rule processes all elements in the SVG, converting `rgb()` color
     * values to their hexadecimal equivalents (e.g., `#RRGGBB`). It also
     * shortens them where possible (e.g., `#RGB`) and normalizes existing
     * hex values to lowercase. This is applied to both color attributes and
     * inline `style` properties.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $colorAttributes = SvgAttribute::colors();
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $element) {
            if ($element->hasAttribute(SvgAttribute::Style->value)) {
                $style = $element->getAttribute(SvgAttribute::Style->value);
                $style = $this->processStyle($style, $colorAttributes);
                $element->setAttribute(SvgAttribute::Style->value, $style);
            }

            foreach ($colorAttributes as $colorAttribute) {
                if (!$element->hasAttribute($colorAttribute)) {
                    continue;
                }

                $value = trim($element->getAttribute($colorAttribute));
                $element->setAttribute($colorAttribute, $this->convertColorValue($value));
            }
        }
    }

    /**
     * Processes a `style` attribute string to convert color values.
     *
     * This method finds color-related properties within the style string and
     * applies the `convertColorValue` transformation to their values.
     *
     * @param string       $style           the inline style string
     * @param list<string> $colorAttributes a list of color-related CSS properties
     *
     * @return string the processed style string with converted colors
     */
    private function processStyle(string $style, array $colorAttributes): string
    {
        $attributes = array_map(
            static fn (string $a): string => preg_quote($a, '/'),
            $colorAttributes
        );

        $pattern = '/\b(' . implode('|', $attributes) . ')\s*:\s*([^;]+)/i';

        return preg_replace_callback(
            $pattern,
            fn (array $m): string => $m[1] . ':' . $this->convertColorValue(trim($m[2])),
            $style
        ) ?? $style;
    }

    /**
     * Converts a single color value to its hexadecimal representation.
     *
     * This method handles `rgb()` values, converting them to `#RRGGBB` or `#RGB`
     * format. It also normalizes existing hexadecimal colors to lowercase.
     * Other color formats are returned unchanged.
     *
     * @param string $value the color value to convert
     *
     * @return string the converted hexadecimal color or the original value
     */
    private function convertColorValue(string $value): string
    {
        if (1 === preg_match(self::RGB_REGEX, $value, $matches)) {
            [$r, $g, $b] = array_map(intval(...), \array_slice($matches, 1));

            if ($r < 0 || $r > 255 || $g < 0 || $g > 255 || $b < 0 || $b > 255) {
                return $value;
            }

            $hex = \sprintf('#%02x%02x%02x', $r, $g, $b);

            if ($hex[1] === $hex[2] && $hex[3] === $hex[4] && $hex[5] === $hex[6]) {
                return \sprintf('#%1x%1x%1x', $r >> 4, $g >> 4, $b >> 4);
            }

            return $hex;
        }

        if (1 === preg_match(self::HEX_REGEX, $value)) {
            return mb_strtolower($value);
        }

        return $value;
    }
}
