<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Providers;

use DOMDocument;

interface SvgProviderInterface
{
    /**
     * Loads the SVG content into a DOMDocument instance.
     *
     * @return \DOMDocument the DOMDocument instance representing the loaded SVG content
     */
    public function load(): \DOMDocument;

    /**
     * Optimizes the given DOMDocument instance.
     *
     * This method performs the optimization on the SVG content represented
     * by the provided DOMDocument. The optimization process may modify the
     * DOMDocument instance or prepare it for further processing.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG content to be optimized
     *
     * @return self returns the current instance to allow method chaining
     */
    public function optimize(\DOMDocument $domDocument): self;

    /**
     * Retrieves the input content before optimization.
     *
     * This method returns the raw SVG content that was initially provided
     * to the provider, before any optimization has taken place.
     *
     * @return string the raw input SVG content
     */
    public function getInputContent(): string;

    /**
     * Retrieves the optimized content after processing.
     *
     * This method returns the SVG content after optimization has been applied.
     *
     * @return string the optimized SVG content
     */
    public function getOutputContent(): string;

    /**
     * Retrieves metadata about the optimization process.
     *
     * This method provides details about the sizes of the original and
     * optimized SVG content, as well as the amount of bytes saved and the
     * percentage of space saved.
     *
     * @return array{ originalSize: int, optimizedSize: int, savedBytes: int, savedPercentage: float}
     */
    public function getMetaData(): array;
}
