<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class SortAttributes implements PassInterface
{
    use AttributeNodeTrait;

    private const array ATTRIBUTE_ORDER = [
        Attribute::Id->value,
        Attribute::Width->value,
        Attribute::Height->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        foreach ($domDocument->getElementsByTagName('*') as $element) {
            $this->sortElementAttributes($element);
        }
    }

    /**
     * @param \DOMElement $domElement the element whose attributes to sort
     */
    private function sortElementAttributes(\DOMElement $domElement): void
    {
        /** @var list<\DOMAttr> $attributes */
        $attributes = iterator_to_array($domElement->attributes, false);
        $sortedAttributes = $attributes;
        usort($sortedAttributes, $this->compareAttributes(...));

        if ($sortedAttributes === $attributes || $this->sharesLocalName($attributes)) {
            return;
        }

        foreach ($sortedAttributes as $sortedAttribute) {
            $this->appendAttributeNode($domElement, $sortedAttribute);
        }
    }

    /**
     * @param \DOMAttr $first  an attribute
     * @param \DOMAttr $second another attribute of the same element
     */
    private function compareAttributes(\DOMAttr $first, \DOMAttr $second): int
    {
        return [$this->priority($first), $first->nodeName] <=> [$this->priority($second), $second->nodeName];
    }

    /**
     * @param \DOMAttr $domAttr the attribute to rank
     *
     * @return int the position of the attribute among the attributes written first, or the number of those attributes
     *             for any other attribute
     */
    private function priority(\DOMAttr $domAttr): int
    {
        $position = array_search($domAttr->nodeName, self::ATTRIBUTE_ORDER, true);

        return false === $position ? \count(self::ATTRIBUTE_ORDER) : $position;
    }
}
