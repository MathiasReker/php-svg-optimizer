<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output\Stream;

use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StdoutStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function constructorOpensStdoutSuccessfully(): void
    {
        $this->expectNotToPerformAssertions();
        new StdoutStream();
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function writeAndWritelnToMemoryStream(): void
    {
        $stream = new TestStream();

        $stream->write('Hello');
        $stream->writeln(' World');

        $output = $stream->getContent();

        self::assertStringContainsString('Hello', $output);
        self::assertStringContainsString(' World', $output);
    }

    /**
     * @throws \RuntimeException
     */
    #[Test]
    public function multipleWrites(): void
    {
        $stream = new TestStream();

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
     * @throws \ReflectionException
     */
    #[Test]
    public function streamClosedOnDestruct(): void
    {
        $stdoutStream = new StdoutStream();

        $reflectionClass = new \ReflectionClass($stdoutStream);
        $reflectionProperty = $reflectionClass->getProperty('stream');

        $resource = $reflectionProperty->getValue($stdoutStream);
        \assert(\is_resource($resource));

        unset($stdoutStream);

        self::assertFalse(\is_resource($resource), 'Stream should be closed after destruction');
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    #[Test]
    public function destructDoesNotThrowOnClosedStream(): void
    {
        $stdoutStream = new StdoutStream();

        $reflectionClass = new \ReflectionClass($stdoutStream);
        $reflectionProperty = $reflectionClass->getProperty('stream');

        $resource = $reflectionProperty->getValue($stdoutStream);
        \assert(\is_resource($resource));

        fclose($resource);

        unset($stdoutStream);

        $this->expectNotToPerformAssertions();
    }
}
