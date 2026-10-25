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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDefaultAttributes implements PassInterface
{
    use ElementTrait;

    private const array DEFAULT_SVG_ATTRIBUTES = [
        SvgAttribute::Stroke->value => 'none',
        SvgAttribute::StrokeWidth->value => '1',
        SvgAttribute::StrokeLinecap->value => 'butt',
        SvgAttribute::StrokeLinejoin->value => 'miter',
        SvgAttribute::StrokeMiterlimit->value => '4',
    ];

    private const string XPATH_ALL_ATTRIBUTES = '//@*';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);
        $animatedAttributes = $this->animatedAttributeNames($domDocument);

        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = $domXPath->query(self::XPATH_ALL_ATTRIBUTES);

        foreach ($attributes as $attribute) {
            if (!$this->isDefaultValue($attribute) || \array_key_exists($attribute->name, $animatedAttributes)) {
                continue;
            }

            $element = $attribute->ownerElement;
            if ($element instanceof \DOMElement
                && !$this->styleDeclares($element, 'all|' . $attribute->name)
                && $this->inheritsDefault($element, $attribute->name)
            ) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    private function isDefaultValue(\DOMAttr $domAttr): bool
    {
        return null === $domAttr->namespaceURI
            && \array_key_exists($domAttr->name, self::DEFAULT_SVG_ATTRIBUTES)
            && self::DEFAULT_SVG_ATTRIBUTES[$domAttr->name] === $domAttr->value;
    }

    /**
     * @param \DOMElement $domElement the element carrying the attribute
     * @param string      $name       the attribute name
     */
    private function inheritsDefault(\DOMElement $domElement, string $name): bool
    {
        $node = $domElement;

        while (!$this->isReferenceable($node)) {
            $parent = $node->parentNode;
            if (!$parent instanceof \DOMElement) {
                return true;
            }

            if ($this->styleDeclares($parent, 'all|' . $name)) {
                return false;
            }

            if ($parent->hasAttribute($name)) {
                return self::DEFAULT_SVG_ATTRIBUTES[$name] === $parent->getAttribute($name);
            }

            $node = $parent;
        }

        return false;
    }

    private function isReferenceable(\DOMElement $domElement): bool
    {
        return $this->hasId($domElement) && $domElement->parentNode instanceof \DOMElement;
    }
}
