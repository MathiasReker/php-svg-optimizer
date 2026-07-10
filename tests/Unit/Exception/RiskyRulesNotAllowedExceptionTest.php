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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RiskyRulesNotAllowedException::class)]
final class RiskyRulesNotAllowedExceptionTest extends TestCase
{
    #[Test]
    public function canBeInstantiated(): void
    {
        $riskyRulesNotAllowedException = new RiskyRulesNotAllowedException('Risky rules are not allowed', 403);

        self::assertSame('Risky rules are not allowed', $riskyRulesNotAllowedException->getMessage());
        self::assertSame(403, $riskyRulesNotAllowedException->getCode());
    }
}
