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
use MathiasReker\PhpSvgOptimizer\Exception\RegexProcessingException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;

final class RemoveUnnecessaryWhitespace implements SvgOptimizerRuleInterface
{
    /**
     * Regex pattern for matching attribute values.
     *
     * This regex captures attributes and their values, allowing whitespace
     * to be removed within the values.
     *
     * @see https://regex101.com/r/6oZWnx/1
     *
     * @var string
     */
    private const ATTRIBUTE_VALUE_REGEX = '/(\S+)=\s*"([^"]*)"/';

    /**
     * Regex pattern for matching style attribute values.
     *
     * This regex captures style attributes and their values, allowing
     * whitespace to be removed within the style values.
     *
     * @see https://regex101.com/r/JFLCQm/1
     *
     * @var string
     */
    private const STYLE_ATTRIBUTE_REGEX = '/style\s*=\s*"([^"]*)"/';

    /**
     * Regex pattern for matching whitespace characters.
     *
     * This regex is used to find and replace multiple whitespace characters
     * within attribute values.
     *
     * @see https://regex101.com/r/pxX489/1
     *
     * @var string
     */
    private const WHITESPACE_REGEX = '/\s+/';

    /**
     * Remove unnecessary whitespace from the SVG document.
     *
     * This method saves the current SVG content, processes it to remove
     * unnecessary whitespace, and then reloads the optimized content back
     * into the DOMDocument.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
     *
     * @throws XmlProcessingException   When XML content cannot be saved or loaded
     * @throws RegexProcessingException When regex processing fails
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $svgContent = $domDocument->saveXML();

        if (false === $svgContent) {
            throw new XmlProcessingException('Failed to save SVG XML content.');
        }

        try {
            $svgContent = $this->removeAttributeValueWhitespace($svgContent);
            $svgContent = $this->removeStyleAttributeWhitespace($svgContent);
        } catch (\Exception $exception) {
            throw new RegexProcessingException('Failed to process SVG content with regex.', 0, $exception);
        }

        if (!$domDocument->loadXML($svgContent)) {
            throw new XmlProcessingException('Failed to load optimized XML content.');
        }
    }

    /**
     * Remove unnecessary whitespace inside attribute values.
     *
     * This method processes the SVG content to trim and reduce whitespace
     * within attribute values.
     *
     * @param string $content The SVG content to process
     *
     * @return string The processed SVG content with reduced whitespace in attribute values
     */
    private function removeAttributeValueWhitespace(string $content): string
    {
        return preg_replace_callback(
            self::ATTRIBUTE_VALUE_REGEX,
            static fn (array $matches): string => \sprintf(
                '%s="%s"',
                $matches[1],
                preg_replace(self::WHITESPACE_REGEX, ' ', trim($matches[2]))
            ),
            $content
        ) ?? $content;
    }

    /**
     * Remove all whitespace inside style attribute values.
     *
     * This method processes the SVG content to remove all whitespace within
     * style attribute values, which helps to compact the style definitions.
     *
     * @param string $content The SVG content to process
     *
     * @return string The processed SVG content with whitespace removed from style attributes
     */
    private function removeStyleAttributeWhitespace(string $content): string
    {
        return preg_replace_callback(
            self::STYLE_ATTRIBUTE_REGEX,
            static fn (array $matches): string => \sprintf(
                'style="%s"',
                str_replace(' ', '', $matches[1])
            ),
            $content
        ) ?? $content;
    }
}
