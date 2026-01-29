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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;

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
     * Regular expression pattern to replace a standalone decimal point "." with "0".
     *
     * @see https://regex101.com/r/CBS2bQ/1
     */
    private const string STANDALONE_DOT_REGEX = '/(?<=^|\s)\.(?=\s|$)/';

    /**
     * Mapping of SVG elements (XPath queries) to their attributes that should be minified.
     */
    private const array ELEMENTS_TO_ATTRIBUTES = [
        '//svg:path' => [
            SvgAttribute::D->value,
        ],
        '//svg:rect | //svg:circle | //svg:ellipse | //svg:line | //svg:polyline | //svg:polygon | //svg:svg' => [
            SvgAttribute::X->value,
            SvgAttribute::X1->value,
            SvgAttribute::X2->value,
            SvgAttribute::Y->value,
            SvgAttribute::Y1->value,
            SvgAttribute::Y2->value,
            SvgAttribute::Width->value,
            SvgAttribute::Height->value,
            SvgAttribute::Cx->value,
            SvgAttribute::Cy->value,
            SvgAttribute::Rx->value,
            SvgAttribute::Ry->value,
            SvgAttribute::R->value,
            SvgAttribute::Points->value,
            SvgAttribute::D->value,
        ],
        '//svg:svg' => [
            SvgAttribute::ViewBox->value,
            SvgAttribute::EnableBackground->value,
        ],
    ];

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    /**
     * Optimize the SVG document by minifying the coordinates of specific elements.
     *
     * This method processes the following elements and their attributes:
     * - `<path>` elements with `d` attribute
     * - `<svg>`, `<rect>`, `<circle>`, `<ellipse>`, `<line>`, `<polyline>`, and `<polygon>` elements with coordinate attributes
     *
     * It removes unnecessary trailing zeroes, decimal points, and trailing decimal points in coordinates.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Svg->prefix(), SvgNamespace::Svg->value);

        foreach (self::ELEMENTS_TO_ATTRIBUTES as $xpath => $attributes) {
            $this->processNodes($domXPath, $xpath, $attributes);
        }
    }

    /**
     * Query nodes for a given XPath and minify their attributes.
     *
     * @param list<string> $attributes
     */
    private function processNodes(\DOMXPath $domXPath, string $xpath, array $attributes): void
    {
        $nodes = $domXPath->query($xpath);
        if (!$nodes instanceof \DOMNodeList) {
            return;
        }

        /** @var \DOMNode $node */
        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $this->minifyNodeAttributes($node, $attributes);
            }
        }
    }

    /**
     * Minify specified attributes of a DOM element.
     *
     * @param list<string> $attributes
     */
    private function minifyNodeAttributes(\DOMNode $domNode, array $attributes): void
    {
        if (!$domNode instanceof \DOMElement) {
            return;
        }

        foreach ($attributes as $attribute) {
            if ($domNode->hasAttribute($attribute)) {
                $domNode->setAttribute($attribute, $this->minifyCoordinates($domNode->getAttribute($attribute)));
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

        if (str_contains($value, '.')) {
            $value = $this->removeLeadingZero($value);
        }

        $value = $this->removeTrailingZeroes($value);
        $value = $this->removeUnnecessaryDecimalPoint($value);
        $value = $this->removeTrailingDecimalPoint($value);

        return preg_replace(self::STANDALONE_DOT_REGEX, '0', $value) ?? $value;
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

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
