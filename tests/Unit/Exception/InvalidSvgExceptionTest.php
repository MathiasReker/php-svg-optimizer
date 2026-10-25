<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Exception;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(InvalidSvgException::class)]
final class InvalidSvgExceptionTest extends TestCase
{
    #[Test]
    public function keepsTheMessageAndThePreviousException(): void
    {
        $runtimeException = new \RuntimeException('detail');
        $invalidSvgException = new InvalidSvgException('Invalid SVG detected', 0, $runtimeException);

        self::assertSame('Invalid SVG detected', $invalidSvgException->getMessage());
        self::assertSame($runtimeException, $invalidSvgException->getPrevious());
    }
}
