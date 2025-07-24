<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Provider;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Provider\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\ValueObject\MetaDataValueObject;

/**
 * @no-named-arguments
 */
abstract class AbstractProvider implements SvgProviderInterface
{
    /**
     * Regex pattern for XML declaration.
     *
     * @see https://regex101.com/r/uWTo0N/1
     */
    private const string XML_DECLARATION_REGEX = '/^\s*<\?xml[^>]*\?>\s*/';

    /**
     * Default directory permissions for newly created directories.
     */
    private const int DEFAULT_DIRECTORY_PERMISSION = 0o755;

    /**
     * Holds the optimized SVG content.
     */
    protected string $outputContent = '';

    /**
     * The DOMDocumentWrapper instance.
     */
    protected readonly DomDocumentWrapper $domDocumentWrapper;

    /**
     * Input content to be loaded in child classes.
     */
    protected string $inputContent = '';

    /**
     * Constructor for the AbstractProvider class.
     *
     * Initializes the DomDocumentWrapper instance.
     */
    public function __construct()
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }

    /**
     * Optimize the provided \DOMDocument instance.
     *
     * @throws XmlProcessingException If the XML processing fails
     */
    #[\Override]
    final public function optimize(\DOMDocument $domDocument): self
    {
        $content = $this->domDocumentWrapper->saveToString($domDocument);
        $content = preg_replace(self::XML_DECLARATION_REGEX, '', $content);
        if (null === $content) {
            throw new XmlProcessingException('Failed to process XML content.');
        }

        $this->outputContent = trim($content);

        return $this;
    }

    /**
     * Get metadata about the optimization.
     *
     * @throws \InvalidArgumentException If the original size is less than or equal to 0
     */
    #[\Override]
    final public function getMetaData(): MetaDataValueObject
    {
        $metaData = new MetaData(
            mb_strlen($this->inputContent, '8bit'),
            mb_strlen($this->outputContent, '8bit')
        );

        return $metaData->toValueObject();
    }

    /**
     * Abstract method to load content into \DOMDocument.
     */
    abstract public function loadContent(): \DOMDocument;

    /**
     * Abstract method to get the input content.
     */
    abstract public function getInputContent(): string;

    /**
     * Save the optimized SVG content to a file.
     *
     * @param string $path The path to save the optimized SVG content to
     *
     * @throws IOException If the output file cannot be written
     */
    #[\Override]
    final public function saveToFile(string $path): self
    {
        if (!$this->ensureDirectoryExists(\dirname($path))) {
            throw new IOException(\sprintf('Failed to create directory for output file: %s', $path));
        }

        if (false === file_put_contents($path, $this->getOutputContent())) {
            throw new IOException(\sprintf('Failed to write optimized content to the output file: %s', $path));
        }

        return $this;
    }

    /**
     * Ensures that the directory for the output file exists. Creates it if necessary.
     *
     * @param string $directoryPath The directory path to check/create
     */
    private function ensureDirectoryExists(string $directoryPath): bool
    {
        if (is_dir($directoryPath)) {
            return true;
        }

        $parent = \dirname($directoryPath);
        if (!is_dir($parent)) {
            return false;
        }

        return mkdir($directoryPath, self::DEFAULT_DIRECTORY_PERMISSION, true);
    }

    /**
     * Get the optimized SVG content.
     */
    #[\Override]
    final public function getOutputContent(): string
    {
        return $this->outputContent;
    }
}
