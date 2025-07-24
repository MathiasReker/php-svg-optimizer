<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Filesystem;

use MathiasReker\PhpSvgOptimizer\Service\Filesystem\Finder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Finder::class)]
final class FinderTest extends TestCase
{
    private string $tempDir;

    public function testReturnsEmptyArrayForInvalidDirectory(): void
    {
        $finder = (new Finder())->in('/non/existing/path');

        self::assertSame([], $finder->find());
    }

    public function testFindsFilesWithSpecificExtension(): void
    {
        $svg = $this->tempDir . '/icon.svg';
        $txt = $this->tempDir . '/note.txt';

        file_put_contents($svg, '<svg></svg>');
        file_put_contents($txt, 'text');

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files()
            ->withExtension('svg');

        $result = $finder->find();

        self::assertCount(1, $result);
        self::assertSame(realpath($svg), $result[0]);
    }

    public function testFindsFilesCaseInsensitiveExtension(): void
    {
        $svg = $this->tempDir . '/icon.SVG';
        file_put_contents($svg, '<svg></svg>');

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files()
            ->withExtension('svg');

        $result = $finder->find();

        self::assertCount(1, $result);
        self::assertSame(realpath($svg), $result[0]);
    }

    public function testFindsFilesRecursively(): void
    {
        $nestedDir = $this->tempDir . '/nested';
        mkdir($nestedDir, 0o777, true);

        $svg1 = $this->tempDir . '/file1.svg';
        $svg2 = $nestedDir . '/file2.svg';

        file_put_contents($svg1, '<svg></svg>');
        file_put_contents($svg2, '<svg></svg>');

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files()
            ->withExtension('svg');

        $result = $finder->find();

        self::assertCount(2, $result);
        self::assertContains(realpath($svg1), $result);
        self::assertContains(realpath($svg2), $result);
    }

    public function testFindIgnoresDirectoriesWhenOnlyFiles(): void
    {
        $dirPath = $this->tempDir . '/subdir';
        mkdir($dirPath);

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files()
            ->withExtension('svg');

        $result = $finder->find();

        self::assertSame([], $result);
    }

    public function testFindReturnsEmptyWhenExtensionDoesNotMatch(): void
    {
        $file = $this->tempDir . '/file.txt';
        file_put_contents($file, 'some content');

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files()
            ->withExtension('svg');

        self::assertSame([], $finder->find());
    }

    public function testNotReturnsDirectoriesIfFilesNotSet(): void
    {
        $subDir = $this->tempDir . '/subfolder';
        mkdir($subDir);

        $finder = (new Finder())
            ->in($this->tempDir);

        $results = $finder->find();

        self::assertNotContains(realpath($subDir), $results);
    }

    public function testFindsAllFilesWhenNoExtensionSet(): void
    {
        $svg = $this->tempDir . '/icon.svg';
        $txt = $this->tempDir . '/note.txt';

        file_put_contents($svg, '<svg></svg>');
        file_put_contents($txt, 'note');

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files();

        $results = $finder->find();

        self::assertCount(2, $results);
        self::assertContains(realpath($svg), $results);
        self::assertContains(realpath($txt), $results);
    }

    public function testSkipsFilesWithInvalidRealPath(): void
    {
        $brokenLink = $this->tempDir . '/broken.svg';
        symlink('/nonexistent/path.svg', $brokenLink);

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files()
            ->withExtension('svg');

        $results = $finder->find();

        self::assertNotContains($brokenLink, $results);
    }

    public function testWithExtensionMatchesMixedCase(): void
    {
        $svgUpper = $this->tempDir . '/IMAGE.SvG';
        file_put_contents($svgUpper, '<svg></svg>');

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files()
            ->withExtension('sVG');

        $results = $finder->find();

        self::assertCount(1, $results);
        self::assertSame(realpath($svgUpper), $results[0]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/finder_test_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    /**
     * @throws \UnexpectedValueException
     */
    protected function tearDown(): void
    {
        self::deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * @throws \UnexpectedValueException
     */
    private static function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
