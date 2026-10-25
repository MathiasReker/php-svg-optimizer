<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberListTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifyListValues implements PassInterface
{
    use ElementTrait;
    use NumberFormatterTrait;
    use NumberListTrait;

    private const array LIST_ATTRIBUTES = [
        'baseFrequency' => [],
        'filterRes' => [],
        'kernelMatrix' => [],
        'kernelUnitLength' => [],
        'keyPoints' => [],
        'keySplines' => [],
        'keyTimes' => [],
        'order' => [],
        'points' => [],
        'radius' => [],
        'stdDeviation' => [],
        'stroke-dasharray' => [],
        'tableValues' => [],
        'viewBox' => [],
        'rotate' => self::TEXT_ELEMENTS,
        'dx' => self::TEXT_ELEMENTS,
        'dy' => self::TEXT_ELEMENTS,
        'x' => self::TEXT_ELEMENTS,
        'y' => self::TEXT_ELEMENTS,
    ];

    private const array TEXT_ELEMENTS = ['text', 'tspan'];

    private const array POSITION_ATTRIBUTES = ['dx', 'dy', 'x', 'y'];

    private const array SEMICOLON_ATTRIBUTES = ['keyPoints', 'keySplines', 'keyTimes', self::VALUES];

    private const string COLOR_MATRIX = 'feColorMatrix';

    private const array COMPACT_ATTRIBUTES = ['points'];

    private const string VALUES = 'values';

    private const array NUMERIC_VALUES_ELEMENTS = ['animateMotion', 'animateTransform', self::COLOR_MATRIX];

    private const string ANIMATE = 'animate';

    private const string ATTRIBUTE_NAME = 'attributeName';

    private const array NUMERIC_ANIMATED_ATTRIBUTES = [
        'baseFrequency', 'cx', 'cy', 'dx', 'dy', 'fill-opacity', 'flood-opacity', 'font-size', 'fr', 'fx', 'fy',
        'height', 'k1', 'k2', 'k3', 'k4', 'offset', 'opacity', 'points', 'r', 'rx', 'ry', 'stdDeviation',
        'stop-opacity', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-miterlimit', 'stroke-opacity',
        'stroke-width', 'viewBox', 'width', 'x', 'x1', 'x2', 'y', 'y1', 'y2',
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->mayMatchAttributeValues($domDocument)) {
            return;
        }

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (!$this->isSvgElement($domNodeList)) {
                continue;
            }

            foreach (iterator_to_array($domNodeList->attributes, false) as $domAttr) {
                $this->minifyAttribute($domNodeList, $domAttr);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element holding the attribute
     * @param \DOMAttr    $domAttr    the attribute to minify
     */
    private function minifyAttribute(\DOMElement $domElement, \DOMAttr $domAttr): void
    {
        if (null !== $domAttr->namespaceURI || !$this->isListAttribute($domElement, $domAttr->name)) {
            return;
        }

        $semicolons = \in_array($domAttr->name, self::SEMICOLON_ATTRIBUTES, true) && self::COLOR_MATRIX !== $domElement->localName;
        $minified = $this->minifyValue($domAttr->name, $domAttr->value, $semicolons);

        if ('' !== $minified && mb_strlen($minified) < mb_strlen($domAttr->value)) {
            $domAttr->value = $minified;
        }
    }

    /**
     * @param \DOMElement $domElement the element holding the attribute
     * @param string      $name       the attribute name
     */
    private function isListAttribute(\DOMElement $domElement, string $name): bool
    {
        if (self::VALUES === $name) {
            return $this->hasNumericValues($domElement);
        }

        $elements = self::LIST_ATTRIBUTES[$name] ?? null;

        return null !== $elements && ([] === $elements || \in_array($domElement->localName, $elements, true));
    }

    /**
     * @param \DOMElement $domElement the element holding a `values` attribute
     */
    private function hasNumericValues(\DOMElement $domElement): bool
    {
        if (self::ANIMATE === $domElement->localName) {
            return \in_array($domElement->getAttribute(self::ATTRIBUTE_NAME), self::NUMERIC_ANIMATED_ATTRIBUTES, true);
        }

        return \in_array($domElement->localName, self::NUMERIC_VALUES_ELEMENTS, true);
    }

    /**
     * @param string $name       the attribute name
     * @param string $value      the attribute value
     * @param bool   $semicolons whether the value is a `;`-separated list of number lists
     *
     * @return string the minified value, or an empty string if the value is not a number list
     */
    private function minifyValue(string $name, string $value, bool $semicolons): string
    {
        $parts = $semicolons ? explode(';', $value) : [$value];
        $minimumCount = \in_array($name, self::POSITION_ATTRIBUTES, true) ? 2 : 1;
        $compact = \in_array($name, self::COMPACT_ATTRIBUTES, true);
        $minifiedParts = [];

        foreach ($parts as $part) {
            $numbers = $this->parseNumberList($part, $compact, $compact);

            if (\count($numbers) < $minimumCount) {
                return '';
            }

            $minifiedParts[] = $this->joinNumberList(array_map($this->minifyNumber(...), $numbers), $compact);
        }

        return implode(';', $minifiedParts);
    }
}
