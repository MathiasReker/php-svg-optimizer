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
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Providers\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;
use MathiasReker\PhpSvgOptimizer\Services\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;

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
    protected string $outputContent;

    /**
     * The DOMDocumentWrapper instance.
     */
    protected readonly DomDocumentWrapper $domDocumentWrapper;

    /**
     * Input content to be loaded in child classes.
     */
    protected string $inputContent;

    /**
     * AbstractProvider constructor.
     */
    public function __construct()
    {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }

    /**
     * Optimize the provided DOMDocument instance.
     *
     * @throws XmlProcessingException
     */
    #[\Override]
    final public function optimize(\DOMDocument $domDocument): self
    {
        $xmlContent = $this->domDocumentWrapper->saveToString($domDocument);
        $xmlContent = preg_replace(self::XML_DECLARATION_REGEX, '', $xmlContent);
        if (null === $xmlContent) {
            throw new XmlProcessingException('Failed to process XML content.');
        }

        $this->outputContent = trim($xmlContent);

        return $this;
    }

    /**
     * Get metadata about the optimization.
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
     * Abstract method to load content into DOMDocument.
     */
    #[\Override]
    abstract public function loadContent(): \DOMDocument;

    /**
     * Abstract method to get the input content.
     */
    #[\Override]
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
     *
     * @throws IOException If the directory cannot be created
     */
    private function ensureDirectoryExists(string $directoryPath): bool
    {
        if (is_dir($directoryPath)) {
            return true;
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
