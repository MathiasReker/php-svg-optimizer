<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer;

use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;

/**
 * @api
 *
 * @no-named-arguments
 */
enum RuleSet: string
{
    case Security = 'security';

    case Standards = 'standards';

    case Cleanup = 'cleanup';

    case Conversion = 'conversion';

    case Structure = 'structure';

    case Minification = 'minification';

    /**
     * @return list<RuleSet> the sets that SvgOptimizer::withDefaultRules() selects
     */
    public static function defaults(): array
    {
        return [self::Standards, self::Cleanup, self::Conversion, self::Structure, self::Minification];
    }

    /**
     * @return list<Rule> the rules of the set, in the order of their first pass
     */
    public function getRules(): array
    {
        $rules = array_values(array_filter(Rule::cases(), fn (Rule $rule): bool => $this === $rule->getRuleSet()));
        usort($rules, static fn (Rule $a, Rule $b): int => self::firstPosition($a) <=> self::firstPosition($b));

        return $rules;
    }

    private static function firstPosition(Rule $rule): int
    {
        return min(array_map(ExecutionOrder::position(...), $rule->getPassClasses()));
    }

    /**
     * @return bool true if the rule belongs to the set
     */
    public function contains(Rule $rule): bool
    {
        return $this === $rule->getRuleSet();
    }
}
