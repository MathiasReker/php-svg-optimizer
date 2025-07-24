<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\FileCollector;
use MathiasReker\PhpSvgOptimizer\Service\Filesystem\Finder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FileCollector::class)]
#[CoversClass(Finder::class)]
final class FileCollectorTest extends TestCase
{
    private string $tempDir;

    public function testReturnsEmptyArrayForNonExistentPath(): void
    {
        $collector = new FileCollector();
        $result = $collector->collectSvgFiles(['/path/does/not/exist']);

        self::assertSame([], $result);
    }

    public function testCollectsSingleSvgFile(): void
    {
        $svgFile = $this->tempDir . '/icon.svg';
        file_put_contents($svgFile, '<svg></svg>');

        $collector = new FileCollector();
        $result = $collector->collectSvgFiles([$svgFile]);

        self::assertCount(1, $result);
        self::assertSame(realpath($svgFile), $result[0]);
    }

    public function testIgnoresNonSvgFiles(): void
    {
        $txtFile = $this->tempDir . '/note.txt';
        file_put_contents($txtFile, 'text');

        $collector = new FileCollector();
        $result = $collector->collectSvgFiles([$txtFile]);

        self::assertSame([], $result);
    }

    public function testCollectsSvgFilesFromDirectory(): void
    {
        $svg1 = $this->tempDir . '/file1.svg';
        $svg2 = $this->tempDir . '/nested/file2.svg';
        $txt = $this->tempDir . '/file3.txt';

        mkdir(\dirname($svg2), 0o777, true);
        file_put_contents($svg1, '<svg></svg>');
        file_put_contents($svg2, '<svg></svg>');
        file_put_contents($txt, 'text');

        $collector = new FileCollector();
        $result = $collector->collectSvgFiles([$this->tempDir]);

        self::assertCount(2, $result);
        self::assertContains(realpath($svg1), $result);
        self::assertContains(realpath($svg2), $result);
    }

    public function testRemovesDuplicateFiles(): void
    {
        $svgFile = $this->tempDir . '/duplicate.svg';
        file_put_contents($svgFile, '<svg></svg>');

        $collector = new FileCollector();
        $result = $collector->collectSvgFiles([$svgFile, $svgFile]);

        self::assertCount(1, $result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/svg_test_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    /**
     * @throws \UnexpectedValueException
     */
    protected function tearDown(): void
    {
        self::deleteDirectory($this->tempDir);
    }

    /**
     * @throws \UnexpectedValueException
     */
    private static function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = $file->getPathname();

            if ($file->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
