<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\RuleSetParser;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RuleSetParser::class)]
final class RuleSetParserTest extends TestCase
{
    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function parseReturnsTheRuleSetsInTheGivenOrder(): void
    {
        self::assertSame(
            [RuleSet::Minification, RuleSet::Security, RuleSet::Minification],
            RuleSetParser::parse(['minification', 'security', 'minification']),
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function parseAcceptsEveryRuleSetName(): void
    {
        self::assertSame(
            RuleSet::cases(),
            RuleSetParser::parse(array_map(static fn (RuleSet $ruleSet): string => $ruleSet->value, RuleSet::cases())),
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function parseReturnsAnEmptyListForNoNames(): void
    {
        self::assertSame([], RuleSetParser::parse([]));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function parseRejectsAnUnknownName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown rule set "security ". Valid rule sets are: security, standards, cleanup, conversion, structure, minification.');

        RuleSetParser::parse(['cleanup', 'security ']);
    }
}
