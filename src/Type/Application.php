<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Type;

/**
 * @no-named-arguments
 */
enum Application: string
{
    case NAME = 'PHP SVG Optimizer';
    case VERSION = '7.3.0';
    case AUTHOR = 'Mathias Reker';
}
