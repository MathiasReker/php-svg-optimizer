<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\File;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class SvgFile
{
    private const int DIRECTORY_PERMISSION = 0o755;

    private const int FILE_PERMISSION = 0o644;

    private const int PERMISSION_MASK = 0o777;

    /**
     * @param string $path the file to read
     *
     * @return string the content of the file
     *
     * @throws IOException If the file does not exist, the path is not usable or the file cannot be read
     */
    public static function read(string $path): string
    {
        self::assertUsablePath($path, 'Input');

        if (is_dir($path)) {
            throw new IOException(\sprintf('Input path is a directory: %s', $path));
        }

        if (!is_file($path)) {
            throw new FileNotFoundException(\sprintf('Input file does not exist: %s', $path));
        }

        if (!is_readable($path)) {
            throw new IOException(\sprintf('Input file is not readable: %s', $path));
        }

        $content = file_get_contents($path);

        if (false === $content) {
            throw new IOException(\sprintf('Failed to read input file content: %s', $path));
        }

        return $content;
    }

    /**
     * @param string $path    the file to replace, or create, with a complete copy of the content or nothing at all;
     *                        missing directories are created
     * @param string $content the content to write
     *
     * @throws IOException If the path is not usable or the file cannot be replaced
     */
    public static function write(string $path, string $content): void
    {
        self::prepareOutput($path);

        $realPath = realpath($path);
        $target = is_link($path) && false !== $realPath ? $realPath : $path;

        if ((file_exists($target) && !is_writable($target)) || !self::replaceThroughTemporaryFile($target, $content)) {
            throw new IOException(\sprintf('Failed to write optimized content to the output file: %s', $path));
        }
    }

    /**
     * @param string $target  the file to replace
     * @param string $content the content to write
     *
     * @return bool true if the target now holds the content
     */
    private static function replaceThroughTemporaryFile(string $target, string $content): bool
    {
        $temporary = self::withoutWarnings(static fn (): false|string => tempnam(\dirname($target), '.svg-optimizer-'));

        $replaced = false !== $temporary
            && false !== self::withoutWarnings(static fn (): false|int => file_put_contents($temporary, $content))
            && self::withoutWarnings(static fn (): bool => chmod($temporary, file_exists($target) ? fileperms($target) & self::PERMISSION_MASK : self::FILE_PERMISSION))
            && self::withoutWarnings(static fn (): bool => rename($temporary, $target));

        self::withoutWarnings(static fn (): bool => false !== $temporary && file_exists($temporary) && unlink($temporary));

        return $replaced;
    }

    /**
     * @param string $path the file to write, whose missing directories are created
     *
     * @throws IOException If the path is not usable, is a directory, or its directory cannot be created
     */
    private static function prepareOutput(string $path): void
    {
        self::assertUsablePath($path, 'Output');

        if (is_dir($path)) {
            throw new IOException(\sprintf('Output path is a directory: %s', $path));
        }

        if (!self::ensureDirectoryExists(\dirname($path))) {
            throw new IOException(\sprintf('Failed to create directory for output file: %s', $path));
        }
    }

    /**
     * @param string $path the file path to check
     * @param string $role the role of the file in messages, `Input` or `Output`
     *
     * @throws IOException If the path is empty or contains a null byte
     */
    private static function assertUsablePath(string $path, string $role): void
    {
        if ('' === $path) {
            throw new IOException(\sprintf('%s path is empty.', $role));
        }

        if (str_contains($path, "\0")) {
            throw new IOException(\sprintf('%s path contains a null byte: %s', $role, str_replace("\0", '\0', $path)));
        }
    }

    /**
     * @param string $directoryPath the directory to create if it does not exist
     */
    private static function ensureDirectoryExists(string $directoryPath): bool
    {
        return is_dir($directoryPath)
            || self::withoutWarnings(static fn (): bool => mkdir($directoryPath, self::DIRECTORY_PERMISSION, true))
            || is_dir($directoryPath);
    }

    /**
     * @template T
     *
     * @param \Closure(): T $callback a file system operation that reports a failure through its return value
     *
     * @param-immediately-invoked-callable $callback
     *
     * @return T
     */
    private static function withoutWarnings(\Closure $callback): mixed
    {
        set_error_handler(static fn (): bool => true);

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
