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
 * Shortens number literals by rewriting the string only, so the represented value never changes.
 *
 * @internal
 *
 * @no-named-arguments
 */
trait NumberFormatterTrait
{
    private const string NUMBER_PATTERN = '[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?';

    /**
     * The lookahead requires a digit in the mantissa, so a lone sign or `.` is not a number.
     */
    private const string NUMBER_PARTS_REGEX = '/^([+-]?)(?=\.?\d)(\d*)(?:\.(\d*))?(?:[eE]([+-]?)(\d+))?$/';

    private const string PLAIN_NUMBER_REGEX = '/^\s*' . self::NUMBER_PATTERN . '\s*$/';

    private const string ZERO = '0';

    /**
     * @param string $number a number literal
     *
     * @return string the shortest equivalent literal, or the input if it is not a number literal
     */
    private function minifyNumber(string $number): string
    {
        if (1 !== preg_match(self::NUMBER_PARTS_REGEX, $number, $parts)) {
            return $number;
        }

        $integer = ltrim($parts[2], self::ZERO);
        $fraction = rtrim($parts[3] ?? '', self::ZERO);

        if ('' === $integer && '' === $fraction) {
            return self::ZERO;
        }

        $mantissa = '' === $fraction ? $integer : $integer . '.' . $fraction;
        $sign = '-' === $parts[1] ? '-' : '';

        return $sign . $mantissa . $this->minifyExponent($parts[4] ?? '', $parts[5] ?? '');
    }

    /**
     * @param string $number the literal to check
     *
     * @return bool true if the literal is zero
     */
    private function isZeroNumber(string $number): bool
    {
        return self::ZERO === $this->minifyNumber($number);
    }

    /**
     * @param string $value the attribute value
     *
     * @return bool true if the value is a single unitless number
     */
    private function isPlainNumber(string $value): bool
    {
        return 1 === preg_match(self::PLAIN_NUMBER_REGEX, $value);
    }

    /**
     * @param string $sign   the exponent sign
     * @param string $digits the exponent digits
     */
    private function minifyExponent(string $sign, string $digits): string
    {
        $digits = ltrim($digits, self::ZERO);

        if ('' === $digits) {
            return '';
        }

        return 'e' . ('-' === $sign ? '-' : '') . $digits;
    }
}
