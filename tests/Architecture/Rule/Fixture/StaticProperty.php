<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Svg {
    /**
     * @no-named-arguments
     */
    final class Cache
    {
        /** @var array<string, string> */
        public static array $values = [];

        /** @var array<string, string> */
        public array $instanceValues = [];
    }
}

namespace MathiasReker\PhpSvgOptimizer\Tests {
    /**
     * @no-named-arguments
     */
    final class TestCache
    {
        /** @var array<string, string> */
        public static array $values = [];
    }
}
