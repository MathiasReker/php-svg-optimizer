<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Double;

/**
 * @internal
 */
trait RegexFailureTrait
{
    /**
     * @template T
     *
     * @param \Closure(): T $callback runs while every regular expression that needs to backtrack fails
     *
     * @return T
     */
    private function withFailingRegularExpressions(\Closure $callback): mixed
    {
        $jit = (string) \ini_get('pcre.jit');
        $backtrackLimit = (string) \ini_get('pcre.backtrack_limit');
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '1');

        try {
            return $callback();
        } finally {
            ini_set('pcre.jit', $jit);
            ini_set('pcre.backtrack_limit', $backtrackLimit);
        }
    }
}
