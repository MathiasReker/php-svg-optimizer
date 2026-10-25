<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Exception;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RiskyRulesNotAllowedException::class)]
#[UsesClass(Rule::class)]
final class RiskyRulesNotAllowedExceptionTest extends TestCase
{
    #[Test]
    public function listsTheRules(): void
    {
        $riskyRulesNotAllowedException = new RiskyRulesNotAllowedException([Rule::RemoveUnusedIds, Rule::FixAttributeNames]);

        self::assertSame([Rule::RemoveUnusedIds, Rule::FixAttributeNames], $riskyRulesNotAllowedException->getRules());
        self::assertSame('Risky rules are not allowed: removeUnusedIds, fixAttributeNames. Allow risky rules to use them.', $riskyRulesNotAllowedException->getMessage());
    }
}
