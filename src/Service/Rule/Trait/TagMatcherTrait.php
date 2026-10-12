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
 * @no-named-arguments
 */
trait TagMatcherTrait
{
    /**
     * Matches case-insensitively on the local name, and on the part after the last colon for undeclared prefixes.
     *
     * @param \DOMElement  $domElement the element to check
     * @param list<string> $tags       the tag names to match
     *
     * @return bool true if the element's local name is one of the tags
     */
    private function isTagIn(\DOMElement $domElement, array $tags): bool
    {
        $localName = $domElement->localName ?? $domElement->tagName;
        $separator = mb_strrpos($localName, ':');

        if (false !== $separator) {
            $localName = mb_substr($localName, $separator + 1);
        }

        foreach ($tags as $tag) {
            if (0 === strcasecmp($localName, $tag)) {
                return true;
            }
        }

        return false;
    }
}
