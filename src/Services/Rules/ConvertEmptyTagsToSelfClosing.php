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

final class ConvertEmptyTagsToSelfClosing implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for converting empty tags to self-closing tags without space before the slash.
     *
     * This regex matches any tag that is empty (e.g., <rect></rect>) and converts it to a self-closing tag (<rect/>).
     */
    private const string EMPTY_TAG_REGEX = '/<([a-zA-Z][a-zA-Z0-9-]*)([^>]*?)\s*><\/\1>/';

    /**
     * Convert empty tags to self-closing tags in the SVG document.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     *
     * @throws XmlProcessingException When XML content cannot be saved or loaded
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $svgContent = $domDocument->saveXML();

        if (false === $svgContent) {
            throw new XmlProcessingException('Failed to save SVG XML content.');
        }

        try {
            $svgContent = $this->convertEmptyTagsToSelfClosing($svgContent);
        } catch (\Exception $exception) {
            throw new XmlProcessingException('Failed to convert empty tags to self-closing tags.', 0, $exception);
        }

        if (!$domDocument->loadXML($svgContent)) {
            throw new XmlProcessingException('Failed to load optimized XML content.');
        }
    }

    /**
     * Convert empty tags to self-closing tags.
     *
     * This method processes the SVG content and converts tags with no content or child nodes
     * into self-closing tags (e.g., <rect/> instead of <rect></rect>).
     *
     * @param string $content The SVG content to process
     *
     * @return string The processed SVG content with empty tags converted to self-closing tags
     */
    private function convertEmptyTagsToSelfClosing(string $content): string
    {
        return (string) preg_replace(self::EMPTY_TAG_REGEX, '<$1$2/>', $content);
    }
}
