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
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AbstractStream::class)]
#[CoversClass(MemoryStream::class)]
final class ConcreteStreamTest extends TestCase
{
    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function supportsColorIsFalseByDefaultOnANonTtyStream(): void
    {
        $this->withEnv(
            ['NO_COLOR' => false, 'FORCE_COLOR' => false, 'CLICOLOR_FORCE' => false],
            static function (): void {
                self::assertFalse((new MemoryStream())->supportsColor());
            }
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function supportsColorIsFalseWhenNoColorIsSet(): void
    {
        $this->withEnv(
            ['NO_COLOR' => '1', 'FORCE_COLOR' => '1'],
            static function (): void {
                self::assertFalse((new MemoryStream())->supportsColor());
            }
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function supportsColorIsTrueWhenForceColorIsSetToANonZeroValue(): void
    {
        $this->withEnv(
            ['NO_COLOR' => false, 'FORCE_COLOR' => '1'],
            static function (): void {
                self::assertTrue((new MemoryStream())->supportsColor());
            }
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function supportsColorIsFalseWhenForceColorIsSetToZero(): void
    {
        $this->withEnv(
            ['NO_COLOR' => false, 'FORCE_COLOR' => '0'],
            static function (): void {
                self::assertFalse((new MemoryStream())->supportsColor());
            }
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function supportsColorIsTrueWhenCliColorForceIsSetToANonZeroValue(): void
    {
        $this->withEnv(
            ['NO_COLOR' => false, 'FORCE_COLOR' => false, 'CLICOLOR_FORCE' => '1'],
            static function (): void {
                self::assertTrue((new MemoryStream())->supportsColor());
            }
        );
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function supportsColorIsFalseWhenCliColorForceIsSetToZero(): void
    {
        $this->withEnv(
            ['NO_COLOR' => false, 'FORCE_COLOR' => false, 'CLICOLOR_FORCE' => '0'],
            static function (): void {
                self::assertFalse((new MemoryStream())->supportsColor());
            }
        );
    }

    /**
     * @param array<string, false|string> $env
     *
     * @param-immediately-invoked-callable $callback
     */
    private function withEnv(array $env, callable $callback): void
    {
        $original = [];

        foreach ($env as $name => $value) {
            $original[$name] = getenv($name);
            false === $value ? putenv($name) : putenv($name . '=' . $value);
        }

        try {
            $callback();
        } finally {
            foreach ($original as $name => $value) {
                false === $value ? putenv($name) : putenv($name . '=' . $value);
            }
        }
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function writeAppendsToStream(): void
    {
        $stream = new class extends AbstractStream {
            /**
             * @throws \RuntimeException
             */
            public function __construct()
            {
                $stream = fopen('php://memory', 'w+');

                if (false === $stream) {
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
    #[Test]
    public function writelnAppendsWithNewline(): void
    {
        $stream = new class extends AbstractStream {
            /**
             * @throws \RuntimeException
             */
            public function __construct()
            {
                $stream = fopen('php://memory', 'w+');

                if (false === $stream) {
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
