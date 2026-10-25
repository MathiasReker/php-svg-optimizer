<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait InheritanceBoundaryTrait
{
    use ElementTrait;

    private const string UNKNOWN_INHERITED_VALUE = '';

    private const string NOT_SET_HERE = "\0";

    /**
     * @param \DOMElement                 $domElement the element to start from
     * @param string                      $attribute  the inherited presentation attribute
     * @param \Closure(\DOMElement): bool $overrides  whether something else than the attribute, such as an inline style,
     *                                                may set the property on an element of the chain
     * @param \Closure(\DOMElement): bool $isBoundary whether an element of the chain that does not set the attribute may
     *                                                inherit from somewhere else than its parent, such as a `<use>`
     *
     * @return string the value of the attribute on the nearest element of the ancestor-or-self chain that sets it, or an
     *                empty string if the value cannot be known; the root element inherits from the page that inlines the
     *                SVG or from the `<use>` element that references it, so the initial value is never assumed
     */
    private function inheritedValue(\DOMElement $domElement, string $attribute, \Closure $overrides, \Closure $isBoundary): string
    {
        for ($node = $domElement; $node instanceof \DOMElement; $node = $node->parentNode) {
            if ($overrides($node)) {
                return self::UNKNOWN_INHERITED_VALUE;
            }

            $value = $this->ownInheritedValue($node, $attribute, self::NOT_SET_HERE);

            if (self::NOT_SET_HERE !== $value) {
                return $value;
            }

            if ($isBoundary($node)) {
                return self::UNKNOWN_INHERITED_VALUE;
            }
        }

        return self::UNKNOWN_INHERITED_VALUE;
    }

    /**
     * @param \DOMElement $domElement  the element to read
     * @param string      $attribute   the inherited presentation attribute
     * @param string      $parentValue the value the element inherits when it does not set the attribute
     *
     * @return string the trimmed value the element sets, the parent value when it does not set the attribute or sets
     *                `inherit`, or an empty string if the value depends on the parent in a way that cannot be known
     */
    private function ownInheritedValue(\DOMElement $domElement, string $attribute, string $parentValue): string
    {
        $value = trim($domElement->getAttribute($attribute));

        if (!$domElement->hasAttribute($attribute) || $this->isInheritKeyword($value)) {
            return $parentValue;
        }

        return $this->mayUseParentValue($value) ? self::UNKNOWN_INHERITED_VALUE : $value;
    }
}
