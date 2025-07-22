<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\Service\Filesystem\Finder;

/**
 * @no-named-arguments
 */
final class FileCollector
{
    /**
     * The file extension for SVG files.
     */
    private const string SVG_EXTENSION = 'svg';

    /**
     * Collect unique .svg files from the given paths.
     *
     * @param list<string> $paths
     *
     * @return list<string>
     */
    public function collectSvgFiles(array $paths): array
    {
        $realFiles = [];

        foreach ($paths as $path) {
            $files = $this->resolveSvgFiles($path);

            foreach ($files as $file) {
                $realFiles[$file] = true;
            }
        }

        return array_keys($realFiles);
    }

    /**
     * Resolve all .svg files from a given path (file or directory).
     *
     * @return list<string>
     */
    private function resolveSvgFiles(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $realPath = realpath($path);
        if (false === $realPath) {
            return [];
        }

        if (is_dir($realPath)) {
            return (new Finder())
                ->in($realPath)
                ->files()
                ->withExtension(self::SVG_EXTENSION)
                ->find();
        }

        if (is_file($realPath) && $this->hasSvgExtension($realPath)) {
            return [$realPath];
        }

        return [];
    }

    /**
     * Checks if a file has a .svg extension.
     */
    private function hasSvgExtension(string $filePath): bool
    {
        return self::SVG_EXTENSION === mb_strtolower(pathinfo($filePath, \PATHINFO_EXTENSION));
    }
}
