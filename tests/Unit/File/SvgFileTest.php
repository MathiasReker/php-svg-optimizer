<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\File;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\File\SvgFile;
use MathiasReker\PhpSvgOptimizer\Tests\Double\TemporaryDirectoryTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\UnreadableFileStreamWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgFile::class)]
final class SvgFileTest extends TestCase
{
    use TemporaryDirectoryTrait;

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function readReturnsTheContentAsWritten(): void
    {
        $path = $this->createTemporaryDirectory() . '/my.icon ✓.svg';
        file_put_contents($path, "\xEF\xBB\xBF<svg/>\r\n");

        self::assertSame("\xEF\xBB\xBF<svg/>\r\n", SvgFile::read($path));
    }

    /**
     * @throws IOException
     * @throws FileNotFoundException
     */
    #[Test]
    public function readThrowsWhenTheFileDoesNotExist(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('Input file does not exist: nonexistent.svg');

        SvgFile::read('nonexistent.svg');
    }

    /**
     * @throws FileNotFoundException
     */
    #[DataProvider('provideReadRejectsAnUnusablePathCases')]
    #[Test]
    public function readRejectsAnUnusablePath(string $path, string $message): void
    {
        try {
            SvgFile::read($path);
            self::fail('Expected an IOException.');
        } catch (IOException $ioException) {
            self::assertSame($message, $ioException->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideReadRejectsAnUnusablePathCases(): iterable
    {
        yield 'empty path' => ['', 'Input path is empty.'];

        yield 'path with a null byte' => ["a\0b.svg", 'Input path contains a null byte: a\0b.svg'];

        yield 'directory' => [__DIR__, 'Input path is a directory: ' . __DIR__];
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function readThrowsWhenTheFileIsNotReadable(): void
    {
        $probeFile = $this->createTemporaryDirectory() . '/probe.svg';
        file_put_contents($probeFile, '<svg></svg>');
        chmod($probeFile, 0o000);
        clearstatcache(true, $probeFile);
        $chmodMakesUnreadable = !is_readable($probeFile);
        chmod($probeFile, 0o644);

        $userName = (string) getenv('USERNAME');
        $filePath = $this->createTemporaryDirectory() . '/unreadable.svg';
        file_put_contents($filePath, '<svg></svg>');

        try {
            if ($chmodMakesUnreadable) {
                chmod($filePath, 0o000);
            } else {
                exec('icacls ' . escapeshellarg($filePath) . ' /deny ' . escapeshellarg($userName . ':R') . ' 2>&1');
            }

            clearstatcache(true, $filePath);

            self::assertIsNotReadable($filePath, 'Precondition failed: could not make the file unreadable in this environment.');

            $this->expectException(IOException::class);
            $this->expectExceptionMessage(\sprintf('Input file is not readable: %s', $filePath));

            SvgFile::read($filePath);
        } finally {
            if ($chmodMakesUnreadable) {
                chmod($filePath, 0o644);
            } else {
                exec('icacls ' . escapeshellarg($filePath) . ' /remove:d ' . escapeshellarg($userName) . ' 2>&1');
            }
        }
    }

    /**
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[Test]
    public function readThrowsWhenTheContentCannotBeRead(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Failed to read input file content: unreadable://file.svg');

        UnreadableFileStreamWrapper::run(static fn (string $path): string => SvgFile::read($path));
    }

    /**
     * @throws IOException
     */
    #[Test]
    public function writeCreatesEveryMissingParentDirectory(): void
    {
        $path = $this->createTemporaryDirectory() . '/a/b/c/output.svg';

        SvgFile::write($path, '<svg/>');

        self::assertSame('<svg/>', file_get_contents($path));
    }

    /**
     * @throws IOException
     */
    #[Test]
    public function writeReplacesAnExistingFile(): void
    {
        $path = $this->createTemporaryDirectory() . '/output.svg';
        file_put_contents($path, '<svg><rect/></svg>');

        SvgFile::write($path, '<svg/>');

        self::assertSame('<svg/>', file_get_contents($path));
    }

    /**
     * @throws IOException
     */
    #[DataProvider('provideWriteRejectsAnUnusablePathCases')]
    #[Test]
    public function writeRejectsAnUnusablePath(string $path, string $message): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage($message);

        SvgFile::write($path, '<svg/>');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideWriteRejectsAnUnusablePathCases(): iterable
    {
        yield 'empty path' => ['', 'Output path is empty.'];

        yield 'path with a null byte' => ["out\0.svg", 'Output path contains a null byte: out\0.svg'];
    }

    #[Test]
    public function writeRefusesToReplaceADirectory(): void
    {
        $directory = $this->createTemporaryDirectory();

        try {
            SvgFile::write($directory . '/', '<svg/>');
            self::fail('Expected an IOException.');
        } catch (IOException $ioException) {
            self::assertSame('Output path is a directory: ' . $directory . '/', $ioException->getMessage());
            self::assertDirectoryExists($directory);
        }
    }

    #[Test]
    public function writeThrowsWithoutWarningsWhenTheDirectoryCannotBeCreated(): void
    {
        $blockingFile = $this->createTemporaryDirectory() . '/blocking';
        file_put_contents($blockingFile, '');
        $path = $blockingFile . '/output.svg';
        error_clear_last();

        try {
            SvgFile::write($path, '<svg/>');
            self::fail('Expected an IOException.');
        } catch (IOException $ioException) {
            self::assertSame('Failed to create directory for output file: ' . $path, $ioException->getMessage());
            self::assertNull(error_get_last());
        }
    }

    #[Test]
    public function writeThrowsWithoutWarningsWhenTheFileCannotBeWritten(): void
    {
        $path = $this->createTemporaryDirectory() . '/readonly.svg';
        file_put_contents($path, '<svg></svg>');
        chmod($path, 0o444);
        error_clear_last();

        try {
            SvgFile::write($path, '<svg/>');
            self::fail('Expected an IOException.');
        } catch (IOException $ioException) {
            self::assertSame('Failed to write optimized content to the output file: ' . $path, $ioException->getMessage());
            self::assertNull(error_get_last());
        } finally {
            chmod($path, 0o666);
        }
    }
}
