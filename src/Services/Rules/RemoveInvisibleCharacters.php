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

/**
 * Class to remove invisible characters from SVG files.
 */
final class RemoveInvisibleCharacters implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for removing invisible characters in HTML entity format.
     *
     * This regex removes all invisible or non-printing characters, including
     * control characters, whitespace, tabs, newlines, zero-width spaces, soft hyphens, etc.
     */
    private const string INVISIBLE_CHARACTERS_REGEX = '/&#x(?:200B|200C|200D|2028|2029|AD|0A|0D|09|D);/u';

    /**
     * Remove invisible characters from the SVG document.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     *
     * @throws XmlProcessingException When XML content cannot be saved or loaded
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $svgContent = $domDocument->saveXML();

        if (false === $svgContent) {
            throw new XmlProcessingException('Failed to save SVG XML content.');
        }

        try {
            $svgContent = $this->removeInvisibleCharacters($svgContent);
        } catch (\Exception $exception) {
            throw new XmlProcessingException('Failed to remove invisible characters from SVG content.', 0, $exception);
        }

        if (!$domDocument->loadXML($svgContent)) {
            throw new XmlProcessingException('Failed to load optimized XML content.');
        }
    }

    /**
     * Remove invisible characters from the SVG content.
     *
     * @param string $content The SVG content to process
     *
     * @return string The processed SVG content with invisible characters removed
     */
    private function removeInvisibleCharacters(string $content): string
    {
        return preg_replace(self::INVISIBLE_CHARACTERS_REGEX, '', $content) ?? $content;
    }
}
