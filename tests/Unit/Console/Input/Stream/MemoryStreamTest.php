<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input\Stream;

use MathiasReker\PhpSvgOptimizer\Console\Input\Stream\MemoryStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MemoryStream::class)]
final class MemoryStreamTest extends TestCase
{
    /**
     * @throws \RuntimeException
     */
    public function testConstructorOpensMemoryStreamSuccessfully(): void
    {
        $this->expectNotToPerformAssertions();

        new MemoryStream();
    }

    /**
     * @throws \RuntimeException
     */
    public function testWriteAndGetContents(): void
    {
        $stream = new MemoryStream();
        $stream->write('Hello');
        $stream->writeln(' World');

        $output = $stream->getContent();

        self::assertStringContainsString('Hello', $output);
        self::assertStringContainsString(' World', $output);
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function testConstructorThrowsIfStreamFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to open memory stream.');

        $mock = $this->getMockBuilder(MemoryStream::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $reflection = new \ReflectionClass(MemoryStream::class);
        $streamProperty = $reflection->getProperty('stream');
        $streamProperty->setValue($mock, false);

        throw new \RuntimeException('Unable to open memory stream.');
    }

    /**
     * @throws \RuntimeException
     */
    public function testGetContentsThrowsIfReadingFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read from memory stream.');

        $mock = $this->getMockBuilder(MemoryStream::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getContent'])
            ->getMock();

        $mock->method('getContent')->willReturnCallback(
            static function (): void {
                /*
                 * @phpstan-ignore-next-line
                 */
                throw new \RuntimeException('Failed to read from memory stream.');
            }
        );

        $mock->getContent();
    }
}
