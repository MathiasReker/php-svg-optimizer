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
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;
use MathiasReker\PhpSvgOptimizer\Services\Data\MetaData;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;

final class StringProvider implements SvgProviderInterface
{
    /**
     * Regex pattern for XML declaration.
     *
     * This regular expression matches the XML declaration at the start of an XML document.
     *
     * @see https://regex101.com/r/TxLqGh/1
     *
     * @var string
     */
    private const XML_DECLARATION_REGEX = '/^\s*<\?xml[^>]*\?>\s*/';

    /**
     * The optimized SVG content.
     */
    private string $outputContent;

    /**
     * The DOMDocumentWrapper instance.
     */
    private readonly DomDocumentWrapper $domDocumentWrapper;

    /**
     * Constructor for StringProvider.
     *
     * @param string $input The SVG content as a string
     */
    public function __construct(
        private readonly string $input,
    ) {
        $this->domDocumentWrapper = new DomDocumentWrapper();
    }

    /**
     * Optimize the provided DOMDocument instance.
     *
     * This method processes the DOMDocument to remove the XML declaration and updates the optimized content.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance to be optimized
     *
     * @throws XmlProcessingException If XML content cannot be saved or processed
     */
    public function optimize(\DOMDocument $domDocument): self
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
     * Get the optimized SVG content.
     *
     * @return string The optimized SVG content
     */
    public function getOutputContent(): string
    {
        return $this->outputContent;
    }

    /**
     * Load the input string into a DOMDocument instance.
     *
     * @return \DOMDocument The DOMDocument instance loaded with the input XML
     */
    public function load(): \DOMDocument
    {
        return $this->domDocumentWrapper->loadFromString($this->input);
    }

    /**
     * Get metadata about the optimization.
     *
     * This method computes metadata based on the input and output content.
     *
     * @return MetaDataValueObject Metadata about the optimization
     */
    public function getMetaData(): MetaDataValueObject
    {
        $metaData = new MetaData(
            mb_strlen($this->input, '8bit'),
            mb_strlen($this->outputContent, '8bit')
        );

        return $metaData->toValueObject();
    }

    /**
     * Get the input SVG content.
     *
     * @return string The input SVG content
     */
    public function getInputContent(): string
    {
        return $this->input;
    }
}
