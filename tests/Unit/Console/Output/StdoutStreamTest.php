<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Output;

use MathiasReker\PhpSvgOptimizer\Console\Output\AbstractStream;
use MathiasReker\PhpSvgOptimizer\Console\Output\StdoutStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StdoutStream::class)]
#[UsesClass(AbstractStream::class)]
final class StdoutStreamTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    #[Test]
    public function opensTheStandardOutput(): void
    {
        $stdoutStream = new StdoutStream();

        $resource = (new \ReflectionProperty($stdoutStream, 'stream'))->getValue($stdoutStream);
        self::assertIsResource($resource);
        self::assertSame('php://stdout', stream_get_meta_data($resource)['uri'] ?? '');
    }

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    #[Test]
    public function closesTheStreamWhenItIsDestroyed(): void
    {
        $stdoutStream = new StdoutStream();
        $resource = (new \ReflectionProperty($stdoutStream, 'stream'))->getValue($stdoutStream);
        self::assertIsResource($resource);

        unset($stdoutStream);

        self::assertSame('Unknown', get_resource_type($resource));
    }

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    #[Test]
    public function isDestroyedWithoutErrorsWhenTheStreamIsAlreadyClosed(): void
    {
        $stdoutStream = new StdoutStream();
        $resource = (new \ReflectionProperty($stdoutStream, 'stream'))->getValue($stdoutStream);
        self::assertIsResource($resource);
        fclose($resource);

        unset($stdoutStream);

        self::assertSame('Unknown', get_resource_type($resource));
    }
}
