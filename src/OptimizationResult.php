<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer;

use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\File\SvgFile;

/**
 * @api
 *
 * @no-named-arguments
 */
final readonly class OptimizationResult
{
    /**
     * @param string  $content the optimized SVG
     * @param Metrics $metrics the sizes and the duration of the optimization
     *
     * @internal
     */
    public function __construct(
        private string $content,
        private Metrics $metrics,
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $path the file to write the optimized SVG to; missing directories are created
     *
     * @throws IOException If the file cannot be written
     */
    public function saveToFile(string $path): void
    {
        SvgFile::write($path, $this->content);
    }

    public function getMetrics(): Metrics
    {
        return $this->metrics;
    }
}
