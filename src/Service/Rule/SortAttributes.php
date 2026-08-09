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

/**
 * @no-named-arguments
 */
final readonly class SortAttributes implements SvgOptimizerRuleInterface
{
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
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $element) {
            $this->sortElementAttributes($element);
        }
    }

    /**
     * @param \DOMElement $domElement the element whose attributes to sort
     */
    private function sortElementAttributes(\DOMElement $domElement): void
    {
        $attributes = $this->extractAttributes($domElement);
        $sortedAttributes = $this->sortAttributes($attributes);

        /** @var \DOMAttr $domAttr */
        foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
            $domElement->removeAttribute($domAttr->nodeName);
        }

        foreach ($sortedAttributes as $name => $value) {
            $domElement->setAttribute($name, $value);
        }
    }

    /**
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
            $attributes[$domAttribute->nodeName] = $domAttribute->value;
        }

        return $attributes;
    }

    /**
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
