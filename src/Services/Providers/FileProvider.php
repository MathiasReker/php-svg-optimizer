<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Providers;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;

final class FileProvider extends AbstractProvider
{
    /**
     * FileProvider constructor.
     *
     * @param string $inputFile The path to the input SVG file
     */
    public function __construct(private readonly string $inputFile)
    {
        parent::__construct();

        // Load the input content immediately to have it as a reference for metadata.
        $this->inputContent = $this->getInputContent();
    }

    /**
     * Get the content of the input file.
     *
     * @throws FileNotFoundException
     * @throws IOException
     */
    #[\Override]
    public function getInputContent(): string
    {
        if (!file_exists($this->inputFile)) {
            throw new FileNotFoundException(\sprintf('Input file does not exist: %s', $this->inputFile));
        }

        $svgContent = file_get_contents($this->inputFile);
        if (false === $svgContent) {
            throw new IOException(\sprintf('Failed to read input file content: %s', $this->inputFile));
        }

        return $svgContent;
    }

    /**
     * Load the input file into a DOMDocument instance.
     */
    #[\Override]
    public function loadContent(): \DOMDocument
    {
        return $this->domDocumentWrapper->loadFromFile($this->inputFile);
    }
}
