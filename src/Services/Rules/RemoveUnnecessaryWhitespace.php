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
use MathiasReker\PhpSvgOptimizer\Exceptions\RegexProcessingException;
use MathiasReker\PhpSvgOptimizer\Exceptions\XmlProcessingException;

class RemoveUnnecessaryWhitespace implements SvgOptimizerRuleInterface
{
    /**
     * Remove unnecessary whitespace from the SVG document.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     *
     * @throws XmlProcessingException
     * @throws RegexProcessingException
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $svgContent = $domDocument->saveXML();

        if (false === $svgContent) {
            throw new XmlProcessingException('Failed to save SVG XML content.');
        }

        // Remove unnecessary whitespace inside attribute values
        $svgContent = (string) preg_replace_callback(
            '/(\S+)=\s*"([^"]*)"/',
            static fn ($matches): string => \sprintf('%s="%s"', $matches[1], preg_replace('/\s+/', ' ', trim($matches[2]))),
            $svgContent
        );

        // Remove all whitespace inside style attribute values
        $svgContent = (string) preg_replace_callback(
            '/style\s*=\s*"([^"]*)"/',
            static fn ($matches): string => \sprintf('style="%s"', str_replace(' ', '', $matches[1])),
            $svgContent
        );

        if (false === $domDocument->loadXML($svgContent)) {
            throw new XmlProcessingException('Failed to load optimized XML content.');
        }
    }
}
