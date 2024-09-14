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
use MathiasReker\PhpSvgOptimizer\Exception\FileLoadingException;
use MathiasReker\PhpSvgOptimizer\Exception\FileNotFoundException;
use MathiasReker\PhpSvgOptimizer\Exception\FileSizeException;
use MathiasReker\PhpSvgOptimizer\Exception\IOException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;
use MathiasReker\PhpSvgOptimizer\Services\Data\MetaData;

class FileProvider extends AbstractDomDocument implements SvgProviderInterface
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
    private string $output;

    /**
     * The content of the input file.
     */
    private readonly string $inputContent;

    /**
     * FileProvider constructor.
     *
     * @param string      $inputFile  The path to the input SVG file
     * @param string|null $outputFile The optional path to the output SVG file. If null, the output is not saved to a file
     */
    public function __construct(
        private readonly string $inputFile,
        private readonly ?string $outputFile = null
    ) {
        // Store the content without any optimization to have a reference for metadata.
        $this->inputContent = $this->getInputContent();
    }

    /**
     * Retrieve the content of the input file.
     *
     * @return string The content of the input file
     *
     * @throws FileNotFoundException If the input file does not exist
     * @throws IOException           If the content cannot be read from the input file
     */
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
     * Optimize the given DOMDocument and save the result to the output file if specified.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance to optimize
     *
     * @return self The FileProvider instance for method chaining
     *
     * @throws XmlProcessingException If the XML content cannot be saved
     * @throws IOException            If the optimized content cannot be written to the output file
     */
    public function optimize(\DOMDocument $domDocument): self
    {
        $xmlContent = $this->saveToString($domDocument);
        if (false === $xmlContent) {
            throw new XmlProcessingException('Failed to save XML content as a string.');
        }

        $xmlContent = (string) preg_replace(self::XML_DECLARATION_REGEX, '', $xmlContent);

        if (null !== $this->outputFile) {
            $this->ensureDirectoryExists(\dirname($this->outputFile));

            if (false === file_put_contents($this->outputFile, $xmlContent)) {
                throw new IOException(\sprintf('Failed to write optimized content to the output file: %s', $this->outputFile));
            }
        }

        $this->output = trim($xmlContent);

        return $this;
    }

    /**
     * Ensures that the directory for the output file exists. Creates it if necessary.
     *
     * @param string $directoryPath The directory path to check/create.
     *
     * @throws IOException If the directory cannot be created.
     */
    private function ensureDirectoryExists(string $directoryPath): void
    {
        if (!is_dir($directoryPath) && !mkdir($directoryPath, 0o755, true)) {
            throw new IOException(\sprintf('Failed to create directory: %s', $directoryPath));
        }
    }

    /**
     * Get the optimized SVG content.
     *
     * @return string The optimized SVG content
     */
    public function getOutputContent(): string
    {
        return $this->output;
    }

    /**
     * Load the input file into a DOMDocument instance.
     *
     * @return \DOMDocument The DOMDocument instance loaded with the input SVG file
     *
     * @throws FileLoadingException If the SVG content cannot be loaded into a DOMDocument
     */
    public function load(): \DOMDocument
    {
        $domDocument = $this->loadFromFile($this->inputFile);

        if (!$domDocument instanceof \DOMDocument) {
            $errorMessage = \sprintf('Unable to load SVG content from file: %s', $this->inputFile);

            throw new FileLoadingException($errorMessage);
        }

        return $domDocument;
    }

    /**
     * Get metadata about the input and output files.
     *
     * @return MetaDataValueObject The metadata value object containing the sizes of the input and output files
     *
     * @throws FileSizeException If the size of the input or output files cannot be determined
     */
    public function getMetaData(): MetaDataValueObject
    {
        $originalSize = mb_strlen($this->inputContent, '8bit');
        if (false === $originalSize) {
            throw new FileSizeException(\sprintf('Failed to determine size of the input file: %s', $this->inputFile));
        }

        $optimizedSize = null === $this->outputFile
            ? mb_strlen($this->output, '8bit')
            : filesize($this->outputFile);

        if (false === $optimizedSize) {
            if (null !== $this->outputFile) {
                throw new FileSizeException(\sprintf('Failed to determine size of the output file: %s', $this->outputFile));
            }

            throw new FileSizeException('Failed to determine size of the output.');
        }

        $metaData = new MetaData($originalSize, $optimizedSize);

        return $metaData->toValueObject();
    }
}
