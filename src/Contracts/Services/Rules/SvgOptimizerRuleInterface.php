<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules;

use DOMDocument;

/**
 * Interface SvgOptimizerRuleInterface.
 *
 * Defines the contract for optimization rules that can be applied to an SVG document.
 */
interface SvgOptimizerRuleInterface
{
    /**
     * Apply optimization rules to the given DOMDocument instance.
     *
     * This method modifies the provided $domDocument instance in place,
     * applying any defined optimization rules. The modifications should be
     * made directly on the $domDocument, and the method should not return any value.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void;
}
