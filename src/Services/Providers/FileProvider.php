<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Providers;

use MathiasReker\PhpSvgOptimizer\Exceptions\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Services\MetaData;

class FileProvider implements SvgProviderInterface
{
    private string $content;

    public function __construct(private readonly string $inputFile, private readonly string $outputFile)
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

        // Remove XML declaration if present
        $xmlContent = preg_replace('/^\s*<\?xml[^>]*\?>\s*/', '', $xmlContent);

        if (false === file_put_contents($this->outputFile, $xmlContent)) {
            throw new \RuntimeException('Failed to write optimized content to the output file');
        }

        $this->content = trim((string) $xmlContent);

        return $this;
    }

    public function getOutputContent(): string
    {
        return $this->content;
    }

    public function load(): \DOMDocument
    {
        $domDocument = new \DOMDocument();
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = false;

        if (!$domDocument->load($this->inputFile)) {
            throw new \RuntimeException('Failed to load SVG content into DOMDocument');
        }

        return $domDocument;
    }

    /**
     * @return array{ originalSize: int, optimizedSize: int, savedBytes: int, savedPercentage: float}
     */
    public function getMetaData(): array
    {
        $originalSize = filesize($this->inputFile);
        if (false === $originalSize) {
            throw new \RuntimeException('Failed to determine size of the input file');
        }

        $optimizedSize = filesize($this->outputFile);
        if (false === $optimizedSize) {
            throw new \RuntimeException('Failed to determine size of the output file');
        }

        $metaData = new MetaData($originalSize, $optimizedSize);

        return $metaData->toArray();
    }
}
