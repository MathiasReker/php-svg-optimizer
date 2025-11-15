<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Exception;

/**
 * \Exception thrown when risky SVG optimization rules are used
 * but have not been explicitly allowed.
 *
 * Risky rules may alter the SVG in ways that could be unsafe
 * or non-standard, so they must be explicitly enabled.
 *
 * @no-named-arguments
 */
final class RiskyRulesNotAllowedException extends \Exception
{
}
