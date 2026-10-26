<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Console;

use MathiasReker\PhpSvgOptimizer\Console\Output\Printer;
use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\File\SvgFile;
use MathiasReker\PhpSvgOptimizer\Metrics;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class FileOptimizer
{
    /**
     * @param SvgOptimizer $svgOptimizer the optimizer with the selected rules, used for every file
     * @param Printer      $printer      where the result of every file is printed
     * @param RunSummary   $runSummary   the totals of the run, to which every file is added
     * @param bool         $writing      whether a file that gets smaller is written, or only measured
     */
    public function __construct(
        private SvgOptimizer $svgOptimizer,
        private Printer $printer,
        private RunSummary $runSummary,
        private bool $writing,
    ) {}

    /**
     * @param string $path the SVG file to optimize
     *
     * @throws IOException                   If the file does not exist, cannot be read or cannot be written
     * @throws InvalidSvgException           If the file is not a well-formed SVG document
     * @throws RiskyRulesNotAllowedException If a selected rule is risky and risky rules are not allowed
     */
    public function optimizePath(string $path): void
    {
        $original = SvgFile::read($path);
        $optimizationResult = $this->svgOptimizer->optimizeString($original);
        $metrics = $optimizationResult->getMetrics();

        if ($metrics->getSavedSizeInBytes() < 0) {
            $this->runSummary->add(new Metrics($metrics->getOriginalSizeInBytes(), $metrics->getOriginalSizeInBytes(), $metrics->getDurationInSeconds()));
            $this->printer->printSkippedFile($path);

            return;
        }

        $changed = $optimizationResult->getContent() !== $original;

        if ($changed && $this->writing) {
            SvgFile::write($path, $optimizationResult->getContent());
        }

        $this->runSummary->add($metrics);

        if ($changed) {
            $this->runSummary->addChange();
        }

        $this->printer->printFileResult($path, $metrics->getSavedPercentage());
    }
}
