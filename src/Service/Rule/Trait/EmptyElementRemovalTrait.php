<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @no-named-arguments
 */
trait EmptyElementRemovalTrait
{
    /**
     * Visits elements in reverse document order, so descendants are removed before their ancestors are checked and an
     * element emptied by the removal is removed as well. The document element is never removed.
     *
     * @param \DOMDocument                $domDocument the document to clean
     * @param callable(\DOMElement): bool $isRemovable returns true for elements to remove
     */
    private function removeElementsBottomUp(\DOMDocument $domDocument, callable $isRemovable): void
    {
        $elements = array_reverse(iterator_to_array($domDocument->getElementsByTagName('*'), false));

        foreach ($elements as $domElement) {
            $parent = $domElement->parentNode;

            // A switch renders its first child whose conditions pass, so removing one would let another render.
            if ($parent instanceof \DOMElement && SvgTag::Switch->value !== $parent->localName && $isRemovable($domElement)) {
                $parent->removeChild($domElement);
            }
        }
    }
}
