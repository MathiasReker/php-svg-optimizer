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

class RemoveUnnecessaryWhitespace implements SvgOptimizerRuleInterface
{
    /**
     * Regex for attribute values.
     *
     * @see https://regex101.com/r/6oZWnx/1
     *
     * @var string
     */
    private const ATTRIBUTE_VALUE_REGEX = '/(\S+)=\s*"([^"]*)"/';

    /**
     * Regex for style attribute values.
     *
     * @see https://regex101.com/r/JFLCQm/1
     *
     * @var string
     */
    private const STYLE_ATTRIBUTE_REGEX = '/style\s*=\s*"([^"]*)"/';

    /**
     * Regex for whitespace.
     *
     * @see https://regex101.com/r/pxX489/1
     *
     * @var string
     */
    private const WHITESPACE_REGEX = '/\s+/';

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
            self::ATTRIBUTE_VALUE_REGEX,
            static fn (array $matches): string => \sprintf('%s="%s"', $matches[1], preg_replace(self::WHITESPACE_REGEX, ' ', trim($matches[2]))),
            $svgContent
        );

        // Remove all whitespace inside style attribute values
        $svgContent = (string) preg_replace_callback(
            self::STYLE_ATTRIBUTE_REGEX,
            static fn (array $matches): string => \sprintf('style="%s"', str_replace(' ', '', $matches[1])),
            $svgContent
        );

        if (false === $domDocument->loadXML($svgContent)) {
            throw new XmlProcessingException('Failed to load optimized XML content.');
        }
    }
}
