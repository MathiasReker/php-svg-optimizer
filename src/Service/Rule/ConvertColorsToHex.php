<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NamedColorTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConvertColorsToHex implements PassInterface
{
    use AsciiCaseTrait;
    use ElementTrait;
    use NamedColorTrait;

    private const string RGB_CHANNEL = '([01]?\d?\d|2[0-4]\d|25[0-5])';

    private const string RGB_REGEX = '/^rgb\s*\(\s*' . self::RGB_CHANNEL . '\s*,\s*' . self::RGB_CHANNEL . '\s*,\s*' . self::RGB_CHANNEL . '\s*\)$/';

    private const string HEX_REGEX = '/^#(?:[a-fA-F0-9]{3,6}|[a-fA-F0-9]{8})$/';

    private const string SHORTENABLE_HEX_REGEX = '/^#(?:([0-9a-f])\1){3,4}$/';

    private const string REPEATED_DIGIT_REGEX = '/([0-9a-f])\1/';

    /** @var array<string, string> the named colors shorter than their shortest hex form, keyed by that hex form */
    private array $shorterNames;

    public function __construct()
    {
        $shorterNames = [];

        foreach (self::NAMED_COLORS as $name => $hex) {
            $hex = $this->shortenHex($hex);

            if (mb_strlen($name) < mb_strlen($shorterNames[$hex] ?? $hex)) {
                $shorterNames[$hex] = $name;
            }
        }

        $this->shorterNames = $shorterNames;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->mayMatchAttributeValues($domDocument)) {
            return;
        }

        $colorAttributes = SvgAttribute::colors();
        $stylePattern = $this->buildStylePattern($colorAttributes);
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $element) {
            if ($element->hasAttribute(SvgAttribute::Style->value)) {
                $style = $element->getAttribute(SvgAttribute::Style->value);
                $style = $this->processStyle($style, $stylePattern);
                $element->setAttribute(SvgAttribute::Style->value, $style);
            }

            if ($this->isSvgElement($element)) {
                $this->convertColorAttributes($element, $colorAttributes);
            }
        }
    }

    /**
     * @param \DOMElement  $domElement      the SVG element holding the attributes
     * @param list<string> $colorAttributes the color attributes to convert
     */
    private function convertColorAttributes(\DOMElement $domElement, array $colorAttributes): void
    {
        foreach ($colorAttributes as $colorAttribute) {
            if ($domElement->hasAttribute($colorAttribute)) {
                $value = trim($domElement->getAttribute($colorAttribute));
                $domElement->setAttribute($colorAttribute, $this->convertColorValue($value));
            }
        }
    }

    /**
     * @param list<string> $colorAttributes a list of color-related CSS properties
     *
     * @return string the regex pattern matching those properties in a style declaration
     */
    private function buildStylePattern(array $colorAttributes): string
    {
        return '/(?<![\w-])(' . implode('|', $colorAttributes) . ')\s*:\s*([^;]+)/i';
    }

    /**
     * @param string $style   the inline style string
     * @param string $pattern the regex pattern matching color-related CSS properties
     *
     * @return string the processed style string with converted colors
     */
    private function processStyle(string $style, string $pattern): string
    {
        return preg_replace_callback(
            $pattern,
            fn (array $m): string => $m[1] . ':' . $this->convertColorValue(trim($m[2])),
            $style
        ) ?? $style;
    }

    /**
     * @param string $value the color value to convert
     *
     * @return string the shortest equivalent color, or the original value
     */
    private function convertColorValue(string $value): string
    {
        $name = $this->asciiLowercase($value);
        $hex = self::NAMED_COLORS[$name] ?? $this->toHex($value);

        if ('' === $hex) {
            return $value;
        }

        $hex = $this->shortenHex($hex);
        $shortest = $this->shorterNames[$hex] ?? $hex;
        $isNamedColor = \array_key_exists($name, self::NAMED_COLORS);

        return $isNamedColor && mb_strlen($shortest) >= mb_strlen($value) ? $value : $shortest;
    }

    /**
     * @param string $value the color value
     *
     * @return string the lowercase hex color, or an empty string if the value is not an `rgb()` or hex color
     */
    private function toHex(string $value): string
    {
        if (1 === preg_match(self::HEX_REGEX, $value)) {
            return $this->asciiLowercase($value);
        }

        if (1 !== preg_match(self::RGB_REGEX, $value, $matches)) {
            return '';
        }

        return vsprintf('#%02x%02x%02x', \array_slice($matches, 1));
    }

    /**
     * @param string $hex a lowercase hex color
     *
     * @return string the 3- or 4-digit form if every channel repeats its digit, otherwise the input
     */
    private function shortenHex(string $hex): string
    {
        if (1 !== preg_match(self::SHORTENABLE_HEX_REGEX, $hex)) {
            return $hex;
        }

        return preg_replace(self::REPEATED_DIGIT_REGEX, '$1', $hex) ?? $hex;
    }
}
