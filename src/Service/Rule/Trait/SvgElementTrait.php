<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait SvgElementTrait
{
    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is in the SVG namespace, or in no namespace as in a document without a namespace
     *              declaration
     */
    private function isSvgElement(\DOMElement $domElement): bool
    {
        return \in_array($domElement->namespaceURI, [null, SvgNamespace::Svg->value], true);
    }

    /**
     * @param list<\DOMElement> $elements the elements with a class
     */
    private function hasNonSvgElement(array $elements): bool
    {
        foreach ($elements as $element) {
            if (!$this->isSvgElement($element)) {
                return true;
            }
        }

        return false;
    }
}
