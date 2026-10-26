<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console;

use MathiasReker\PhpSvgOptimizer\Console\FileFinder;
use MathiasReker\PhpSvgOptimizer\Tests\Double\LinearTimeTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\TemporaryDirectoryTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\WorkingDirectoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FileFinder::class)]
final class FileFinderTest extends TestCase
{
    use LinearTimeTrait;
    use TemporaryDirectoryTrait;
    use WorkingDirectoryTrait;

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function findsTheSvgFilesOfADirectoryInNameOrderWithThePathAsGiven(): void
    {
        $directory = $this->createTree(['b.svg', 'a.svg', 'C.SVG', 'notes.txt', 'sub/d.svg', 'sub/e.png', 'sub/deeper/f.Svg', 'archive.svgz']);

        self::assertSame(
            [$directory . '/C.SVG', $directory . '/a.svg', $directory . '/b.svg', $directory . '/sub/d.svg', $directory . '/sub/deeper/f.Svg'],
            (new FileFinder())->find([$directory]),
        );
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function writesTheFilesBelowADirectoryWithATrailingSeparatorWithoutDoubling(): void
    {
        $directory = $this->createTree(['a.svg', 'sub/b.svg']);

        self::assertSame([$directory . '/a.svg', $directory . '/sub/b.svg'], (new FileFinder())->find([$directory . '/']));
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function writesRelativePathsRelativeToTheWorkingDirectory(): void
    {
        $directory = $this->createTree(['icons/a.svg', 'icons/sub/b.svg', 'c.svg']);
        $this->changeWorkingDirectory($directory);

        self::assertSame(['icons/a.svg', 'icons/sub/b.svg'], (new FileFinder())->find(['icons']));
        self::assertSame(['./icons/a.svg', './icons/sub/b.svg'], (new FileFinder())->find(['./icons']));
        self::assertSame(['c.svg', 'icons/a.svg', 'icons/sub/b.svg'], (new FileFinder())->find(['.']));
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function findsAFileGivenDirectlyAsGiven(): void
    {
        $directory = $this->createTree(['a.svg']);
        $this->changeWorkingDirectory($directory);

        self::assertSame(['a.svg'], (new FileFinder())->find(['a.svg']));
        self::assertSame([$directory . '/a.svg'], (new FileFinder())->find([$directory . '/a.svg']));
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function keepsTheOrderOfThePaths(): void
    {
        $directory = $this->createTree(['b/b.svg', 'a/a.svg', 'z.svg']);

        self::assertSame(
            [$directory . '/z.svg', $directory . '/b/b.svg', $directory . '/a/a.svg'],
            (new FileFinder())->find([$directory . '/z.svg', $directory . '/b', $directory . '/a']),
        );
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function skipsVersionControlDependencyAndVendorDirectories(): void
    {
        $directory = $this->createTree(['a.svg', '.git/g.svg', '.hg/h.svg', '.svn/s.svg', 'node_modules/n.svg', 'vendor/v.svg', 'sub/vendor/w.svg', 'sub/b.svg', 'vendors/x.svg']);

        self::assertSame(
            [$directory . '/a.svg', $directory . '/sub/b.svg', $directory . '/vendors/x.svg'],
            (new FileFinder())->find([$directory]),
        );
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function searchesAnIgnoredDirectoryGivenAsAPath(): void
    {
        $directory = $this->createTree(['vendor/v.svg', 'vendor/sub/w.svg']);

        self::assertSame(
            [$directory . '/vendor/sub/w.svg', $directory . '/vendor/v.svg'],
            (new FileFinder())->find([$directory . '/vendor']),
        );
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function doesNotFollowSymbolicLinksToDirectories(): void
    {
        $directory = $this->createTree(['real/a.svg', 'top.svg']);
        $this->createSymbolicLink($directory . '/real', $directory . '/link');
        $this->createSymbolicLink($directory, $directory . '/real/loop');

        self::assertSame(
            [$directory . '/real/a.svg', $directory . '/top.svg'],
            (new FileFinder())->find([$directory]),
        );
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function skipsSymbolicLinksToFilesAndDanglingOnesWhileSearchingADirectory(): void
    {
        $directory = $this->createTree(['target.svg', 'vanishing.svg']);
        $this->createSymbolicLink($directory . '/target.svg', $directory . '/link.svg');
        $this->createSymbolicLink($directory . '/vanishing.svg', $directory . '/dangling.svg');
        unlink($directory . '/vanishing.svg');

        self::assertSame([$directory . '/target.svg'], (new FileFinder())->find([$directory]));
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function usesASymbolicLinkToAFileThatIsGivenAsAPath(): void
    {
        $directory = $this->createTree(['target.svg']);
        $this->createSymbolicLink($directory . '/target.svg', $directory . '/link.svg');

        self::assertSame([$directory . '/link.svg'], (new FileFinder())->find([$directory . '/link.svg']));
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function keepsSpacesAndMultibyteCharactersInFileNames(): void
    {
        $directory = $this->createTree(['a b/ünï/ïcön ✓.svg']);

        self::assertSame([$directory . '/a b/ünï/ïcön ✓.svg'], (new FileFinder())->find([$directory]));
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function returnsNothingForADirectoryWithoutSvgFilesOrWithoutPaths(): void
    {
        $directory = $this->createTree(['notes.txt']);

        self::assertSame([], (new FileFinder())->find([$directory]));
        self::assertSame([], (new FileFinder())->find([]));
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function rejectsAPathThatDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"missing.svg" does not exist.');

        (new FileFinder())->find(['missing.svg']);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function rejectsAnEmptyPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"" does not exist.');

        (new FileFinder())->find(['']);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function rejectsAFileWithoutTheSvgExtension(): void
    {
        $directory = $this->createTree(['notes.txt']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('"%s/notes.txt" is not an SVG file.', $directory));

        (new FileFinder())->find([$directory . '/notes.txt']);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    #[Test]
    public function findsTheFilesOfALargeDirectoryInLinearTime(): void
    {
        $directories = [];

        foreach ([1_500, 12_000] as $size) {
            $directories[$size] = $this->createTree(array_map(static fn (int $i): string => $i . '.svg', range(1, $size)));
        }

        self::assertLinearTime(
            static fn (int $size): string => $directories[$size],
            static fn (string $directory): array => (new FileFinder())->find([$directory]),
            1_500,
        );
    }

    /**
     * @param list<string> $files the files to create, relative to a new temporary directory
     *
     * @return string the temporary directory
     */
    private function createTree(array $files): string
    {
        $directory = (string) realpath($this->createTemporaryDirectory());
        $directory = str_replace('\\', '/', $directory);

        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            if (!is_dir(\dirname($path))) {
                mkdir(\dirname($path), 0o777, true);
            }

            file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg"/>');
        }

        return $directory;
    }

    private function createSymbolicLink(string $target, string $link): void
    {
        set_error_handler(static fn (): bool => true);

        try {
            $created = symlink($target, $link);
        } finally {
            restore_error_handler();
        }

        if (!$created) {
            self::markTestSkipped('Symbolic links cannot be created here.');
        }
    }
}
