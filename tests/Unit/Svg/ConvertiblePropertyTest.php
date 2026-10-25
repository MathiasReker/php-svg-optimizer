<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Svg;

use MathiasReker\PhpSvgOptimizer\Svg\ConvertibleProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertibleProperty::class)]
final class ConvertiblePropertyTest extends TestCase
{
    #[Test]
    public function listsTheConvertibleProperties(): void
    {
        $values = array_column(ConvertibleProperty::cases(), 'value');

        self::assertContains('fill', $values);
        self::assertContains('stroke', $values);
        self::assertContains('display', $values);
    }
}
