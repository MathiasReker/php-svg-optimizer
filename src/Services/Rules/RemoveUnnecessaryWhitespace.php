<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

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

        $svgContent = preg_replace('/\s+/', ' ', $svgContent);

        if (null === $svgContent) {
            throw new RegexProcessingException('An error occurred during the preg_replace operation.');
        }

        $svgContent = str_replace('> <', '><', $svgContent);

        if (false === $domDocument->loadXML($svgContent)) {
            throw new XmlProcessingException('Failed to load optimized XML content.');
        }
    }
}
