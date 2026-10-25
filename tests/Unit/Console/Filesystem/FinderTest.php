<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Filesystem;

use MathiasReker\PhpSvgOptimizer\Console\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Tests\Unit\Support\UnreadableDirectoryStreamWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Finder::class)]
final class FinderTest extends TestCase
{
    private string $tempDir;

    #[Test]
    public function returnsEmptyArrayForInvalidDirectory(): void
    {
        $finder = (new Finder())->in('/non/existing/path');

        self::assertSame([], $finder->find());
    }

    #[Test]
    public function findsFilesWithSpecificExtension(): void
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

    #[Test]
    public function findsFilesCaseInsensitiveExtension(): void
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

    #[Test]
    public function findsFilesRecursively(): void
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

    #[Test]
    public function findIgnoresDirectoriesWhenOnlyFiles(): void
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

    #[Test]
    public function findReturnsEmptyWhenExtensionDoesNotMatch(): void
    {
        $file = $this->tempDir . '/file.txt';
        file_put_contents($file, 'some content');

        $finder = (new Finder())
            ->in($this->tempDir)
            ->files()
            ->withExtension('svg');

        self::assertSame([], $finder->find());
    }

    #[Test]
    public function notReturnsDirectoriesIfFilesNotSet(): void
    {
        $subDir = $this->tempDir . '/subfolder';
        mkdir($subDir);

        $finder = (new Finder())
            ->in($this->tempDir);

        $results = $finder->find();

        self::assertNotContains(realpath($subDir), $results);
    }

    #[Test]
    public function findsAllFilesWhenNoExtensionSet(): void
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

    #[Test]
    public function skipsFilesWithInvalidRealPath(): void
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

    #[Test]
    public function withExtensionMatchesMixedCase(): void
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

    #[Test]
    public function returnsEmptyArrayWhenDirectoryCannotBeOpened(): void
    {
        $result = UnreadableDirectoryStreamWrapper::run(
            static fn (): array => (new Finder())->in(UnreadableDirectoryStreamWrapper::UNREADABLE_DIRECTORY)->find(),
        );

        self::assertSame([], $result);
    }

    #[Test]
    public function skipsSubdirectoriesThatCannotBeOpened(): void
    {
        $result = UnreadableDirectoryStreamWrapper::run(
            static fn (): array => (new Finder())
                ->in(UnreadableDirectoryStreamWrapper::READABLE_DIRECTORY)
                ->files()
                ->withExtension('svg')
                ->find(),
        );

        self::assertSame(['icon.svg', 'inner.svg'], array_map(basename(...), $result));
    }

    #[Test]
    public function findsEachFileOnceWhenASymlinkPointsBackToAnAncestor(): void
    {
        mkdir($this->tempDir . '/nested');
        file_put_contents($this->tempDir . '/nested/icon.svg', '<svg/>');

        self::assertTrue(symlink((string) realpath($this->tempDir), $this->tempDir . '/nested/loop'));

        $result = (new Finder())->in($this->tempDir)->files()->withExtension('svg')->find();

        self::assertSame([realpath($this->tempDir . '/nested/icon.svg')], $result);
    }

    #[Test]
    public function findsFilesOnceWhenTwoSymlinksPointToTheSameDirectory(): void
    {
        mkdir($this->tempDir . '/target');
        file_put_contents($this->tempDir . '/target/icon.svg', '<svg/>');

        self::assertTrue(symlink((string) realpath($this->tempDir . '/target'), $this->tempDir . '/link'));

        $result = (new Finder())->in($this->tempDir)->files()->withExtension('svg')->find();

        self::assertCount(1, $result);
        self::assertSame('icon.svg', basename($result[0]));
    }

    #[Test]
    public function followsASymlinkToADirectoryOutsideTheSearchedDirectory(): void
    {
        $outside = $this->tempDir . '_outside';
        mkdir($outside);
        file_put_contents($outside . '/icon.svg', '<svg/>');
        mkdir($this->tempDir . '/root');

        try {
            self::assertTrue(symlink((string) realpath($outside), $this->tempDir . '/root/link'));

            $result = (new Finder())->in($this->tempDir . '/root')->files()->withExtension('svg')->find();

            self::assertCount(1, $result);
            self::assertStringEndsWith('link' . \DIRECTORY_SEPARATOR . 'icon.svg', $result[0]);
        } finally {
            unlink($outside . '/icon.svg');
            rmdir($outside);
        }
    }

    #[Test]
    public function findsFilesWithSpacesAndMultibyteCharactersInTheirPath(): void
    {
        mkdir($this->tempDir . '/a b/ünï', 0o777, true);
        file_put_contents($this->tempDir . '/a b/ünï/ïcön ✓.svg', '<svg/>');

        $result = (new Finder())->in($this->tempDir)->files()->withExtension('svg')->find();

        self::assertSame([realpath($this->tempDir . '/a b/ünï/ïcön ✓.svg')], $result);
    }

