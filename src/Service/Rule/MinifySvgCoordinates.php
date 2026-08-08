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
     * @see https://regex101.com/r/JVNlRF/1
     */
    private const string REMOVE_LEADING_ZERO_REGEX = '/(?<=^|\D)0(\.\d+)/';

    /**
     * @see https://regex101.com/r/6XmnVQ/1
     */
    private const string REMOVE_TRAILING_ZEROS_REGEX = '/(\.\d*?)0+(\D|$)/';

    /**
     * @see https://regex101.com/r/HpT7H6/1
     */
    private const string REMOVE_TRAILING_DECIMAL_POINT_REGEX = '/(?<=\d)\.(?=\D|$)/';

    /**
     * @see https://regex101.com/r/UH8ubo/1
     */
    private const string REPLACE_STANDALONE_DOT_REGEX = '/(?<=^|\s)\.(?=\s|$)/';

    /**
     * @see https://regex101.com/r/zaCn7k/1
     */
    private const string REMOVE_DECIMAL_IF_ZERO_REGEX = '/(?<=\d)\.0+(\D|$)/';

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
