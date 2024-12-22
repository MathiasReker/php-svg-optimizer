<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use DOMElement;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;

final class SortAttributes implements SvgOptimizerRuleInterface
{
    /**
     * Default order for attributes to be sorted.
     * 'id', 'width', and 'height' come first, followed by other attributes in alphabetical order.
     *
     * This array defines the priority of attributes. Attributes listed here will
     * appear first when sorting, followed by the rest of the attributes sorted
     * alphabetically by their name.
     *
     * @var array<string>
     */
    private const array ATTRIBUTE_ORDER = [
        'id', 'width', 'height',
    ];

    /**
     * Sort element attributes for better compression and optimization.
     *
     * This method iterates through all elements of the SVG document and sorts
     * their attributes according to the predefined order.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $domXPath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $elements = $domXPath->query('//*');

        if ($elements instanceof \DOMNodeList) {
            foreach ($elements as $element) {
                if ($element instanceof \DOMElement) {
                    $this->sortElementAttributes($element);
                }
            }
        }
    }

    /**
     * Sort attributes of a given DOMElement.
     *
     * This method sorts the attributes of the DOMElement by first prioritizing
     * the attributes listed in `ATTRIBUTE_ORDER`, followed by sorting the
     * remaining attributes alphabetically.
     *
     * @param \DOMElement $domElement The DOMElement whose attributes should be sorted
     */
    private function sortElementAttributes(\DOMElement $domElement): void
    {
        $attributes = $this->extractAttributes($domElement);
        $sortedAttributes = $this->sortAttributes($attributes);

        foreach (iterator_to_array($domElement->attributes, false) as $attribute) {
            $domElement->removeAttribute($attribute->name);
        }

        foreach ($sortedAttributes as $name => $value) {
            $domElement->setAttribute($name, $value);
        }
    }

    /**
     * Extract attributes from a DOMElement.
     *
     * This method retrieves all attributes from the given DOMElement and returns
     * them as an associative array where the keys are attribute names (including
     * namespace if available) and the values are the corresponding attribute values.
     *
     * @param \DOMElement $domElement The DOMElement whose attributes are to be extracted
     *
     * @return array<string, string> The extracted attributes and their values
     */
    private function extractAttributes(\DOMElement $domElement): array
    {
        $attributes = [];
        foreach ($domElement->attributes as $attribute) {
            $attributes[null !== $attribute->namespaceURI ? $attribute->namespaceURI . ':' . $attribute->name : $attribute->name] = $attribute->value;
        }

        return $attributes;
    }

    /**
     * Sort attributes based on predefined order and alphabetical order.
     *
     * This method first ensures that attributes listed in the `ATTRIBUTE_ORDER`
     * array appear first in the sorted attributes. Then, the remaining attributes
     * are sorted alphabetically by their name.
     *
     * @param array<string, string> $attributes The attributes to be sorted
     *
     * @return array<string, string> The sorted attributes
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

        return array_merge($priorityAttributes, $otherAttributes);
    }
}
