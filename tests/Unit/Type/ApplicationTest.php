<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Type;

use MathiasReker\PhpSvgOptimizer\Type\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @no-named-arguments
 *
 * @internal
 */
#[CoversClass(Application::class)]
final class ApplicationTest extends TestCase
{
    public function testEnumValuesAreNonEmpty(): void
    {
        foreach (Application::cases() as $case) {
            self::assertNotEmpty($case->value);
        }
    }
}
