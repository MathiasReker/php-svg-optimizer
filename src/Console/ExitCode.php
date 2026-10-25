<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console;

/**
 * @internal
 *
 * @no-named-arguments
 */
enum ExitCode: int
{
    case Success = 0;

    case Failure = 1;

    case InvalidUsage = 2;

    case ChangesDetected = 3;
}
