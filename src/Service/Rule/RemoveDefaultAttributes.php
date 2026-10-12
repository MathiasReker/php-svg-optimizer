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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * Removes inherited presentation attributes whose value equals the initial
 * value, but only when the element would inherit that same value without it.
 *
 * @no-named-arguments
 */
final readonly class RemoveDefaultAttributes implements SvgOptimizerRuleInterface
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

    private const string XPATH_ANIMATED_ATTRIBUTES = '//@attributeName';

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
        // Stylesheet rules may set these properties on ancestors.
        if ($this->hasStyleElement($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);
        $animatedAttributes = $this->findAnimatedAttributes($domXPath);

        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = $domXPath->query(self::XPATH_ALL_ATTRIBUTES);

        foreach ($attributes as $attribute) {
            if (!$this->isDefaultValue($attribute) || \in_array($attribute->name, $animatedAttributes, true)) {
                continue;
            }

            $element = $attribute->ownerElement;
            if ($element instanceof \DOMElement && $this->inheritsDefault($element, $attribute->name)) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    /**
     * @return list<string> the names of all attributes targeted by animation elements
     */
    private function findAnimatedAttributes(\DOMXPath $domXPath): array
    {
        $names = [];

        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = $domXPath->query(self::XPATH_ANIMATED_ATTRIBUTES);

        foreach ($attributes as $attribute) {
            $names[] = trim($attribute->value);
        }

        return $names;
    }

    private function isDefaultValue(\DOMAttr $domAttr): bool
    {
        return null === $domAttr->namespaceURI
            && \array_key_exists($domAttr->name, self::DEFAULT_SVG_ATTRIBUTES)
            && self::DEFAULT_SVG_ATTRIBUTES[$domAttr->name] === $domAttr->value;
    }

    /**
     * Whether the element would get the default value through inheritance.
     *
     * @param \DOMElement $domElement the element carrying the attribute
     * @param string      $name       the attribute name
     */
    private function inheritsDefault(\DOMElement $domElement, string $name): bool
    {
        $node = $domElement;

        // An element with an id may be cloned by a use element and inherit from it instead.
        while (!$this->isReferenceable($node)) {
            $parent = $node->parentNode;
            if (!$parent instanceof \DOMElement) {
                return true;
            }

            if ($this->styleDeclares($parent, $name)) {
                return false;
            }

            if ($parent->hasAttribute($name)) {
                return self::DEFAULT_SVG_ATTRIBUTES[$name] === $parent->getAttribute($name);
            }

            $node = $parent;
        }

        return false;
    }

    /**
     * The id of the document element is ignored: the context of the root is external anyway.
     */
    private function isReferenceable(\DOMElement $domElement): bool
    {
        return $domElement->hasAttribute(SvgAttribute::Id->value)
            && $domElement->parentNode instanceof \DOMElement;
    }

    private function styleDeclares(\DOMElement $domElement, string $name): bool
    {
        $style = $domElement->getAttribute(SvgAttribute::Style->value);

        return '' !== $style
            && 1 === preg_match('/(?:^|;)\s*' . preg_quote($name, '/') . '\s*:/i', $style);
    }
}
