<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Contract\Service\Provider;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;

/**
 * @no-named-arguments
 */
interface SvgProviderInterface
{
    /**
     * @return \DOMDocument The \DOMDocument instance representing the loaded SVG content
     */
    public function loadContent(): \DOMDocument;

    /**
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG content to be optimized
     *
     * @return self Returns the current instance to allow method chaining
     */
    public function optimize(\DOMDocument $domDocument): self;

    /**
     * @return string The raw input SVG content
     */
    public function getInputContent(): string;

    /**
     * @return string The optimized SVG content
     */
    public function getOutputContent(): string;

    /**
     * @param string $path The path to save the optimized SVG content to
     */
    public function saveToFile(string $path): self;

    /**
     * @param \DOMDocument $domDocument The \DOMDocument instance to serialize
     *
     * @return string The serialized XML content
     *
     * @throws XmlProcessingException If the XML content cannot be processed
     */
    public function serialize(\DOMDocument $domDocument): string;
}
