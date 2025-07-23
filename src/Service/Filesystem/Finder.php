<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Filesystem;

/**
 * @no-named-arguments
 */
final class Finder
{
    /**
     * The directory to search in.
     */
    private string $directory;

    /**
     * Whether to only find files.
     */
    private bool $onlyFiles = false;

    /**
     * The file extension to filter by.
     */
    private string $extension = '';

    /**
     * Constructor for Finder.
     *
     * @param string $directory The directory to search in
     */
    public function in(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Set the finder to only return files, not directories.
     */
    public function files(): self
    {
        $this->onlyFiles = true;

        return $this;
    }

    /**
     * Set the file extension to filter by.
     *
     * @param string $extension The file extension to filter by
     */
    public function withExtension(string $extension): self
    {
        $this->extension = mb_strtolower($extension);

        return $this;
    }

    /**
     * Find files in the specified directory based on the set criteria.
     *
     * @return list<string>
     */
    public function find(): array
    {
        if (!is_dir($this->directory)) {
            return [];
        }

        try {
            return $this->searchDirectory();
        } catch (\UnexpectedValueException) {
            return [];
        }
    }

    /**
     * Search the directory recursively for files matching the criteria.
     *
     * @return list<string>
     *
     * @throws \UnexpectedValueException
     */
    private function searchDirectory(): array
    {
        $results = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo) {
                continue;
            }

            if (!$this->matchesFilter($fileInfo)) {
                continue;
            }

            $realPath = $fileInfo->getRealPath();
            if (false === $realPath) {
                continue;
            }

            $results[] = $realPath;
        }

        return $results;
    }

    /**
     * Check if the file matches the filter criteria.
     *
     * @param \SplFileInfo $fileInfo The file information to check
     *
     * @return bool True if the file matches the filter, false otherwise
     */
    private function matchesFilter(\SplFileInfo $fileInfo): bool
    {
        if ($this->onlyFiles && !$fileInfo->isFile()) {
            return false;
        }

        if ('' !== $this->extension && mb_strtolower($fileInfo->getExtension()) !== $this->extension) {
            return false;
        }

        return true;
    }
}
