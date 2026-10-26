<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

/**
 * @internal
 *
 * @no-named-arguments
 */
interface SizeGuardedPassInterface extends PassInterface
{
    /**
     * @param \DOMDocument $domDocument the document to transform in place, also with the changes that the size guard of
     *                                  the pass skips because they make the markup larger
     */
    public function applyWithoutSizeGuard(\DOMDocument $domDocument): void;
}
