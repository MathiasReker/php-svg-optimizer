<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit;

use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\File\SvgFile;
use MathiasReker\PhpSvgOptimizer\Metrics;
use MathiasReker\PhpSvgOptimizer\OptimizationResult;
use MathiasReker\PhpSvgOptimizer\Tests\Double\TemporaryDirectoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OptimizationResult::class)]
#[UsesClass(Metrics::class)]
#[UsesClass(SvgFile::class)]
final class OptimizationResultTest extends TestCase
{
    use TemporaryDirectoryTrait;

    #[Test]
    public function givesTheOptimizedContent(): void
    {
        self::assertSame('<svg/>', (new OptimizationResult('<svg/>', new Metrics(10, 6, 0.0)))->getContent());
    }

    #[Test]
    public function givesTheMetrics(): void
    {
        $metrics = new Metrics(10, 6, 0.0);

        self::assertSame($metrics, (new OptimizationResult('<svg/>', $metrics))->getMetrics());
    }

    /**
     * @throws IOException
     */
    #[Test]
    public function saveToFileWritesTheContentAndCreatesMissingDirectories(): void
    {
        $path = $this->createTemporaryDirectory() . '/a/b/output.svg';

        (new OptimizationResult('<svg/>', new Metrics(10, 6, 0.0)))->saveToFile($path);

        self::assertSame('<svg/>', file_get_contents($path));
    }

    /**
     * @throws IOException
     */
    #[Test]
    public function saveToFileReplacesAnExistingFileInsteadOfRewritingItInPlace(): void
    {
        $directory = $this->createTemporaryDirectory();
        $path = $directory . '/output.svg';
        file_put_contents($path, '<svg><rect/></svg>');
        $inode = fileinode($path);

        (new OptimizationResult('<svg/>', new Metrics(10, 6, 0.0)))->saveToFile($path);
        clearstatcache();

        self::assertSame('<svg/>', file_get_contents($path));
        self::assertNotSame($inode, fileinode($path));
        self::assertSame(['.', '..', 'output.svg'], scandir($directory));
    }

    /**
     * @throws IOException
     */
    #[Test]
    public function saveToFileThrowsAnIoExceptionForADirectory(): void
    {
        $directory = $this->createTemporaryDirectory();

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Output path is a directory: ' . $directory);

        (new OptimizationResult('<svg/>', new Metrics(10, 6, 0.0)))->saveToFile($directory);
    }
}
