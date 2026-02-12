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
final readonly class SortAttributes implements SvgOptimizerRuleInterface
{
    /**
     * Default order for attributes to be sorted.
     * 'id', 'width', and 'height' come first, followed by other attributes in alphabetical order.
     *
     * This array defines the priority of attributes. Attributes listed here will
     * appear first when sorting, followed by the rest of the attributes sorted
     * alphabetically by their name.
     */
    private const array ATTRIBUTE_ORDER = [
        SvgAttribute::Xmlns->value,
        SvgAttribute::Id->value,
        SvgAttribute::Height->value,
        SvgAttribute::Width->value,
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
     * Sorts the attributes of all elements in the SVG document.
     *
     * This can improve consistency and may have a minor positive impact on
     * compression. The sorting order is predefined, with common attributes
     * like `id`, `width`, and `height` appearing first.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Svg->prefix(), SvgNamespace::Svg->value);
        $domXPath->registerNamespace(SvgNamespace::Xlink->prefix(), SvgNamespace::Xlink->value);

        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $element) {
            $this->sortElementAttributes($element);
        }
    }

    /**
     * Sorts the attributes of a single DOM element.
     *
     * The attributes are sorted according to a predefined order, and then
     * alphabetically.
     *
     * @param \DOMElement $domElement the element whose attributes to sort
     */
    private function sortElementAttributes(\DOMElement $domElement): void
    {
        $attributes = $this->extractAttributes($domElement);
        $sortedAttributes = $this->sortAttributes($attributes);

        /** @var \DOMAttr $domAttr */
        foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
            $domElement->removeAttribute($domAttr->name);
        }

        foreach ($sortedAttributes as $name => $value) {
            $domElement->setAttribute($name, $value);
        }
    }

    /**
     * Extracts all attributes from a DOM element into an associative array.
     *
     * @param \DOMElement $domElement the element to extract attributes from
     *
     * @return array<string, string> a map of attribute names to their values
     */
    private function extractAttributes(\DOMElement $domElement): array
    {
        $attributes = [];

        /** @var \DOMNamedNodeMap<\DOMAttr> $domAttributes */
        $domAttributes = $domElement->attributes;

        foreach ($domAttributes as $domAttribute) {
            $attributes[$domAttribute->localName ?? $domAttribute->name] = $domAttribute->value;
        }

        return $attributes;
    }

    /**
     * Sorts an array of attributes.
     *
     * The sorting is based on a predefined priority list, with the remaining
     * attributes sorted alphabetically.
     *
     * @param array<string, string> $attributes the attributes to sort
     *
     * @return array<string, string> the sorted attributes
     */
    private function sortAttributes(array $attributes): array
    {
        $priorityAttributes = [];
        $otherAttributes = [];

        foreach ($attributes as $name => $value) {
            if (\in_array($name, self::ATTRIBUTE_ORDER, true)) {
                $priorityAttributes[$name] = $value;
            } else {
                $otherAttributes[$name] = $value;
            }
        }

        ksort($otherAttributes, \SORT_STRING);

        return [...$priorityAttributes, ...$otherAttributes];
    }
}
