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
use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StdoutStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StdoutStream::class)]
#[UsesClass(MemoryStream::class)]
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
    #[Test]
    public function multipleWrites(): void
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
     * @throws \ReflectionException
     */
    #[Test]
    public function streamClosedOnDestruct(): void
    {
        $stdoutStream = new StdoutStream();

        $reflectionClass = new \ReflectionClass($stdoutStream);
        $reflectionProperty = $reflectionClass->getProperty('stream');

        $resource = $reflectionProperty->getValue($stdoutStream);
        self::assertIsResource($resource);

        unset($stdoutStream);

        self::assertSame('Unknown', get_resource_type($resource), 'Stream should be closed after destruction');
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
        self::assertIsResource($resource);

        fclose($resource);

        unset($stdoutStream);

        self::assertSame('Unknown', get_resource_type($resource));
    }
}
