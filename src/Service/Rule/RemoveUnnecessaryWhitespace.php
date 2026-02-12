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
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;

/**
 * @no-named-arguments
 */
final readonly class RemoveUnnecessaryWhitespace extends AbstractXmlProcessor implements SvgOptimizerRuleInterface
{
    /**
     * This regex matches attribute-value pairs, capturing the attribute name and its value.
     *
     * @see https://regex101.com/r/3p3eY3/1
     */
    private const string ATTRIBUTE_REGEX = '/(\S+)\s*=\s*"([^"]*)"/';

    /**
     * This regex matches one or more whitespace characters.
     *
     * @see https://regex101.com/r/OuyK7V/1
     */
    private const string WHITESPACE_REGEX = '/\s+/';

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
     * Removes unnecessary whitespace from attribute values.
     *
     * This method processes the raw SVG content to normalize whitespace within
     * attribute values. For `style` attributes, all whitespace is removed. For
     * other attributes, consecutive whitespace characters are collapsed into a
     * single space.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     *
     * @throws XmlProcessingException if the XML content cannot be processed
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->process(
            $domDocument,
            static fn (string $content): string => preg_replace_callback(
                self::ATTRIBUTE_REGEX,
                static function (array $matches): string {
                    $name = $matches[1];
                    $value = $matches[2];

                    if (SvgAttribute::Style->value === $name) {
                        $value = rtrim(str_replace(' ', '', $value), ';');
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
