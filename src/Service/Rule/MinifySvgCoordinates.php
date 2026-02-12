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
     * This regex removes leading zeros from decimal values (e.g., 0.5 -> .5).
     *
     * @see https://regex101.com/r/JVNlRF/1
     */
    private const string REMOVE_LEADING_ZERO_REGEX = '/(?<=^|\D)0(\.\d+)/';

    /**
     * This regex removes trailing zeros from decimal values (e.g., 1.230 -> 1.23).
     *
     * @see https://regex101.com/r/6XmnVQ/1
     */
    private const string REMOVE_TRAILING_ZEROS_REGEX = '/(\.\d*?)0+(\D|$)/';

    /**
     * This regex removes decimal points that are not followed by a digit (e.g., 2. -> 2).
     *
     * @see https://regex101.com/r/HpT7H6/1
     */
    private const string REMOVE_TRAILING_DECIMAL_POINT_REGEX = '/(?<=\d)\.(?=\D|$)/';

    /**
     * This regex replaces standalone decimal points with 0.
     *
     * @see https://regex101.com/r/UH8ubo/1
     */
    private const string REPLACE_STANDALONE_DOT_REGEX = '/(?<=^|\s)\.(?=\s|$)/';

    /**
     * This regex removes decimal points that are followed by zeros only (e.g., 2.0 -> 2).
     *
     * @see https://regex101.com/r/zaCn7k/1
     */
    private const string REMOVE_DECIMAL_IF_ZERO_REGEX = '/(?<=\d)\.0+(\D|$)/';

    /**
     * Mapping of SVG elements to attributes to minify.
     */
    private const array ELEMENTS_TO_ATTRIBUTES = [
        '//svg:path' => [SvgAttribute::D->value],
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
        '//svg:svg' => [SvgAttribute::ViewBox->value, SvgAttribute::EnableBackground->value],
    ];

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
     * Minifies numerical values in SVG coordinate and dimension attributes.
     *
     * This method targets attributes like `d`, `points`, `x`, `y`, `width`,
     * `height`, and `viewBox`, applying several regex-based optimizations to
     * reduce the length of floating-point numbers (e.g., removing leading
     * zeros, trailing zeros, and unnecessary decimal points).
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Svg->prefix(), SvgNamespace::Svg->value);

        foreach (self::ELEMENTS_TO_ATTRIBUTES as $query => $attributes) {
            $this->minifyElements($domXPath, $query, $attributes);
        }
    }

    /**
     * Minifies the specified attributes on elements found by the given XPath query.
     *
     * @param \DOMXPath    $domXPath   the XPath object for querying the document
     * @param string       $query      the XPath query to find elements
     * @param list<string> $attributes the attributes to minify on the found elements
     */
    private function minifyElements(\DOMXPath $domXPath, string $query, array $attributes): void
    {
        $nodes = $domXPath->query($query);

        if (false === $nodes) {
            return;
        }

        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            $this->minifyAttributesOnElement($node, $attributes);
        }
    }

    /**
     * Minifies the specified attributes on a single DOM element.
     *
     * @param \DOMElement  $domElement the element to process
     * @param list<string> $attributes the attributes to minify on the element
     */
    private function minifyAttributesOnElement(\DOMElement $domElement, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            if ($domElement->hasAttribute($attribute)) {
                $domElement->setAttribute($attribute, $this->minifyCoordinates($domElement->getAttribute($attribute)));
            }
        }
    }

    /**
     * Applies a series of minification techniques to a string of coordinates.
     *
     * This includes removing leading and trailing zeros from floating-point
     * numbers and removing unnecessary decimal points.
     *
     * @param string $value the string containing coordinates to minify
     *
     * @return string the minified coordinate string
     */
    private function minifyCoordinates(string $value): string
    {
        if ('' === $value) {
            return $value;
        }

        $value = preg_replace(
            [
                self::REMOVE_LEADING_ZERO_REGEX,
                self::REMOVE_TRAILING_ZEROS_REGEX,
                self::REMOVE_DECIMAL_IF_ZERO_REGEX,
                self::REMOVE_TRAILING_DECIMAL_POINT_REGEX,
                self::REPLACE_STANDALONE_DOT_REGEX,
            ],
            [
                '$1',
                '$1$2',
                '$1',
                '',
                '0',
            ],
            $value
        );

        return $value ?? '';
    }
}
