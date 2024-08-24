<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

class MinifySvgCoordinates implements SvgOptimizerRuleInterface
{
    /**
     * Optimize the given SVG document by minifying the coordinates of the path, rect, circle, ellipse, line, polyline, and polygon elements.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
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
                if ($attribute instanceof \DOMAttr && \in_array($attribute->name, ['x', 'x1', 'x2', 'y', 'y1', 'y2', 'width', 'height', 'cx', 'cy', 'rx', 'ry', 'r', 'points', 'd'], true)) {
                    $attribute->value = $this->minifyCoordinates($attribute->value);
                }
            }
        }
    }

    /**
     * Minify the coordinates of the given value.
     *
     * @param string $value the value to minify
     *
     * @return string the minified value
     */
    private function minifyCoordinates(string $value): string
    {
        if ('' === $value) {
            return $value;
        }

        // Remove unnecessary trailing zeroes in decimal numbers
        $value = preg_replace('/(\.\d*?)0+(\D|$)/', '$1$2', $value) ?? $value;

        // Remove unnecessary decimal point if there are no digits after it
        $value = preg_replace('/(?<=\d)\.0+(\D|$)/', '$1', $value) ?? $value;

        // Remove unnecessary trailing decimal point if there are no digits following it
        return preg_replace('/(?<=\d)\.(?=\D|$)/', '', $value) ?? $value;
    }
}
