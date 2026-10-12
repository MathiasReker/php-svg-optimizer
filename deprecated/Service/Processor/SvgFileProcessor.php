<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Processor;

class_alias(\MathiasReker\PhpSvgOptimizer\Console\Processor\SvgFileProcessor::class, SvgFileProcessor::class);

if (false) {
    /**
     * @deprecated since 8.8.0, use {@see \MathiasReker\PhpSvgOptimizer\Console\Processor\SvgFileProcessor} instead. Will be removed in 9.0.
     */
    final class SvgFileProcessor
    {
    }
}
