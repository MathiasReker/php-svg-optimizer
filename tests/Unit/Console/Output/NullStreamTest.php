<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output;

use MathiasReker\PhpSvgOptimizer\Console\Output\NullStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(NullStream::class)]
final class NullStreamTest extends TestCase
{
    #[Test]
    public function discardsWhatIsWritten(): void
    {
        $this->expectOutputString('');

        $nullStream = new NullStream();
        $nullStream->write('x');
        $nullStream->writeLine('y');
    }

    #[Test]
    public function hasNoColorSupport(): void
    {
        self::assertFalse((new NullStream())->hasColorSupport());
    }
}
