<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output\Stream;

use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\SilentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SilentStream::class)]
final class SilentStreamTest extends TestCase
{
    #[Test]
    public function writeDoesNothingAndDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $silentStream = new SilentStream();
        $silentStream->write('x');
    }

    #[Test]
    public function writelnDoesNothingAndDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $silentStream = new SilentStream();
        $silentStream->writeln('y');
    }
}
