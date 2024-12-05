<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;

final class RemoveDoctype implements SvgOptimizerRuleInterface
{
    /**
     * Regular expression to match the DOCTYPE declaration.
     *
     * This regex pattern is used to identify and remove DOCTYPE declarations
     * from the SVG content.
     *
     * @see https://regex101.com/r/DIe4La/1
     *
     * @var string
     */
    private const DOCTYPE_REGEX = '/<!DOCTYPE[^>]*>/i';

    /**
     * Optimizes the given DOMDocument by removing the DOCTYPE declaration.
     *
     * @param \DOMDocument $domDocument The DOMDocument to optimize
     *
     * @throws XmlProcessingException If an error occurs during processing
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $svgContent = $domDocument->saveXML();

        if (false === $svgContent) {
            throw new XmlProcessingException('Failed to save SVG XML content.');
        }

        $optimizedContent = $this->removeDoctype($svgContent);

        if (!$domDocument->loadXML($optimizedContent)) {
            throw new XmlProcessingException('Failed to load optimized XML content.');
        }
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
