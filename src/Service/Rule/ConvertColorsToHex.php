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
     * @see https://regex101.com/r/DUVXtz/1
     */
    private const string RGB_REGEX = '/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/';

    /**
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
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $colorAttributes = SvgAttribute::colors();
        $stylePattern = $this->buildStylePattern($colorAttributes);
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $element) {
            if ($element->hasAttribute(SvgAttribute::Style->value)) {
                $style = $element->getAttribute(SvgAttribute::Style->value);
                $style = $this->processStyle($style, $stylePattern);
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
     * @param list<string> $colorAttributes a list of color-related CSS properties
     *
     * @return string the regex pattern matching those properties in a style declaration
     */
    private function buildStylePattern(array $colorAttributes): string
    {
        $attributes = array_map(
            static fn (string $a): string => preg_quote($a, '/'),
            $colorAttributes
        );

        return '/\b(' . implode('|', $attributes) . ')\s*:\s*([^;]+)/i';
    }

    /**
     * @param string $style   the inline style string
     * @param string $pattern the regex pattern matching color-related CSS properties
     *
     * @return string the processed style string with converted colors
     */
    private function processStyle(string $style, string $pattern): string
    {
        return preg_replace_callback(
            $pattern,
            fn (array $m): string => $m[1] . ':' . $this->convertColorValue(trim($m[2])),
            $style
        ) ?? $style;
    }

    /**
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
