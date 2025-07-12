<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input\Stream;

use MathiasReker\PhpSvgOptimizer\Console\Input\Stream\StdoutStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StdoutStream::class)]
final class StdoutStreamTest extends TestCase
{
    /**
     * @throws \RuntimeException
     */
    public function testConstructorOpensStdoutSuccessfully(): void
    {
        $this->expectNotToPerformAssertions();

        new StdoutStream();
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function testWriteOutputsToStream(): void
    {
        $mock = $this->getMockBuilder(StdoutStream::class)
            ->onlyMethods(['write', 'writeln'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new \ReflectionClass(StdoutStream::class);
        $property = $reflection->getProperty('stream');
        $resource = fopen('php://memory', 'w+');
        $property->setValue($mock, $resource);

        if (false === $resource) {
            throw new \RuntimeException('Unable to open stdout stream.');
        }

        fclose($resource);

        /**
         * @phpstan-ignore-next-line
         */
        $mock = new class extends StdoutStream {
            public function __construct()
            {
                parent::__construct();
                $stream = fopen('php://memory', 'w+');

                if (false === $stream) {
                    throw new \RuntimeException('Unable to open memory stream.');
                }

                $this->stream = $stream;
            }

            public function getContent(): string
            {
                rewind($this->stream);
                $contents = stream_get_contents($this->stream);

                return false !== $contents
                    ? $contents
                    : '';
            }
        };

        $mock->write('Hello');
        $mock->writeln(' World');

        $output = $mock->getContent();

        self::assertStringContainsString('Hello', $output);
        self::assertStringContainsString(' World', $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testConstructorThrowsIfStreamFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to open stdout stream.');

        throw new \RuntimeException('Unable to open stdout stream.');
    }
}
