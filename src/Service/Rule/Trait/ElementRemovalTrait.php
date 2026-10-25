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
trait ElementRemovalTrait
{
    use ElementListTrait;

    /**
     * @param \DOMDocument                $domDocument the DOM document to clean
     * @param callable(\DOMElement): bool $predicate   returns true for elements to remove
     */
    private function removeElementsWhere(\DOMDocument $domDocument, callable $predicate): void
    {
        foreach ($this->elementList($domDocument, '*') as $domElement) {
            if ($domElement !== $domDocument->documentElement && $predicate($domElement)) {
                $domElement->remove();
            }
        }
    }
}
