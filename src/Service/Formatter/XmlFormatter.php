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
     * @see https://regex101.com/r/lyKDnR/1
     */
    private const string WHITESPACE_BETWEEN_TAGS_REGEX = '/>\s+</';

    /**
     * @param string $content The content from which line feeds and tabs will be removed
     *
     * @return string The cleaned content with line feeds and tabs removed
     */
    public static function removeLineFeedsAndTabs(string $content): string
    {
        return str_replace(["\r", "\n", "\t"], '', $content);
    }

    /**
     * @param string $content The XML content with potential inter-tag whitespace
     *
     * @return string The XML content with collapsed inter-tag whitespace
     */
    public static function removeWhitespaceBetweenTags(string $content): string
    {
        return (string) preg_replace(self::WHITESPACE_BETWEEN_TAGS_REGEX, '><', $content);
    }
}
