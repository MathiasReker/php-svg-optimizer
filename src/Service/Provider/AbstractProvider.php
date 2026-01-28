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
     * Default directory permissions for newly created directories.
     */
    private const int DIRECTORY_PERMISSION = 0o755;

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
     * Holds the time spent optimizing, in seconds.
     */
    protected float $optimizationTime = 0.0;

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
     * @throws XmlProcessingException
     */
    #[\Override]
    final public function optimize(\DOMDocument $domDocument): self
    {
        $this->runWithTiming(
            function () use ($domDocument): void {
                $this->outputContent = $this->domDocumentWrapper->saveToString($domDocument);
            }
        );

        return $this;
    }

    /**
     * Measures execution time of a callback and stores it.
     *
     * @param callable $callback The operation to measure
     *
     * @param-immediately-invoked-callable $callback
     */
    private function runWithTiming(callable $callback): void
    {
        $start = microtime(true);
        $callback();
        $end = microtime(true);

        $this->optimizationTime += ($end - $start);
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
            mb_strlen($this->outputContent, '8bit'),
            $this->optimizationTime,
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

        return mkdir($directoryPath, self::DIRECTORY_PERMISSION, true);
    }

    /**
     * Get the optimized SVG content.
     */
    #[\Override]
    final public function getOutputContent(): string
    {
        return $this->outputContent;
    }

    /**
     * Serialize a \DOMDocument to a string without the XML declaration.
     *
     * @param \DOMDocument $domDocument The \DOMDocument to serialize
     *
     * @return string The serialized XML content
     */
    final public function serialize(\DOMDocument $domDocument): string
    {
        return $this->domDocumentWrapper->saveToString($domDocument);
    }
}
