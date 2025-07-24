<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output\Stream;

use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\MemoryStream;
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

    /**
     * @throws \RuntimeException
     */
    public function testMultipleWritesAndGetContent(): void
    {
        $stream = new MemoryStream();
        $stream->write('Line 1');
        $stream->writeln(' Line 2');
        $stream->write('Line 3');
        $stream->writeln(' Line 4');

        $output = $stream->getContent();

        self::assertStringContainsString('Line 1', $output);
        self::assertStringContainsString('Line 2' . \PHP_EOL, $output);
        self::assertStringContainsString('Line 3', $output);
        self::assertStringContainsString('Line 4' . \PHP_EOL, $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testGetContentInitiallyEmpty(): void
    {
        $stream = new MemoryStream();

        $output = $stream->getContent();

        self::assertSame('', $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testGetContentConsistentUnlessChanged(): void
    {
        $stream = new MemoryStream();
        $stream->writeln('Snapshot');

        $first = $stream->getContent();
        $second = $stream->getContent();

        self::assertSame($first, $second);

        $stream->writeln('New line');

        $third = $stream->getContent();

        self::assertNotSame($first, $third);
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function testWriteAndManualRewind(): void
    {
        $stream = new MemoryStream();
        $stream->write('Testing');

        /*
         * @phpstan-ignore-next-line
         */
        rewind((new \ReflectionClass($stream))->getProperty('stream')->getValue($stream));
        $output = $stream->getContent();

        self::assertStringContainsString('Testing', $output);
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function testStreamIsClosedOnDestruct(): void
    {
        $stream = new MemoryStream();

        $ref = new \ReflectionClass($stream);
        $streamProp = $ref->getProperty('stream');
        $resource = $streamProp->getValue($stream);

        unset($stream);

        self::assertFalse(\is_resource($resource), 'Stream should be closed after destruct');
    }

    /**
     * @throws \RuntimeException
     */
    public function testWriteEmptyString(): void
    {
        $stream = new MemoryStream();
        $stream->write('');
        self::assertSame('', $stream->getContent());
    }

    /**
     * @throws \RuntimeException
     */
    public function testWriteBinaryData(): void
    {
        $stream = new MemoryStream();
        $binaryData = "\x00\xFF\x00\xFF";
        $stream->write($binaryData);
        self::assertStringContainsString($binaryData, $stream->getContent());
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function testGetContentAfterStreamClosedThrows(): void
    {
        $stream = new MemoryStream();

        $ref = new \ReflectionClass($stream);
        $streamProp = $ref->getProperty('stream');
        $resource = $streamProp->getValue($stream);

        /*
         * @phpstan-ignore-next-line
         */
        fclose($resource);

        $this->expectException(\Error::class);

        $stream->getContent();
    }
}
