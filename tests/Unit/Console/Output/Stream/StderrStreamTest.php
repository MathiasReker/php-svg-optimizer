<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output\Stream;

use MathiasReker\PhpSvgOptimizer\Console\Output\Stream\StderrStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StderrStream::class)]
final class StderrStreamTest extends TestCase
{
    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    #[Test]
    public function constructorOpensStderr(): void
    {
        $stderrStream = new StderrStream();

        $resource = (new \ReflectionProperty($stderrStream, 'stream'))->getValue($stderrStream);
        self::assertIsResource($resource);
        self::assertSame('php://stderr', stream_get_meta_data($resource)['uri'] ?? '');
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    #[Test]
    public function streamClosedOnDestruct(): void
    {
        $stderrStream = new StderrStream();

        $resource = (new \ReflectionProperty($stderrStream, 'stream'))->getValue($stderrStream);
        self::assertIsResource($resource);

        unset($stderrStream);

        self::assertSame('Unknown', get_resource_type($resource));
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    #[Test]
    public function destructDoesNotThrowOnClosedStream(): void
    {
        $stderrStream = new StderrStream();

        $resource = (new \ReflectionProperty($stderrStream, 'stream'))->getValue($stderrStream);
        self::assertIsResource($resource);

        fclose($resource);

        unset($stderrStream);

        self::assertSame('Unknown', get_resource_type($resource));
    }
}
