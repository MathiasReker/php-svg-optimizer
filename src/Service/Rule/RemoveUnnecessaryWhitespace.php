<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\XmlContentTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnnecessaryWhitespace implements SvgOptimizerRuleInterface
{
    use XmlContentTrait;

    private const string ATTRIBUTE_REGEX = '/(\S+)\s*=\s*"([^"]*)"/';

    private const string WHITESPACE_REGEX = '/\s+/';

    private const string CSS_STRING_REGEX = '/("(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\')/';

    private const string CSS_SEPARATOR_WHITESPACE_REGEX = '/\s*([;:,])\s*/';

    /**
     * @param string $style the style attribute value
     */
    private static function minifyStyle(string $style): string
    {
        $parts = preg_split(self::CSS_STRING_REGEX, $style, -1, \PREG_SPLIT_DELIM_CAPTURE);

        if (false === $parts) {
            return $style;
        }

        foreach ($parts as $index => $part) {
            if (0 === $index % 2) {
                $part = preg_replace(self::WHITESPACE_REGEX, ' ', $part) ?? $part;
                $parts[$index] = preg_replace(self::CSS_SEPARATOR_WHITESPACE_REGEX, '$1', $part) ?? $part;
            }
        }

        return rtrim(trim(implode('', $parts)), ';');
    }

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     *
     * @throws XmlProcessingException if the XML content cannot be processed
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->processXmlContent(
            $domDocument,
            static fn (string $content): string => preg_replace_callback(
                self::ATTRIBUTE_REGEX,
                static function (array $matches): string {
                    $name = $matches[1];
                    $value = $matches[2];

                    if (SvgAttribute::Style->value === $name) {
                        $value = self::minifyStyle($value);
                    } else {
                        $value = preg_replace(self::WHITESPACE_REGEX, ' ', trim($value));
                    }

                    return \sprintf('%s="%s"', $name, $value);
                },
                $content
            ) ?? $content
        );
    }
}
