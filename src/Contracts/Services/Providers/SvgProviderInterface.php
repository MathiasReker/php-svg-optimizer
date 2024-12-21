<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Contracts\Services\Providers;

use MathiasReker\PhpSvgOptimizer\ValueObjects\MetaDataValueObject;

/**
 * Interface SvgProviderInterface.
 *
 * Defines the contract for an SVG content provider, including loading, optimizing,
 * and retrieving SVG content and metadata.
 */
interface SvgProviderInterface
{
    /**
     * Loads the SVG content into a DOMDocument instance.
     *
     * This method should return a DOMDocument instance that represents the
     * SVG content to be processed.
     *
     * @return \DOMDocument The DOMDocument instance representing the loaded SVG content
     */
    public function loadContent(): \DOMDocument;

    /**
     * Optimizes the provided DOMDocument instance.
     *
     * This method performs optimization on the SVG content represented by the
     * given DOMDocument instance. It may modify the instance in place or
     * prepare it for further processing.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG content to be optimized
     *
     * @return self Returns the current instance to allow method chaining
     */
    public function optimize(\DOMDocument $domDocument): self;

    /**
     * Retrieves the raw input SVG content before optimization.
     *
     * This method returns the original SVG content as a string, prior to any
     * optimization being applied.
     *
     * @return string The raw input SVG content
     */
    public function getInputContent(): string;

    /**
     * Retrieves the optimized SVG content after processing.
     *
     * This method returns the SVG content as a string after optimization has
     * been applied.
     *
     * @return string The optimized SVG content
     */
    public function getOutputContent(): string;

    /**
     * Retrieves metadata about the optimization process.
     *
     * This method provides details about the sizes of the original and
     * optimized SVG content, including the amount of bytes saved and the
     * percentage of space saved.
     *
     * @return MetaDataValueObject The metadata about the optimization process
     */
    public function getMetaData(): MetaDataValueObject;

    /**
     * Save the optimized SVG content to a file.
     *
     * This method saves the optimized SVG content to the specified file path.
     *
     * @param string $path The path to save the optimized SVG content to
     */
    public function saveToFile(string $path): self;
}
