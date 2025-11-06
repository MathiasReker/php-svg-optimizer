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
final readonly class MinifySvgCoordinates implements SvgOptimizerRuleInterface
{
    /**
     * Regular expression pattern to remove unnecessary trailing zeroes in decimal numbers.
     *
     * @see https://regex101.com/r/bQpK9Q/1
     */
    private const string TRAILING_ZEROES_REGEX = '/(\.\d*?)0+(\D|$)/';

    /**
     * Regular expression pattern to remove unnecessary decimal point if there are no digits after it.
     *
     * @see https://regex101.com/r/zEFuoB/1
     */
    private const string UNNECESSARY_DECIMAL_POINT_REGEX = '/(?<=\d)\.0+(\D|$)/';

    /**
     * Regular expression pattern to remove unnecessary trailing decimal point if there are no digits following it.
     *
     * @see https://regex101.com/r/XYoySI/1
     */
    private const string TRAILING_DECIMAL_POINT_REGEX = '/(?<=\d)\.(?=\D|$)/';

    /**
     * Regular expression pattern to remove the leading zero before a decimal point in numbers like 0.1.
     *
     * @see https://regex101.com/r/bLWJmu/1
     */
    private const string REMOVE_LEADING_ZERO_REGEX = '/(?<=^|\D)0(\.\d+)/';

    /**
     * Regular expression pattern to match zero values.
     *
     * @see https://regex101.com/r/3ejIEg/1
     */
    private const string ZERO_REGEX = '/^0(\.0+)?$/';

    /**
     * Optimize the SVG document by minifying the coordinates of specific elements.
     *
     * This method processes the following elements and their attributes:
     * - `<path>` elements with `d` attribute
     * - `<rect>`, `<circle>`, `<ellipse>`, `<line>`, `<polyline>`, and `<polygon>` elements with coordinate attributes
     *
     * It removes unnecessary trailing zeroes, decimal points, and trailing decimal points in coordinates.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        /** @var \DOMNodeList<\DOMAttr> $pathAttributes */
        $pathAttributes = $domXPath->query('//svg:path/@d');
        foreach ($pathAttributes as $attribute) {
            $attribute->value = $this->minifyCoordinates($attribute->value);
        }

        /** @var \DOMNodeList<\DOMElement> $coordinateElements */
        $coordinateElements = $domXPath->query('//svg:rect | //svg:circle | //svg:ellipse | //svg:line | //svg:polyline | //svg:polygon');
        foreach ($coordinateElements as $coordinateElement) {
            foreach ($coordinateElement->attributes ?? [] as $attribute) {
                /** @var \DOMAttr $attribute */
                if (\in_array($attribute->name, ['x', 'x1', 'x2', 'y', 'y1', 'y2', 'width', 'height', 'cx', 'cy', 'rx', 'ry', 'r', 'points', 'd'], true)) {
                    $attribute->value = $this->minifyCoordinates($attribute->value);
                }
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Minify the coordinates of the given value by removing unnecessary formatting.
     *
     * This method performs the following transformations:
     * - Removes unnecessary trailing zeroes in decimal numbers.
     * - Removes unnecessary decimal points if there are no digits following them.
     * - Removes trailing decimal points if there are no digits following them.
     * - Removes leading zero before the decimal point.
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

        if (\in_array(preg_match(self::ZERO_REGEX, $value), [0, false], true)) {
            $value = $this->removeLeadingZero($value);
        }

        $value = $this->removeTrailingZeroes($value);
        $value = $this->removeUnnecessaryDecimalPoint($value);

        return $this->removeTrailingDecimalPoint($value);
    }

    /**
     * Remove leading zero before a decimal point.
     */
    private function removeLeadingZero(string $value): string
    {
        return preg_replace(self::REMOVE_LEADING_ZERO_REGEX, '$1', $value) ?? $value;
    }

    /**
     * Remove unnecessary trailing zeroes in decimal numbers.
     */
    private function removeTrailingZeroes(string $value): string
    {
        return preg_replace(self::TRAILING_ZEROES_REGEX, '$1$2', $value) ?? $value;
    }

    /**
     * Remove unnecessary decimal point if there are no digits following it.
     */
    private function removeUnnecessaryDecimalPoint(string $value): string
    {
        return preg_replace(self::UNNECESSARY_DECIMAL_POINT_REGEX, '$1', $value) ?? $value;
    }

    /**
     * Remove trailing decimal point if there are no digits following it.
     */
    private function removeTrailingDecimalPoint(string $value): string
    {
        return preg_replace(self::TRAILING_DECIMAL_POINT_REGEX, '', $value) ?? $value;
    }
}
