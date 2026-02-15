<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Contract\Service\Rule;

/**
 * Defines the contract for optimization rules that can be applied to an SVG document.
 *
 * @no-named-arguments
 */
interface SvgOptimizerRuleInterface
{
    /**
     * Indicates whether this optimization rule is considered risky.
     *
     * A "risky" rule is one that may alter the visual appearance or behavior
     * of the SVG in some cases, even if it successfully reduces file size.
     */
    public static function isRisky(): bool;

    /**
     * If true, SvgOptimizer will only keep the rule's effect if it reduces size.
     */
    public static function shouldCheckSize(): bool;

    /**
     * Apply optimization rules to the given \DOMDocument instance.
     *
     * This method modifies the provided $domDocument instance in place,
     * applying any defined optimization rules. The modifications should be
     * made directly on the $domDocument, and the method should not return any value.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void;
}
