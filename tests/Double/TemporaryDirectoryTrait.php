<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Double;

use PHPUnit\Framework\Attributes\After;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait TemporaryDirectoryTrait
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    /**
     * @return string a new empty directory, removed with everything in it after the test, whether it passes or not
     */
    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . uniqid('svg_optimizer_test_', true);
        mkdir($directory);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }

    /**
     * @param string $directory the directory to list
     *
     * @return list<string> the names of the entries of the directory, sorted
     */
    protected function listDirectory(string $directory): array
    {
        $entries = scandir($directory);
        $names = array_values(array_diff(false === $entries ? [] : $entries, ['.', '..']));

        sort($names);

        return $names;
    }

    #[After]
    protected function removeTemporaryDirectories(): void
    {
        foreach ($this->temporaryDirectories as $temporaryDirectory) {
            $this->removePath($temporaryDirectory);
        }

        $this->temporaryDirectories = [];
    }

    /**
     * @param string $path a file, a symbolic link or a directory, whose symbolic links are removed, not followed
     */
    private function removePath(string $path): void
    {
        if (is_link($path) && is_dir($path) && '\\' === \DIRECTORY_SEPARATOR) {
            rmdir($path);

            return;
        }

        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);

        foreach (array_diff(false === $entries ? [] : $entries, ['.', '..']) as $entry) {
            $this->removePath($path . \DIRECTORY_SEPARATOR . $entry);
        }

        rmdir($path);
    }
}
