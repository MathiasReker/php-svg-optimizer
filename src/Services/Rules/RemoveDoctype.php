<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Services\Util\XmlProcessor;

final readonly class RemoveDoctype implements SvgOptimizerRuleInterface
{
    /**
     * Regular expression to match the DOCTYPE declaration.
     *
     * This regex pattern is used to identify and remove DOCTYPE declarations
     * from the SVG content.
     *
     * @see https://regex101.com/r/DIe4La/1
     */
    private const string DOCTYPE_REGEX = '/<!DOCTYPE[^>]*>/i';

    private XmlProcessor $xmlProcessor;

    public function __construct()
    {
        $this->xmlProcessor = new XmlProcessor();
    }

    /**
     * Optimizes the given DOMDocument by removing the DOCTYPE declaration.
     *
     * @param \DOMDocument $domDocument The DOMDocument to optimize
     *
     * @throws XmlProcessingException If an error occurs during processing
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->xmlProcessor->process($domDocument, fn (string $content): string => $this->removeDoctype($content));
    }

    /**
     * Removes the DOCTYPE declaration from the SVG content.
     *
     * @param string $svgContent The SVG content as a string
     *
     * @return string The SVG content without the DOCTYPE declaration
     */
    private function removeDoctype(string $svgContent): string
    {
        return (string) preg_replace(self::DOCTYPE_REGEX, '', $svgContent);
    }
}
