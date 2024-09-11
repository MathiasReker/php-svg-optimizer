<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Providers;

use MathiasReker\PhpSvgOptimizer\Contracts\Services\Providers\SvgProviderInterface;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Models\MetaDataValueObject;
use MathiasReker\PhpSvgOptimizer\Services\Data\MetaData;

class StringProvider implements SvgProviderInterface
{
    /**
     * Regex for XML declaration.
     *
     * @see https://regex101.com/r/TxLqGh/1
     *
     * @var string
     */
    private const XML_DECLARATION_REGEX = '/^\s*<\?xml[^>]*\?>\s*/';

    private string $output;

    /**
     * @throws \RuntimeException
     */
    public function __construct(private readonly string $input)
    {
    }

    /**
     * @throws \RuntimeException
     */
    public function optimize(\DOMDocument $domDocument): self
    {
        $xmlContent = $domDocument->saveXML();

        if (false === $xmlContent) {
            throw new XmlProcessingException('Failed to save XML content.');
        }

        $xmlContent = preg_replace(self::XML_DECLARATION_REGEX, '', $xmlContent);

        if (null === $xmlContent) {
            throw new XmlProcessingException('Failed to process XML content.');
        }

        $this->output = trim($xmlContent);

        return $this;
    }

    public function getOutputContent(): string
    {
        return $this->output;
    }

    public function load(): \DOMDocument
    {
        $domDocument = new \DOMDocument();
        $domDocument->formatOutput = false;
        $domDocument->preserveWhiteSpace = false;
        $domDocument->loadXML($this->input);

        return $domDocument;
    }

    public function getMetaData(): MetaDataValueObject
    {
        $metaData = new MetaData(mb_strlen($this->input, '8bit'), mb_strlen($this->output, '8bit'));

        return $metaData->toValueObject();
    }

    public function getInputContent(): string
    {
        return $this->input;
    }
}
