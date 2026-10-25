<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Xml;

use MathiasReker\PhpSvgOptimizer\Xml\ParseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ParseException::class)]
final class ParseExceptionTest extends TestCase
{
    #[Test]
    public function keepsTheMessage(): void
    {
        self::assertSame('Test message', (new ParseException('Test message'))->getMessage());
    }
}
