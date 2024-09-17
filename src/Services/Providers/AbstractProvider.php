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
     *
     * @var string
     */
    private const XML_DECLARATION_REGEX = '/^\s*<\?xml[^>]*\?>\s*/';

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
    abstract public function loadContent(): \DOMDocument;

    /**
     * Abstract method to get the input content.
     */
    abstract public function getInputContent(): string;

    /**
     * Save the optimized SVG content to a file.
     *
     * @param string $outputOutputPath The path to save the optimized SVG content to
     *
     * @throws IOException If the output file cannot be written
     */
    final public function saveToFile(string $outputOutputPath): self
    {
        $this->doDirectoryExists(\dirname($outputOutputPath));

        if (false === file_put_contents($outputOutputPath, $this->getOutputContent())) {
            throw new IOException(\sprintf('Failed to write optimized content to the output file: %s', $outputOutputPath));
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
    private function doDirectoryExists(string $directoryPath): void
    {
        if (!is_dir($directoryPath) && !mkdir($directoryPath, 0o755, true)) {
            throw new IOException(\sprintf('Failed to create directory: %s', $directoryPath));
        }
    }

    /**
     * Get the optimized SVG content.
     */
    final public function getOutputContent(): string
    {
        return $this->outputContent;
    }
}