    #[Test]
    public function returnsTheSameResultWhenSearchedTwice(): void
    {
        mkdir($this->tempDir . '/nested');
        file_put_contents($this->tempDir . '/nested/icon.svg', '<svg/>');
        $finder = (new Finder())->in($this->tempDir)->files()->withExtension('svg');

        self::assertSame($finder->find(), $finder->find());
        self::assertCount(1, $finder->find());
    }

    #[Test]
    public function ignoresFilesWithoutAnExtensionAndDotFilesWithTheExtensionAsName(): void
    {
        file_put_contents($this->tempDir . '/svg', '<svg/>');
        file_put_contents($this->tempDir . '/icon.svg.bak', '<svg/>');
        file_put_contents($this->tempDir . '/.svg', '<svg/>');

        $result = (new Finder())->in($this->tempDir)->files()->withExtension('svg')->find();

        self::assertSame([realpath($this->tempDir . '/.svg')], $result);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/finder_test_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    /**
     * @throws \UnexpectedValueException
     */
    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    /**
     * @throws \UnexpectedValueException
     */
    private function deleteDirectory(string $dir): void
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
            $this->deleteEntry($file);
        }

        rmdir($dir);
    }

    private function deleteEntry(\SplFileInfo $file): void
    {
        $isDirectory = $file->isDir() && (!$file->isLink() || '\\' === \DIRECTORY_SEPARATOR);

        $isDirectory ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    #[Test]
    public function returnsEmptyArrayForAnEmptyDirectoryName(): void
    {
        self::assertSame([], (new Finder())->in('')->find());
    }

    #[Test]
    public function returnsADanglingSymlinkOnlyWhenNotRestrictedToFiles(): void
    {
        file_put_contents($this->tempDir . '/target.svg', '<svg/>');
        file_put_contents($this->tempDir . '/icon.svg', '<svg/>');
        self::assertTrue(symlink((string) realpath($this->tempDir . '/target.svg'), $this->tempDir . '/dangling.svg'));
        unlink($this->tempDir . '/target.svg');

        $all = (new Finder())->in($this->tempDir)->withExtension('svg')->find();
        $files = (new Finder())->in($this->tempDir)->files()->withExtension('svg')->find();
        sort($all);

        self::assertSame(['dangling.svg', 'icon.svg'], array_map(basename(...), $all));
        self::assertSame(['icon.svg'], array_map(basename(...), $files));
    }

    #[Test]
    public function returnsAFileAndASymlinkToItUnderBothNames(): void
    {
        file_put_contents($this->tempDir . '/icon.svg', '<svg/>');
        self::assertTrue(symlink((string) realpath($this->tempDir . '/icon.svg'), $this->tempDir . '/link.svg'));

        $result = (new Finder())->in($this->tempDir)->files()->withExtension('svg')->find();
        sort($result);

        self::assertSame(['icon.svg', 'link.svg'], array_map(basename(...), $result));
    }

    #[Test]
    public function doesNotEnterTheSearchedDirectoryAgainThroughASymlink(): void
    {
        file_put_contents($this->tempDir . '/icon.svg', '<svg/>');
        mkdir($this->tempDir . '/nested');
        self::assertTrue(symlink((string) realpath($this->tempDir), $this->tempDir . '/nested/loop'));

        $result = (new Finder())->in($this->tempDir)->files()->withExtension('svg')->find();

        self::assertSame(['icon.svg'], array_map(basename(...), $result));
    }

    #[Test]
    public function matchesAMultibyteExtensionInAnyCase(): void
    {
        file_put_contents($this->tempDir . '/lower.äbc', '<svg/>');
        file_put_contents($this->tempDir . '/upper.ÄBC', '<svg/>');
        file_put_contents($this->tempDir . '/other.abc', '<svg/>');

        $result = (new Finder())->in($this->tempDir)->files()->withExtension('ÄBC')->find();
        sort($result);

        self::assertSame(['lower.äbc', 'upper.ÄBC'], array_map(basename(...), $result));
    }

    #[Test]
    public function skipsDirectoriesWithTheExtensionWhenOnlyFilesAreSearched(): void
    {
        mkdir($this->tempDir . '/folder.svg');
        file_put_contents($this->tempDir . '/icon.svg', '<svg/>');

        $result = (new Finder())->in($this->tempDir)->files()->withExtension('svg')->find();

        self::assertSame([realpath($this->tempDir . '/icon.svg')], $result);
    }
}
