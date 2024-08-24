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

class StringProvider implements SvgProviderInterface
{
    private string $content;

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

        $xmlContent = preg_replace('/^\s*<\?xml[^>]*\?>\s*/', '', $xmlContent);

        if (null === $xmlContent) {
            throw new XmlProcessingException('Failed to process XML content.');
        }

        $this->content = trim($xmlContent);

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
        $domDocument->loadXML($this->input);

        return $domDocument;
    }

    /**
     * @return array{ originalSize: int, optimizedSize: int, savedBytes: int, savedPercentage: float}
     */
    public function getMetaData(): array
    {
        $metaData = new MetaData(mb_strlen($this->input), mb_strlen($this->content));

        return $metaData->toArray();
    }

    public function getInputContent(): string
    {
        return $this->input;
    }
}
