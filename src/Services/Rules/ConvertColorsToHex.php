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

class ConvertColorsToHex implements SvgOptimizerRuleInterface
{
    /**
     * The minimum RGB value.
     *
     * @var int
     */
    private const MIN_RGB_VALUE = 0;

    /**
     * The maximum RGB value.
     *
     * @var int
     */
    private const MAX_RGB_VALUE = 255;

    /**
     * Convert RGB colors to shorthand HEX colors in the SVG document, if possible,
     * and convert the result to lowercase. Invalid RGB values are ignored.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        $attributes = ['fill', 'stroke'];

        foreach ($attributes as $attribute) {
            $nodeList = $domXPath->query('//@' . $attribute);

            // Check if $nodeList is a valid DOMNodeList
            if ($nodeList instanceof \DOMNodeList) {
                foreach ($nodeList as $node) {
                    if ($node instanceof \DOMAttr) {
                        $value = $node->nodeValue;

                        if (!\is_string($value)) {
                            continue;
                        }

                        $value = trim($value);

                        $matches = [];
                        if (1 === preg_match('/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/', $value, $matches)) {
                            $r = (int) $matches[1];
                            $g = (int) $matches[2];
                            $b = (int) $matches[3];

                            if ($this->isValidRgbValue($r) && $this->isValidRgbValue($g) && $this->isValidRgbValue($b)) {
                                $hex = mb_strtolower(\sprintf('#%02x%02x%02x', $r, $g, $b));

                                if ($this->canBeShortened($hex)) {
                                    $hex = mb_strtolower(\sprintf('#%1x%1x%1x', $r >> 4, $g >> 4, $b >> 4));
                                }

                                $node->nodeValue = $hex;
                            }
                        } elseif (1 === preg_match('/^#([a-fA-F0-9]{6})$/', $value) || 1 === preg_match('/^#([a-fA-F0-9]{3})$/', $value)) {
                            $node->nodeValue = mb_strtolower($value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Validate if a given value is a valid RGB component (0-255).
     *
     * @param int $value The RGB component value
     *
     * @return bool true if valid, false otherwise
     */
    private function isValidRgbValue(int $value): bool
    {
        return $value >= self::MIN_RGB_VALUE && $value <= self::MAX_RGB_VALUE;
    }

    /**
     * Check if a full #RRGGBB hex color can be shortened to #RGB.
     *
     * @param string $hex the full #RRGGBB hex string
     *
     * @return bool true if it can be shortened, false otherwise
     */
    private function canBeShortened(string $hex): bool
    {
        return $hex[1] === $hex[2] && $hex[3] === $hex[4] && $hex[5] === $hex[6];
    }
}
