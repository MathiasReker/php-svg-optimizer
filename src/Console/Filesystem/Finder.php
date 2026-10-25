<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Filesystem;

/**
 * @internal
 *
 * @no-named-arguments
 */
final class Finder
{
    private string $directory;

    private bool $onlyFiles = false;

    private string $extension = '';

    /** @var array<string, true> */
    private array $visitedDirectories = [];

    /**
     * @param string $directory The directory to search in
     */
    public function in(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    public function files(): self
    {
        $this->onlyFiles = true;

        return $this;
    }

    /**
     * @param string $extension The file extension to filter by
     */
    public function withExtension(string $extension): self
    {
        $this->extension = mb_strtolower($extension);

        return $this;
    }

    /**
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
     * @return list<string>
     *
     * @throws \UnexpectedValueException
     */
    private function searchDirectory(): array
    {
        $results = [];
        $this->visitedDirectories = [];
        $this->isFirstVisit(new \SplFileInfo($this->directory));

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator(
                    $this->directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
                ),
                fn (\SplFileInfo $fileInfo): bool => !$fileInfo->isDir() || $this->isFirstVisit($fileInfo),
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY,
            \RecursiveIteratorIterator::CATCH_GET_CHILD,
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo instanceof \SplFileInfo && $this->matchesFilter($fileInfo)) {
                $results[] = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $fileInfo->getPathname());
            }
        }

        return $results;
    }

    /**
     * @param \SplFileInfo $directory the directory to enter
     *
     * @return bool true if the directory was not entered before through another path, false otherwise
     */
    private function isFirstVisit(\SplFileInfo $directory): bool
    {
        $realPath = $directory->getRealPath();

        if (false === $realPath) {
            return true;
        }

        if (\array_key_exists($realPath, $this->visitedDirectories)) {
            return false;
        }

        $this->visitedDirectories[$realPath] = true;

        return true;
    }

    /**
     * @param \SplFileInfo $fileInfo The file information to check
     *
     * @return bool True if the file matches the filter, false otherwise
     */
    private function matchesFilter(\SplFileInfo $fileInfo): bool
    {
        return (!$this->onlyFiles || $fileInfo->isFile())
            && ('' === $this->extension || mb_strtolower($fileInfo->getExtension()) === $this->extension);
    }
}
