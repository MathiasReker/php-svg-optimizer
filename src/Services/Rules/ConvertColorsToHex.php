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
     * Convert RGB colors to HEX colors in the SVG document.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        $attributes = ['fill', 'stroke'];

        foreach ($attributes as $attribute) {
            /**
             * @var \DOMNodeList<\DOMAttr> $nodeList
             */
            $nodeList = $domXPath->query('//@' . $attribute);

            foreach ($nodeList as $node) {
                /**
                 * @var \DOMAttr $node
                 */
                $value = $node->nodeValue;

                if (\is_string($value) && (bool) preg_match('/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/', $value, $matches)) {
                    if (!isset($matches[1], $matches[2], $matches[3])) {
                        continue;
                    }

                    $r = (int) $matches[1];
                    $g = (int) $matches[2];
                    $b = (int) $matches[3];

                    $hex = \sprintf('#%02X%02X%02X', $r, $g, $b);
                    $node->nodeValue = $hex;
                }
            }
        }
    }
}
