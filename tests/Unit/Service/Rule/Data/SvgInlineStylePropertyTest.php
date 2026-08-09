<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Data;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgInlineStyleProperty::class)]
final class SvgInlineStylePropertyTest extends TestCase
{
    #[Test]
    public function valuesReturnsAllProperties(): void
    {
        $values = SvgInlineStyleProperty::values();

        self::assertCount(\count(SvgInlineStyleProperty::cases()), $values);
        self::assertContains('fill', $values);
        self::assertContains('stroke', $values);
        self::assertContains('display', $values);
    }
}
