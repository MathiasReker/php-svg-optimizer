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
 * @no-named-arguments
 */
interface SvgOptimizerRuleInterface
{
    public static function isRisky(): bool;

    public static function shouldCheckSize(): bool;

    /**
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void;
}
