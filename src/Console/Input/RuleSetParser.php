<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\RuleSet;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RuleSetParser
{
    /**
     * @param list<string> $names The names of the rule sets, such as "security"
     *
     * @return list<RuleSet> The rule sets
     *
     * @throws \InvalidArgumentException If a name is not the name of a rule set
     */
    public static function parse(array $names): array
    {
        return array_map(self::parseName(...), $names);
    }

    /**
     * @throws \InvalidArgumentException If the name is not the name of a rule set
     */
    private static function parseName(string $name): RuleSet
    {
        return RuleSet::tryFrom($name) ?? throw new \InvalidArgumentException(\sprintf('Unknown rule set "%s". Valid rule sets are: %s.', $name, implode(', ', array_column(RuleSet::cases(), 'value'))));
    }
}
