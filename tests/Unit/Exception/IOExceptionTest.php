<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Exception;

use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IOException::class)]
final class IOExceptionTest extends TestCase
{
    #[Test]
    public function canBeInstantiated(): void
    {
        $ioException = new IOException('IO operation failed', 500);

        self::assertSame('IO operation failed', $ioException->getMessage());
        self::assertSame(500, $ioException->getCode());
    }
}
