<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait CssStyleElementTrait
{
    private const string CSS_TYPE = 'text/css';

    /**
     * @param \DOMElement $domElement a `<style>` element
     *
     * @return bool true if the element holds CSS as text only, with an empty type or a type of `text/css` in any ASCII
     *              case and without whitespace, as browsers apply
     */
    private function isCssTextStyle(\DOMElement $domElement): bool
    {
        $type = $domElement->getAttribute(SvgAttribute::Type->value);

        if ('' !== $type && 0 !== strcasecmp($type, self::CSS_TYPE)) {
            return false;
        }

        foreach ($domElement->childNodes as $childNode) {
            if (!$childNode instanceof \DOMText) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMNodeList<\DOMElement>   $domNodeList the style elements
     * @param \Closure(\DOMElement): bool $predicate   the condition an element has to meet
     *
     * @return list<\DOMElement> the elements that meet the condition, in document order
     */
    private function styleElementsMatching(\DOMNodeList $domNodeList, \Closure $predicate): array
    {
        $styleElements = [];

        foreach ($domNodeList as $domElement) {
            if ($predicate($domElement)) {
                $styleElements[] = $domElement;
            }
        }

        return $styleElements;
    }

    /**
     * @param \DOMNodeList<\DOMElement> $domNodeList the style elements
     *
     * @return string the text of the elements, separated by spaces so that no token spans two of them
     */
    private function styleElementsText(\DOMNodeList $domNodeList): string
    {
        $styleSheets = [];

        foreach ($domNodeList as $domElement) {
            $styleSheets[] = $domElement->textContent;
        }

        return implode(' ', $styleSheets);
    }
}
