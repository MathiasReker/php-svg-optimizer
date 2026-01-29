<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Data\Trait;

trait BaseEnumTrait
{
    /**
     * Convert enum cases to their string values.
     *
     * @param list<\BackedEnum> $cases
     *
     * @return list<string>
     */
    private static function valuesFromCases(array $cases): array
    {
        return array_map(static fn (\BackedEnum $backedEnum): string => (string) $backedEnum->value, $cases);
    }
}
