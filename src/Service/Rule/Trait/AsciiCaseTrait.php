<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * CSS keywords, property names and color names are ASCII case-insensitive. `mb_strtolower()` would also fold
 * non-ASCII letters, such as the Kelvin sign into `k`, and turn an invalid value into a valid one.
 *
 * @no-named-arguments
 */
trait AsciiCaseTrait
{
    private const string ASCII_UPPERCASE_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const string ASCII_LOWERCASE_LETTERS = 'abcdefghijklmnopqrstuvwxyz';

    /**
     * @param string $value the value to lowercase
     *
     * @return string the value with only its ASCII letters lowercased
     */
    private function asciiLowercase(string $value): string
    {
        return strtr($value, self::ASCII_UPPERCASE_LETTERS, self::ASCII_LOWERCASE_LETTERS);
    }
}
