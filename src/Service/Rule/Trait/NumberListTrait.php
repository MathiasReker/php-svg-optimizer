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
 * @internal
 *
 * @no-named-arguments
 */
trait NumberListTrait
{
    private const string NUMBER_LIST_REGEX = '/^\s*+(?>' . self::NUMBER_PATTERN . ')(?:(?:\s++,?+|,)\s*+(?>' . self::NUMBER_PATTERN . '))*+\s*+$/';

    private const string NUMBER_LIST_ITEM_REGEX = '/' . self::NUMBER_PATTERN . '/';

    /**
     * @param string $value the list to parse
     *
     * @return list<string> the number literals, or an empty list if the value is not a number list
     */
    private function parseNumberList(string $value): array
    {
        if (1 !== preg_match(self::NUMBER_LIST_REGEX, $value)) {
            return [];
        }

        preg_match_all(self::NUMBER_LIST_ITEM_REGEX, $value, $matches);

        return $matches[0];
    }

    /**
     * @param list<string> $numbers the minified number literals
     * @param bool         $compact whether to omit separators where the numbers stay distinct, as in path data
     */
    private function joinNumberList(array $numbers, bool $compact): string
    {
        $output = '';
        $previous = '';

        foreach ($numbers as $number) {
            $output .= ($compact ? $this->compactSeparatorBefore($previous, $number) : ('' === $previous ? '' : ' ')) . $number;
            $previous = $number;
        }

        return $output;
    }

    /**
     * @param string $previous the number written before, or an empty string at the start
     * @param string $number   the number about to be written
     */
    private function compactSeparatorBefore(string $previous, string $number): string
    {
        if ('' === $previous || str_starts_with($number, '-')) {
            return '';
        }

        $previousHasDecimalPart = str_contains($previous, '.') || str_contains($previous, 'e');

        return str_starts_with($number, '.') && $previousHasDecimalPart ? '' : ' ';
    }
}
