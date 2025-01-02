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

final readonly class ConvertEmptyTagsToSelfClosing implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for converting empty tags to self-closing tags without space before the slash.
     *
     * This regex matches any tag that is empty (e.g., <rect></rect>) and converts it to a self-closing tag (<rect/>).
     */
    private const string EMPTY_TAG_REGEX = '/<([a-zA-Z][a-zA-Z0-9-]*)([^>]*?)\s*><\/\1>/';

    /**
     * Regex pattern for removing space before the slash in self-closing tags.
     *
     * This regex matches self-closing tags with a space before the slash and removes the space.
     */
    private const string SELF_CLOSING_REGEX = '/<([a-zA-Z][a-zA-Z0-9-]*)([^>]*?)\s*\/>/';

    private XmlProcessor $xmlProcessor;

    public function __construct()
    {
        $this->xmlProcessor = new XmlProcessor();
    }

    /**
     * Convert empty tags to self-closing tags in the SVG document.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     *
     * @throws XmlProcessingException When XML content cannot be saved or loaded
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->xmlProcessor->process($domDocument, fn (string $content): string => $this->convertEmptyTagsToSelfClosing($content));
    }

    /**
     * Convert empty tags to self-closing tags and remove spaces before slashes in self-closing tags.
     *
     * This method processes the SVG content and converts tags with no content or child nodes
     * into self-closing tags (e.g., <rect/> instead of <rect></rect>) and ensures there's no space
     * before the slash in self-closing tags (e.g., <rect/> instead of <rect />).
     *
     * @param string $content The SVG content to process
     *
     * @return string The processed SVG content with empty tags converted to self-closing tags
     */
    private function convertEmptyTagsToSelfClosing(string $content): string
    {
        $content = preg_replace(self::EMPTY_TAG_REGEX, '<$1$2/>', $content) ?? $content;

        return preg_replace(self::SELF_CLOSING_REGEX, '<$1$2/>', $content) ?? $content;
    }
}
