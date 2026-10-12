<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Exception;

use MathiasReker\PhpSvgOptimizer\Contract\Exception\SvgOptimizerExceptionInterface;

/**
 * @api
 *
 * @no-named-arguments
 */
final class SvgValidationException extends \InvalidArgumentException implements SvgOptimizerExceptionInterface
{
}
