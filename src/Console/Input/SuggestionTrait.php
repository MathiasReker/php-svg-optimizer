<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait SuggestionTrait
{
    private const int MINIMUM_DISTANCE = 2;

    private const int DISTANCE_DIVISOR = 3;

    /**
     * @param string       $name       what the user wrote
     * @param list<string> $candidates the valid names
     *
     * @return string a sentence that names the closest valid name, or an empty string if none is close
     */
    private static function suggest(string $name, array $candidates): string
    {
        $lowerName = mb_strtolower($name);
        $closest = '';
        $closestDistance = max(self::MINIMUM_DISTANCE, intdiv(mb_strlen($name), self::DISTANCE_DIVISOR)) + 1;

        foreach ($candidates as $candidate) {
            $distance = levenshtein($lowerName, mb_strtolower($candidate));

            if ($distance < $closestDistance) {
                $closest = $candidate;
                $closestDistance = $distance;
            }
        }

        return '' === $closest ? '' : \sprintf(' Did you mean "%s"?', $closest);
    }
}
