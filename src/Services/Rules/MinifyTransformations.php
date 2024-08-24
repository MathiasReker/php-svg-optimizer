<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

class MinifyTransformations implements SvgOptimizerRuleInterface
{
    /**
     * Optimize the given SVG document by minifying transformations.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /**
         * @var \DOMNodeList<\DOMElement> $elements
         */
        $elements = $domXPath->query('//*[@transform]');

        /**
         * @var \DOMElement $element
         */
        foreach ($elements as $element) {
            $transform = $element->getAttribute('transform');

            // Convert percentages to numbers
            $transform = $this->convertPercentagesToNumbers($transform);

            // Remove identity transforms with more flexible regex
            $transform = (string) preg_replace('/\btranslate\(\s*0\s*(,\s*0\s*)?\)/', '', $transform);
            $transform = (string) preg_replace('/\bscale\(\s*1\s*(,\s*1\s*)?\)/', '', $transform);
            $transform = (string) preg_replace('/\brotate\(\s*0\s*\)/', '', $transform);
            $transform = (string) preg_replace('/\bskewX\(\s*0\s*\)/', '', $transform);
            $transform = (string) preg_replace('/\bskewY\(\s*0\s*\)/', '', $transform);

            // Remove multiple spaces, redundant commas, and trim
            $transform = (string) preg_replace('/\s+/', ' ', $transform);
            $transform = (string) preg_replace('/\s*,\s*/', ',', $transform);
            $transform = trim($transform);

            // Remove the transform attribute if it's empty after optimization
            if ('' === $transform || '0' === $transform) {
                $element->removeAttribute('transform');
            } else {
                $element->setAttribute('transform', $transform);
            }
        }
    }

    /**
     * Convert percentage values in transformations to decimal numbers.
     */
    private function convertPercentagesToNumbers(string $transform): string
    {
        $result = preg_replace_callback(
            '/(\d+)%/',
            fn ($matches): string => (string) ((float) $matches[1] / 100),
            $transform
        );

        // Ensure the result is always a string, even if preg_replace_callback returns null
        return $result ?? $transform;
    }
}
