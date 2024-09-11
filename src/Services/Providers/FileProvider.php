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

class FileProvider implements SvgProviderInterface
{
    /**
     * Regex for XML declaration.
     *
     * @see https://regex101.com/r/uWTo0N/1
     *
     * @var string
     */
    private const XML_DECLARATION_REGEX = '/^\s*<\?xml[^>]*\?>\s*/';

    private string $output;

    public function __construct(private readonly string $inputFile, private readonly ?string $outputFile = null)
    {
    }

    public function getInputContent(): string
    {
        if (!file_exists($this->inputFile)) {
            throw new \InvalidArgumentException('Input file does not exist');
        }

        $svgContent = file_get_contents($this->inputFile);

        if (false === $svgContent) {
            throw new \RuntimeException('Failed to read input file content');
        }

        return $svgContent;
    }

    public function optimize(\DOMDocument $domDocument): self
    {
        $xmlContent = $domDocument->saveXML();

        if (false === $xmlContent) {
            throw new XmlProcessingException('Failed to save XML content');
        }

        $xmlContent = preg_replace(self::XML_DECLARATION_REGEX, '', $xmlContent);

        if (null !== $this->outputFile && false === file_put_contents($this->outputFile, $xmlContent)) {
            throw new \RuntimeException('Failed to write optimized content to the output file');
        }

        $this->output = trim((string) $xmlContent);

        return $this;
    }

    public function getOutputContent(): string
    {
        return $this->output;
    }

    public function load(): \DOMDocument
    {
        $domDocument = new \DOMDocument();

        if (!$domDocument->load($this->inputFile)) {
            throw new \RuntimeException('Failed to load SVG content into DOMDocument');
        }

        return $domDocument;
    }

    public function getMetaData(): MetaDataValueObject
    {
        $originalSize = filesize($this->inputFile);
        if (false === $originalSize) {
            throw new \RuntimeException('Failed to determine size of the input file');
        }

        if (null === $this->outputFile) {
            $optimizedSize = mb_strlen($this->output, '8bit');
        } else {
            $optimizedSize = filesize($this->outputFile);
            if (false === $optimizedSize) {
                throw new \RuntimeException('Failed to determine size of the output file');
            }
        }

        $metaData = new MetaData($originalSize, $optimizedSize);

        return $metaData->toValueObject();
    }
}
