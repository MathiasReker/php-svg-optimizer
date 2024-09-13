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
     * Regex pattern to match percentage values in transformations.
     *
     * @see https://regex101.com/r/JUBzng/1
     *
     * @var string
     */
    private const PERCENTAGE_REGEX = '/(\d+)%/';

    /**
     * Regex pattern to match identity translate transformations.
     *
     * @see https://regex101.com/r/WjV7Zr/1
     *
     * @var string
     */
    private const TRANSLATE_REGEX = '/\btranslate\(\s*0\s*(,\s*0\s*)?\)/';

    /**
     * Regex pattern to match identity scale transformations.
     *
     * @see https://regex101.com/r/wZi4DL/1
     *
     * @var string
     */
    private const SCALE_REGEX = '/\bscale\(\s*1\s*(,\s*1\s*)?\)/';

    /**
     * Regex pattern to match identity rotate transformations.
     *
     * @see https://regex101.com/r/2vmgRO/1
     *
     * @var string
     */
    private const ROTATE_REGEX = '/\brotate\(\s*0\s*\)/';

    /**
     * Regex pattern to match identity skewX transformations.
     *
     * @see https://regex101.com/r/83aNVu/1
     *
     * @var string
     */
    private const SKEW_X_REGEX = '/\bskewX\(\s*0\s*\)/';

    /**
     * Regex pattern to match identity skewY transformations.
     *
     * @see https://regex101.com/r/tiPsgQ/1
     *
     * @var string
     */
    private const SKEW_Y_REGEX = '/\bskewY\(\s*0\s*\)/';

    /**
     * Regex pattern to match multiple consecutive spaces.
     *
     * @see https://regex101.com/r/OuyK7V/1
     *
     * @var string
     */
    private const MULTIPLE_SPACES_REGEX = '/\s+/';

    /**
     * Regex pattern to match redundant commas.
     *
     * @see https://regex101.com/r/E8wfPk/1
     *
     * @var string
     */
    private const REDUNDANT_COMMAS_REGEX = '/\s*,\s*/';

    /**
     * Optimize the SVG document by minifying transformations.
     *
     * This method processes the `transform` attribute of all elements in the SVG document and performs the following optimizations:
     * - Converts percentage values to decimal numbers.
     * - Removes identity transformations such as `translate(0, 0)`, `scale(1, 1)`, `rotate(0)`, `skewX(0)`, and `skewY(0)`.
     * - Reduces multiple spaces to a single space.
     * - Removes redundant commas.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized.
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /**
         * @var \DOMNodeList<\DOMElement> $elements
         */
        $elements = $domXPath->query('//*[@transform]');

        foreach ($elements as $element) {
            $transform = $element->getAttribute('transform');

            // Convert percentages to numbers and minify the transform attribute
            $transform = $this->convertPercentagesToNumbers($transform);
            $transform = preg_replace(
                [
                    self::TRANSLATE_REGEX,
                    self::SCALE_REGEX,
                    self::ROTATE_REGEX,
                    self::SKEW_X_REGEX,
                    self::SKEW_Y_REGEX,
                    self::MULTIPLE_SPACES_REGEX,
                    self::REDUNDANT_COMMAS_REGEX,
                ],
                [
                    '',
                    '',
                    '',
                    '',
                    '',
                    ' ',
                    ',',
                ],
                $transform
            ) ?? '';

            $transform = trim($transform);

            if ('' === $transform || '0' === $transform) {
                $element->removeAttribute('transform');
            } else {
                $element->setAttribute('transform', $transform);
            }
        }
    }

    /**
     * Convert percentage values in transformations to decimal numbers.
     *
     * This method replaces percentage values in the transform attribute with their decimal equivalents.
     *
     * @param string $transform The transform attribute value to be processed.
     *
     * @return string The transformed value with percentages converted to decimals.
     */
    private function convertPercentagesToNumbers(string $transform): string
    {
        return preg_replace_callback(
            self::PERCENTAGE_REGEX,
            static fn (array $matches): string => (string) ((float) $matches[1] / 100),
            $transform
        ) ?? $transform;
    }
}
