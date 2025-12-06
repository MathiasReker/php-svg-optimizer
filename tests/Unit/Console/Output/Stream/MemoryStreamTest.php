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
        $memoryStream = new MemoryStream();
        $memoryStream->write('Hello');
        $memoryStream->writeln(' World');

        $output = $memoryStream->getContent();

        self::assertStringContainsString('Hello', $output);
        self::assertStringContainsString(' World', $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testMultipleWritesAndGetContent(): void
    {
        $memoryStream = new MemoryStream();
        $memoryStream->write('Line 1');
        $memoryStream->writeln(' Line 2');
        $memoryStream->write('Line 3');
        $memoryStream->writeln(' Line 4');

        $output = $memoryStream->getContent();

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
        $memoryStream = new MemoryStream();

        $output = $memoryStream->getContent();

        self::assertSame('', $output);
    }

    /**
     * @throws \RuntimeException
     */
    public function testGetContentConsistentUnlessChanged(): void
    {
        $memoryStream = new MemoryStream();
        $memoryStream->writeln('Snapshot');

        $first = $memoryStream->getContent();
        $second = $memoryStream->getContent();

        self::assertSame($first, $second);

        $memoryStream->writeln('New line');

        $third = $memoryStream->getContent();

        self::assertNotSame($first, $third);
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function testWriteAndManualRewind(): void
    {
        $memoryStream = new MemoryStream();
        $memoryStream->write('Testing');

        /*
         * @phpstan-ignore-next-line
         */
        rewind((new \ReflectionClass($memoryStream))->getProperty('stream')->getValue($memoryStream));
        $output = $memoryStream->getContent();

        self::assertStringContainsString('Testing', $output);
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function testStreamIsClosedOnDestruct(): void
    {
        $memoryStream = new MemoryStream();

        $reflectionClass = new \ReflectionClass($memoryStream);
        $reflectionProperty = $reflectionClass->getProperty('stream');
        $resource = $reflectionProperty->getValue($memoryStream);

        unset($memoryStream);

        self::assertFalse(\is_resource($resource), 'Stream should be closed after destruct');
    }

    /**
     * @throws \RuntimeException
     */
    public function testWriteEmptyString(): void
    {
        $memoryStream = new MemoryStream();
        $memoryStream->write('');
        self::assertSame('', $memoryStream->getContent());
    }

    /**
     * @throws \RuntimeException
     */
    public function testWriteBinaryData(): void
    {
        $memoryStream = new MemoryStream();
        $binaryData = "\x00\xFF\x00\xFF";
        $memoryStream->write($binaryData);
        self::assertStringContainsString($binaryData, $memoryStream->getContent());
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    public function testGetContentAfterStreamClosedThrows(): void
    {
        $memoryStream = new MemoryStream();

        $reflectionClass = new \ReflectionClass($memoryStream);
        $reflectionProperty = $reflectionClass->getProperty('stream');
        $resource = $reflectionProperty->getValue($memoryStream);

        /*
         * @phpstan-ignore-next-line
         */
        fclose($resource);

        $this->expectException(\Error::class);

        $memoryStream->getContent();
    }
}
