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

class MinifyTransformations implements SvgOptimizerRuleInterface
{
    /**
     * Regex for percentage values in transformations.
     *
     * @see https://regex101.com/r/JUBzng/1
     *
     * @var string
     */
    private const PERCENTAGE_REGEX = '/(\d+)%/';

    /**
     * Regex for identity translate transformations.
     *
     * @see https://regex101.com/r/WjV7Zr/1
     *
     * @var string
     */
    private const TRANSLATE_REGEX = '/\btranslate\(\s*0\s*(,\s*0\s*)?\)/';

    /**
     * Regex for identity scale transformations.
     *
     * @see https://regex101.com/r/wZi4DL/1
     *
     * @var string
     */
    private const SCALE_REGEX = '/\bscale\(\s*1\s*(,\s*1\s*)?\)/';

    /**
     * Regex for identity rotate, skewX, and skewY transformations.
     *
     * @see https://regex101.com/r/2vmgRO/1
     *
     * @var string
     */
    private const ROTATE_REGEX = '/\brotate\(\s*0\s*\)/';

    /**
     * Regex for identity skewX transformations.
     *
     * @see https://regex101.com/r/83aNVu/1
     *
     * @var string
     */
    private const SKEW_X_REGEX = '/\bskewX\(\s*0\s*\)/';

    /**
     * Regex for identity skewY transformations.
     *
     * @see https://regex101.com/r/tiPsgQ/1
     *
     * @var string
     */
    private const SKEW_Y_REGEX = '/\bskewY\(\s*0\s*\)/';

    /**
     * Regex for multiple spaces.
     *
     * @see https://regex101.com/r/OuyK7V/1
     *
     * @var string
     */
    private const MULTIPLE_SPACES_REGEX = '/\s+/';

    /**
     * Regex for redundant commas.
     *
     * @see https://regex101.com/r/E8wfPk/1
     *
     * @var string
     */
    private const REDUNDANT_COMMAS_REGEX = '/\s*,\s*/';

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
            $transform = (string) preg_replace(self::TRANSLATE_REGEX, '', $transform);
            $transform = (string) preg_replace(self::SCALE_REGEX, '', $transform);
            $transform = (string) preg_replace(self::ROTATE_REGEX, '', $transform);
            $transform = (string) preg_replace(self::SKEW_X_REGEX, '', $transform);
            $transform = (string) preg_replace(self::SKEW_Y_REGEX, '', $transform);

            // Remove multiple spaces, redundant commas, and trim
            $transform = (string) preg_replace(self::MULTIPLE_SPACES_REGEX, ' ', $transform);
            $transform = (string) preg_replace(self::REDUNDANT_COMMAS_REGEX, ',', $transform);
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
            self::PERCENTAGE_REGEX,
            fn (array $matches): string => (string) ((float) $matches[1] / 100),
            $transform
        );

        // Ensure the result is always a string, even if preg_replace_callback returns null
        return $result ?? $transform;
    }
}
