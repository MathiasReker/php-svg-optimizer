<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Formatter;

/**
 * @no-named-arguments
 */
final readonly class XmlFormatter
{
    /**
     * Regex pattern to match whitespace between XML tags.
     *
     * This pattern matches any whitespace (spaces, tabs, newlines) that appears
     * between closing and opening tags, allowing for removal of unnecessary
     * whitespace in the final XML output.
     *
     * @see https://regex101.com/r/lyKDnR/1
     */
    private const string WHITESPACE_BETWEEN_TAGS_REGEX = '/>\s+</';

    /**
     * Removes line feeds (newlines, carriage returns) and tabs from the given content.
     *
     * This method removes all newline characters, including `\n` (LF), `\r` (CR), and tabs (`\t`),
     * while preserving spaces.
     *
     * @param string $content The content from which line feeds and tabs will be removed
     *
     * @return string The cleaned content with line feeds and tabs removed
     */
    public static function removeLineFeedsAndTabs(string $content): string
    {
        return str_replace(["\r", "\n", "\t"], '', $content);
    }

    /**
     * Removes unnecessary whitespace (spaces, tabs, newlines) between XML tags.
     *
     * Converts patterns like `>   <` into `><` to compact the XML output.
     *
     * @param string $content The XML content with potential inter-tag whitespace
     *
     * @return string The XML content with collapsed inter-tag whitespace
     */
    public static function removeWhitespaceBetweenTags(string $content): string
    {
        return (string) preg_replace(self::WHITESPACE_BETWEEN_TAGS_REGEX, '><', $content);
    }
}
