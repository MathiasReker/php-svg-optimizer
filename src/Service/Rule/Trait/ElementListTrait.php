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
trait ElementListTrait
{
    /**
     * @param \DOMDocument $domDocument the document to search
     * @param string       $tagName     the qualified name of the elements, or `*` for every element
     *
     * @return list<\DOMElement> the matching elements in document order, unaffected by later changes to the document
     */
    private function elementList(\DOMDocument $domDocument, string $tagName): array
    {
        $elements = [];

        foreach ($domDocument->getElementsByTagName($tagName) as $domNodeList) {
            $elements[] = $domNodeList;
        }

        return $elements;
    }
}
