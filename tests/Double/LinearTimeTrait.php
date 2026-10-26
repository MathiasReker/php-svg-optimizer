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
trait LinearTimeTrait
{
    private const int LINEAR_TIME_GROWTH = 8;

    private const int LINEAR_TIME_ALLOWED_RATIO = 10;

    private const float LINEAR_TIME_FLOOR_IN_SECONDS = 0.05;

    private const int LINEAR_TIME_RUNS = 3;

    /**
     * @template T
     *
     * @param \Closure(int): T   $arrange builds the input of the given size
     * @param \Closure(T): mixed $act     the work to time, which receives the input
     * @param int                $size    the size of the smaller input
     *
     * @param-immediately-invoked-callable $arrange
     * @param-immediately-invoked-callable $act
     */
    private static function assertLinearTime(\Closure $arrange, \Closure $act, int $size): void
    {
        $small = self::fastestRun($arrange, $act, $size);
        $large = self::fastestRun($arrange, $act, self::LINEAR_TIME_GROWTH * $size);

        self::assertLessThan(self::LINEAR_TIME_ALLOWED_RATIO * max($small, self::LINEAR_TIME_FLOOR_IN_SECONDS), $large);
    }

    /**
     * @template T
     *
     * @param \Closure(int): T   $arrange builds the input of the given size
     * @param \Closure(T): mixed $act     the work to time, which receives the input
     * @param int                $size    the size of the input
     *
     * @param-immediately-invoked-callable $arrange
     * @param-immediately-invoked-callable $act
     *
     * @return float the fastest of a few runs on fresh input, in seconds, which leaves out pauses of the runtime
     */
    private static function fastestRun(\Closure $arrange, \Closure $act, int $size): float
    {
        $fastest = \INF;

        for ($run = 0; $run < self::LINEAR_TIME_RUNS; ++$run) {
            $input = $arrange($size);
            gc_collect_cycles();
            $start = hrtime(true);
            $act($input);
            $fastest = min($fastest, (hrtime(true) - $start) / 1e9);
        }

        return $fastest;
    }
}
