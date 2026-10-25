<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritedValueTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDefaultAttributes implements PassInterface
{
    use AnimationTrait;
    use IdTrait;
    use InheritedValueTrait;
    use StyleSheetTrait;

    private const array DEFAULT_SVG_ATTRIBUTES = [
        Attribute::Stroke->value => 'none',
        Attribute::StrokeWidth->value => '1',
        Attribute::StrokeLinecap->value => 'butt',
        Attribute::StrokeLinejoin->value => 'miter',
        Attribute::StrokeMiterlimit->value => '4',
    ];

    private const string XPATH_ALL_ATTRIBUTES = '//@*';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument)) {
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
                && !$this->inlineStyleDeclares($element, 'all|' . $attribute->name)
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
        $parent = $domElement->parentNode;

        return $parent instanceof \DOMElement
            && !$this->isReferenceable($domElement)
            && self::DEFAULT_SVG_ATTRIBUTES[$name] === $this->inheritedValue(
                $parent,
                $name,
                fn (\DOMElement $domElement): bool => $this->inlineStyleDeclares($domElement, 'all|' . $name),
                $this->isReferenceable(...),
            );
    }

    private function isReferenceable(\DOMElement $domElement): bool
    {
        return $this->hasId($domElement) && $domElement->parentNode instanceof \DOMElement;
    }
}
