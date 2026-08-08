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
    private string $directory;

    private bool $onlyFiles = false;

    private string $extension = '';

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

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->directory,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
            )
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo) {
                continue;
            }

            if ($this->matchesFilter($fileInfo)) {
                $results[] = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $fileInfo->getPathname());
            }
        }

        return $results;
    }

    /**
     * @param \SplFileInfo $fileInfo The file information to check
     *
     * @return bool True if the file matches the filter, false otherwise
     */
    private function matchesFilter(\SplFileInfo $fileInfo): bool
    {
        if ($this->onlyFiles && !$fileInfo->isFile()) {
            return false;
        }

        return '' === $this->extension || mb_strtolower($fileInfo->getExtension()) === $this->extension;
    }
}
