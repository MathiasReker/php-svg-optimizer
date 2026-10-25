<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Exception;

use MathiasReker\PhpSvgOptimizer\Rule;

/**
 * @api
 *
 * @no-named-arguments
 */
final class RiskyRulesNotAllowedException extends \LogicException implements ExceptionInterface
{
    /**
     * @param non-empty-list<Rule> $rules the risky rules that were selected without allowing risky rules
     *
     * @internal
     */
    public function __construct(
        private readonly array $rules,
    ) {
        parent::__construct(\sprintf(
            'Risky rules are not allowed: %s. Allow risky rules to use them.',
            implode(', ', array_map(static fn (Rule $rule): string => $rule->value, $rules)),
        ));
    }

    /**
     * @return non-empty-list<Rule> the risky rules that were selected without allowing risky rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
