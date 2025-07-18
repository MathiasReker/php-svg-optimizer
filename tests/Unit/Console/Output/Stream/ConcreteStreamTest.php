<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output\Stream;

use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\AbstractStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractStream::class)]
final class ConcreteStreamTest extends TestCase
{
    /**
     * @throws \RuntimeException
     */
    public function testWriteAppendsToStream(): void
    {
        $stream = new class extends AbstractStream {
            public function __construct()
            {
                $stream = fopen('php://memory', 'w+');

                if (false === $stream) {
                    /*
                     * @phpstan-ignore-next-line
                     */
                    throw new \RuntimeException('Unable to open memory stream.');
                }

                $this->stream = $stream;
            }

            public function getContent(): string
            {
                rewind($this->stream);

                return stream_get_contents($this->stream);
            }
        };

        $stream->write('Hello');
        $stream->write(' World');

        self::assertSame('Hello World', $stream->getContent());
    }

    /**
     * @throws \RuntimeException
     */
    public function testWritelnAppendsWithNewline(): void
    {
        $stream = new class extends AbstractStream {
            public function __construct()
            {
                $stream = fopen('php://memory', 'w+');

                if (false === $stream) {
                    /*
                     * @phpstan-ignore-next-line
                     */
                    throw new \RuntimeException('Unable to open memory stream.');
                }

                $this->stream = $stream;
            }

            public function getContent(): string
            {
                rewind($this->stream);

                return stream_get_contents($this->stream);
            }
        };

        $stream->writeln('Line1');
        $stream->writeln('Line2');

        $expected = 'Line1' . \PHP_EOL . 'Line2' . \PHP_EOL;

        self::assertSame($expected, $stream->getContent());
    }
}
