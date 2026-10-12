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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;

/**
 * Writes colors in their shortest exact form: `rgb()` becomes hex, hex is lowercased and shortened from `#aabbcc` to
 * `#abc` (and `#aabbccdd` to `#abcd`), and a color is written as a named color or as hex, whichever is shorter, such
 * as `#ff0000` to `red` and `white` to `#fff`. On a tie, a named color is kept and any other color is written as hex.
 * Keywords without a fixed value, such as `currentColor`, `transparent` or `none`, are kept.
 *
 * @no-named-arguments
 */
final readonly class ConvertColorsToHex implements SvgOptimizerRuleInterface
{
    use AsciiCaseTrait;

    private const string RGB_REGEX = '/^rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/';

    private const string HEX_REGEX = '/^#(?:[a-fA-F0-9]{3,6}|[a-fA-F0-9]{8})$/';

    /**
     * Matches a 6- or 8-digit hex color whose every channel repeats its digit.
     */
    private const string SHORTENABLE_HEX_REGEX = '/^#(?:([0-9a-f])\1){3,4}$/';

    private const string REPEATED_DIGIT_REGEX = '/([0-9a-f])\1/';

    /**
     * The CSS named colors with their exact values.
     */
    private const array NAMED_COLORS = [
        'aliceblue' => '#f0f8ff', 'antiquewhite' => '#faebd7', 'aqua' => '#00ffff', 'aquamarine' => '#7fffd4',
        'azure' => '#f0ffff', 'beige' => '#f5f5dc', 'bisque' => '#ffe4c4', 'black' => '#000000',
        'blanchedalmond' => '#ffebcd', 'blue' => '#0000ff', 'blueviolet' => '#8a2be2', 'brown' => '#a52a2a',
        'burlywood' => '#deb887', 'cadetblue' => '#5f9ea0', 'chartreuse' => '#7fff00', 'chocolate' => '#d2691e',
        'coral' => '#ff7f50', 'cornflowerblue' => '#6495ed', 'cornsilk' => '#fff8dc', 'crimson' => '#dc143c',
        'cyan' => '#00ffff', 'darkblue' => '#00008b', 'darkcyan' => '#008b8b', 'darkgoldenrod' => '#b8860b',
        'darkgray' => '#a9a9a9', 'darkgreen' => '#006400', 'darkgrey' => '#a9a9a9', 'darkkhaki' => '#bdb76b',
        'darkmagenta' => '#8b008b', 'darkolivegreen' => '#556b2f', 'darkorange' => '#ff8c00',
        'darkorchid' => '#9932cc', 'darkred' => '#8b0000', 'darksalmon' => '#e9967a', 'darkseagreen' => '#8fbc8f',
        'darkslateblue' => '#483d8b', 'darkslategray' => '#2f4f4f', 'darkslategrey' => '#2f4f4f',
        'darkturquoise' => '#00ced1', 'darkviolet' => '#9400d3', 'deeppink' => '#ff1493',
        'deepskyblue' => '#00bfff', 'dimgray' => '#696969', 'dimgrey' => '#696969', 'dodgerblue' => '#1e90ff',
        'firebrick' => '#b22222', 'floralwhite' => '#fffaf0', 'forestgreen' => '#228b22', 'fuchsia' => '#ff00ff',
        'gainsboro' => '#dcdcdc', 'ghostwhite' => '#f8f8ff', 'gold' => '#ffd700', 'goldenrod' => '#daa520',
        'gray' => '#808080', 'green' => '#008000', 'greenyellow' => '#adff2f', 'grey' => '#808080',
        'honeydew' => '#f0fff0', 'hotpink' => '#ff69b4', 'indianred' => '#cd5c5c', 'indigo' => '#4b0082',
        'ivory' => '#fffff0', 'khaki' => '#f0e68c', 'lavender' => '#e6e6fa', 'lavenderblush' => '#fff0f5',
        'lawngreen' => '#7cfc00', 'lemonchiffon' => '#fffacd', 'lightblue' => '#add8e6',
        'lightcoral' => '#f08080', 'lightcyan' => '#e0ffff', 'lightgoldenrodyellow' => '#fafad2',
        'lightgray' => '#d3d3d3', 'lightgreen' => '#90ee90', 'lightgrey' => '#d3d3d3', 'lightpink' => '#ffb6c1',
        'lightsalmon' => '#ffa07a', 'lightseagreen' => '#20b2aa', 'lightskyblue' => '#87cefa',
        'lightslategray' => '#778899', 'lightslategrey' => '#778899', 'lightsteelblue' => '#b0c4de',
        'lightyellow' => '#ffffe0', 'lime' => '#00ff00', 'limegreen' => '#32cd32', 'linen' => '#faf0e6',
        'magenta' => '#ff00ff', 'maroon' => '#800000', 'mediumaquamarine' => '#66cdaa',
        'mediumblue' => '#0000cd', 'mediumorchid' => '#ba55d3', 'mediumpurple' => '#9370db',
        'mediumseagreen' => '#3cb371', 'mediumslateblue' => '#7b68ee', 'mediumspringgreen' => '#00fa9a',
        'mediumturquoise' => '#48d1cc', 'mediumvioletred' => '#c71585', 'midnightblue' => '#191970',
        'mintcream' => '#f5fffa', 'mistyrose' => '#ffe4e1', 'moccasin' => '#ffe4b5', 'navajowhite' => '#ffdead',
        'navy' => '#000080', 'oldlace' => '#fdf5e6', 'olive' => '#808000', 'olivedrab' => '#6b8e23',
        'orange' => '#ffa500', 'orangered' => '#ff4500', 'orchid' => '#da70d6', 'palegoldenrod' => '#eee8aa',
        'palegreen' => '#98fb98', 'paleturquoise' => '#afeeee', 'palevioletred' => '#db7093',
        'papayawhip' => '#ffefd5', 'peachpuff' => '#ffdab9', 'peru' => '#cd853f', 'pink' => '#ffc0cb',
        'plum' => '#dda0dd', 'powderblue' => '#b0e0e6', 'purple' => '#800080', 'rebeccapurple' => '#663399',
        'red' => '#ff0000', 'rosybrown' => '#bc8f8f', 'royalblue' => '#4169e1', 'saddlebrown' => '#8b4513',
        'salmon' => '#fa8072', 'sandybrown' => '#f4a460', 'seagreen' => '#2e8b57', 'seashell' => '#fff5ee',
        'sienna' => '#a0522d', 'silver' => '#c0c0c0', 'skyblue' => '#87ceeb', 'slateblue' => '#6a5acd',
        'slategray' => '#708090', 'slategrey' => '#708090', 'snow' => '#fffafa', 'springgreen' => '#00ff7f',
        'steelblue' => '#4682b4', 'tan' => '#d2b48c', 'teal' => '#008080', 'thistle' => '#d8bfd8',
        'tomato' => '#ff6347', 'turquoise' => '#40e0d0', 'violet' => '#ee82ee', 'wheat' => '#f5deb3',
        'white' => '#ffffff', 'whitesmoke' => '#f5f5f5', 'yellow' => '#ffff00', 'yellowgreen' => '#9acd32',
    ];

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
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $colorAttributes = SvgAttribute::colors();
        $stylePattern = $this->buildStylePattern($colorAttributes);
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $element) {
            if ($element->hasAttribute(SvgAttribute::Style->value)) {
                $style = $element->getAttribute(SvgAttribute::Style->value);
                $style = $this->processStyle($style, $stylePattern);
                $element->setAttribute(SvgAttribute::Style->value, $style);
            }

            foreach ($colorAttributes as $colorAttribute) {
                if (!$element->hasAttribute($colorAttribute)) {
                    continue;
                }

                $value = trim($element->getAttribute($colorAttribute));
                $element->setAttribute($colorAttribute, $this->convertColorValue($value));
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
        return '/\b(' . implode('|', $colorAttributes) . ')\s*:\s*([^;]+)/i';
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
            return mb_strtolower($value);
        }

        if (1 !== preg_match(self::RGB_REGEX, $value, $matches)) {
            return '';
        }

        [$r, $g, $b] = array_map(intval(...), \array_slice($matches, 1));

        if ($r > 255 || $g > 255 || $b > 255) {
            return '';
        }

        return \sprintf('#%02x%02x%02x', $r, $g, $b);
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
