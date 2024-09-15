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

final class MinifySvgCoordinates implements SvgOptimizerRuleInterface
{
    /**
     * Regular expression pattern to remove unnecessary trailing zeroes in decimal numbers.
     *
     * @see https://regex101.com/r/bQpK9Q/1
     *
     * @var string
     */
    private const TRAILING_ZEROES_REGEX = '/(\.\d*?)0+(\D|$)/';

    /**
     * Regular expression pattern to remove unnecessary decimal point if there are no digits after it.
     *
     * @see https://regex101.com/r/zEFuoB/1
     *
     * @var string
     */
    private const UNNECESSARY_DECIMAL_POINT_REGEX = '/(?<=\d)\.0+(\D|$)/';

    /**
     * Regular expression pattern to remove unnecessary trailing decimal point if there are no digits following it.
     *
     * @see https://regex101.com/r/XYoySI/1
     *
     * @var string
     */
    private const TRAILING_DECIMAL_POINT_REGEX = '/(?<=\d)\.(?=\D|$)/';

    /**
     * Optimize the SVG document by minifying the coordinates of specific elements.
     *
     * This method processes the following elements and their attributes:
     * - `<path>` elements with `d` attribute
     * - `<rect>`, `<circle>`, `<ellipse>`, `<line>`, `<polyline>`, and `<polygon>` elements with coordinate attributes
     *
     * It removes unnecessary trailing zeroes, decimal points, and trailing decimal points in coordinates.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        /**
         * @var \DOMNodeList<\DOMAttr> $pathAttributes
         */
        $pathAttributes = $domXPath->query('//svg:path/@d');
        foreach ($pathAttributes as $attribute) {
            $newValue = $this->minifyCoordinates($attribute->value);
            $attribute->value = $newValue;
        }

        /**
         * @var \DOMNodeList<\DOMElement> $coordinateElements
         */
        $coordinateElements = $domXPath->query('//svg:rect | //svg:circle | //svg:ellipse | //svg:line | //svg:polyline | //svg:polygon');
        foreach ($coordinateElements as $coordinateElement) {
            foreach ($coordinateElement->attributes as $attribute) {
                /**
                 * @var \DOMAttr $attribute
                 */
                if (\in_array($attribute->name, ['x', 'x1', 'x2', 'y', 'y1', 'y2', 'width', 'height', 'cx', 'cy', 'rx', 'ry', 'r', 'points', 'd'], true)) {
                    $attribute->value = $this->minifyCoordinates($attribute->value);
                }
            }
        }
    }

    /**
     * Minify the coordinates of the given value by removing unnecessary formatting.
     *
     * This method performs the following transformations:
     * - Removes unnecessary trailing zeroes in decimal numbers.
     * - Removes unnecessary decimal points if there are no digits following them.
     * - Removes trailing decimal points if there are no digits following them.
     *
     * @param string $value The value to minify
     *
     * @return string The minified value
     */
    private function minifyCoordinates(string $value): string
    {
        if ('' === $value) {
            return $value;
        }

        $value = $this->removeTrailingZeroes($value);
        $value = $this->removeUnnecessaryDecimalPoint($value);

        return $this->removeTrailingDecimalPoint($value);
    }

    private function removeTrailingZeroes(string $value): string
    {
        return preg_replace(self::TRAILING_ZEROES_REGEX, '$1$2', $value) ?? $value;
    }

    private function removeUnnecessaryDecimalPoint(string $value): string
    {
        return preg_replace(self::UNNECESSARY_DECIMAL_POINT_REGEX, '$1', $value) ?? $value;
    }

    private function removeTrailingDecimalPoint(string $value): string
    {
        return preg_replace(self::TRAILING_DECIMAL_POINT_REGEX, '', $value) ?? $value;
    }
}
