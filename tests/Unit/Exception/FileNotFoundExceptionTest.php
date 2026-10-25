<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Exception;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FileNotFoundException::class)]
final class FileNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function keepsTheMessage(): void
    {
        self::assertSame('File not found', (new FileNotFoundException('File not found'))->getMessage());
    }

    /**
     * @throws FileNotFoundException
     */
    #[Test]
    public function isCaughtAsAnIoException(): void
    {
        $this->expectException(IOException::class);

        throw new FileNotFoundException('File not found');
    }
}
