<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class FileFinder
{
    private const array IGNORED_DIRECTORIES = ['.git', '.hg', '.svn', 'node_modules', 'vendor'];

    private const string CURRENT_DIRECTORY = '.';

    private const string SEPARATOR = '/';

    private const string EXTENSION = 'svg';

    /**
     * @param list<string> $paths the files and directories to search, as the user wrote them
     *
     * @return list<string> the SVG files in the order of the paths, each path as the user wrote it followed by what
     *                      leads to the file; directories are searched in name order, without .git, .hg, .svn,
     *                      node_modules and vendor, and without symbolic links to files or directories
     *
     * @throws \InvalidArgumentException If a path does not exist or is a file without the .svg extension
     * @throws \UnexpectedValueException If a directory cannot be read
     */
    public function find(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            array_push($files, ...$this->findIn($path));
        }

        return $files;
    }

    /**
     * @param string $path a file or directory, as the user wrote it
     *
     * @return list<string>
     *
     * @throws \InvalidArgumentException If the path does not exist or is a file without the .svg extension
     * @throws \UnexpectedValueException If a directory cannot be read
     */
    private function findIn(string $path): array
    {
        if (is_dir($path)) {
            return $this->scan($path, self::CURRENT_DIRECTORY === $path ? '' : $this->withSeparator($path));
        }

        if (!is_file($path)) {
            throw new \InvalidArgumentException(\sprintf('"%s" does not exist.', $path));
        }

        if (!$this->hasSvgExtension($path)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not an SVG file.', $path));
        }

        return [$path];
    }

    /**
     * @param string $directory the directory to scan
     * @param string $prefix    what leads to the directory, ending with a separator, or an empty string
     *
     * @return list<string>
     *
     * @throws \UnexpectedValueException If the directory cannot be read
     */
    private function scan(string $directory, string $prefix): array
    {
        $files = [];

        foreach ($this->entries($directory) as $entry) {
            array_push($files, ...$this->scanEntry($entry, $prefix));
        }

        return $files;
    }

    /**
     * @param \SplFileInfo $entry  a file, directory or link in a directory
     * @param string       $prefix what leads to the directory, ending with a separator, or an empty string
     *
     * @return list<string>
     *
     * @throws \UnexpectedValueException If the entry is a directory that cannot be read
     */
    private function scanEntry(\SplFileInfo $entry, string $prefix): array
    {
        $name = $entry->getFilename();

        if ($entry->isDir()) {
            return $entry->isLink() || \in_array($name, self::IGNORED_DIRECTORIES, true)
                ? []
                : $this->scan($entry->getPathname(), $prefix . $name . self::SEPARATOR);
        }

        return $entry->isFile() && !$entry->isLink() && $this->hasSvgExtension($name) ? [$prefix . $name] : [];
    }

    /**
     * @param string $directory the directory to list
     *
     * @return list<\SplFileInfo> the entries of the directory in name order
     *
     * @throws \UnexpectedValueException If the directory cannot be read
     */
    private function entries(string $directory): array
    {
        $entries = [];

        foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry instanceof \SplFileInfo) {
                $entries[] = $entry;
            }
        }

        usort($entries, static fn (\SplFileInfo $a, \SplFileInfo $b): int => strcmp($a->getFilename(), $b->getFilename()));

        return $entries;
    }

    private function withSeparator(string $directory): string
    {
        $last = mb_substr($directory, -1);

        return self::SEPARATOR === $last || \DIRECTORY_SEPARATOR === $last ? $directory : $directory . self::SEPARATOR;
    }

    private function hasSvgExtension(string $path): bool
    {
        return 0 === strcasecmp(pathinfo($path, \PATHINFO_EXTENSION), self::EXTENSION);
    }
}
