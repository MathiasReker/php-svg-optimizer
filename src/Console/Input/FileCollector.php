<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @internal
 *
 * @no-named-arguments
 */
final class FileCollector
{
    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    public function collectSvgFiles(array $paths): array
    {
        $files = [];
        foreach ($paths as $path) {
            $files[] = $this->resolveSvgFiles($path);
        }

        return array_values(array_unique(array_merge(...$files)));
    }

    /**
     * @return list<string>
     */
    private function resolveSvgFiles(string $path): array
    {
        $realPath = file_exists($path) ? realpath($path) : false;

        if (false === $realPath) {
            return [];
        }

        if (is_dir($realPath)) {
            return (new Finder())
                ->in($realPath)
                ->files()
                ->withExtension(SvgTag::Svg->value)
                ->find();
        }

        if (is_file($realPath) && $this->hasSvgExtension($realPath)) {
            return [$realPath];
        }

        return [];
    }

    private function hasSvgExtension(string $filePath): bool
    {
        return SvgTag::Svg->value === mb_strtolower(pathinfo($filePath, \PATHINFO_EXTENSION));
    }
}
