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
use MathiasReker\PhpSvgOptimizer\Console\Output\StderrStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StderrStream::class)]
#[UsesClass(AbstractStream::class)]
final class StderrStreamTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    #[Test]
    public function opensTheStandardError(): void
    {
        $stderrStream = new StderrStream();

        $resource = (new \ReflectionProperty($stderrStream, 'stream'))->getValue($stderrStream);
        self::assertIsResource($resource);
        self::assertSame('php://stderr', stream_get_meta_data($resource)['uri'] ?? '');
    }

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    #[Test]
    public function closesTheStreamWhenItIsDestroyed(): void
    {
        $stderrStream = new StderrStream();
        $resource = (new \ReflectionProperty($stderrStream, 'stream'))->getValue($stderrStream);
        self::assertIsResource($resource);

        unset($stderrStream);

        self::assertSame('Unknown', get_resource_type($resource));
    }

    /**
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    #[Test]
    public function isDestroyedWithoutErrorsWhenTheStreamIsAlreadyClosed(): void
    {
        $stderrStream = new StderrStream();
        $resource = (new \ReflectionProperty($stderrStream, 'stream'))->getValue($stderrStream);
        self::assertIsResource($resource);
        fclose($resource);

        unset($stderrStream);

        self::assertSame('Unknown', get_resource_type($resource));
    }
}
