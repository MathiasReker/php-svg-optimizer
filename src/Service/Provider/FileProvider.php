<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Provider;

use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;

/**
 * @no-named-arguments
 */
final class FileProvider extends AbstractProvider
{
    /**
     * The path to the input SVG file.
     *
     * @param string $inputFile The path to the input SVG file
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws IOException           If the file does not exist or cannot be read
     */
    public function __construct(
        private readonly string $inputFile,
    ) {
        parent::__construct();

        // Load the input content immediately to have it as a reference for metadata.
        $this->inputContent = $this->getInputContent();
    }

    /**
     * Get the content of the input file.
     *
     * @throws FileNotFoundException If the file does not exist
     * @throws IOException           If the file does not exist or cannot be read
     */
    #[\Override]
    public function getInputContent(): string
    {
        if (!file_exists($this->inputFile) || !is_file($this->inputFile)) {
            throw new FileNotFoundException(\sprintf('Input file does not exist: %s', $this->inputFile));
        }

        if (!is_readable($this->inputFile)) {
            throw new IOException(\sprintf('Input file is not readable: %s', $this->inputFile));
        }

        $content = file_get_contents($this->inputFile);

        if (false === $content) {
            throw new IOException(\sprintf('Failed to read input file content: %s', $this->inputFile));
        }

        return $content;
    }

    /**
     * Load the input file into a \DOMDocument instance.
     *
     * @throws XmlProcessingException If the XML processing fails
     */
    #[\Override]
    public function loadContent(): \DOMDocument
    {
        return $this->domDocumentWrapper->loadFromFile($this->inputFile);
    }
}
