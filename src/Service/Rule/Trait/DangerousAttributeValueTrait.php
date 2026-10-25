<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Support\SvgDefaults;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait DangerousAttributeValueTrait
{
    private const string NON_ASCII_REGEX = '/[^\x00-\x7F]/';

    private const array HOMOGLYPHS = [
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z',
        'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd', 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j',
        'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n', 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't',
        'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z',
        '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
    ];

    private const string DANGEROUS_PROTOCOLS_REGEX = '~^\s*(?:(?:javascript|livescript|mocha|vbscript|vbs|file|http|https|mailto|ftp|tel|sms|callto|cis|xmpp|blob|jar|mhtml|ms-its|res|view-source|wyciwyg):|data:(?!image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,)|[/\\\]{2})~ix';

    private const string URL_FUNCTION_REGEX = '/url\(\s*([\'"]?)([^\'")]*)/i';

    private const string CSS_ESCAPE_REGEX = '/\\\(?:([0-9a-f]{1,6})|([^\r\n\f]))/i';

    private const string INVISIBLE_CHARS_REGEX = '/[\p{Cf}\x{2028}\x{2029}]+/u';

    private const string URL_IGNORED_CHARS_REGEX = '/[\x00-\x20\x7F\p{Cf}\x{2028}\x{2029}]+/u';

    private const string CONTROL_CHARS_REGEX = '/[\x00-\x1F\x7F]+/u';

    private const string WHITESPACE_REGEX = '/\s+/u';

    private const string C_STYLE_COMMENT_REGEX = '/\/\*.*?\*\//s';

    /**
     * @param list<string> $urls the raw URLs to check
     *
     * @return bool true if any URL uses a dangerous protocol
     */
    private function containsDangerousUrl(array $urls): bool
    {
        foreach ($urls as $url) {
            if ($this->matchesPattern($url, self::DANGEROUS_PROTOCOLS_REGEX) || $this->matchesPattern($this->normalizeValue($url), self::DANGEROUS_PROTOCOLS_REGEX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value the value that may contain url() functions
     *
     * @return bool true if the URL of any url() function uses a dangerous protocol
     */
    private function containsDangerousUrlFunction(string $value): bool
    {
        preg_match_all(self::URL_FUNCTION_REGEX, $value, $matches);

        return $this->containsDangerousUrl($matches[2]);
    }

    /**
     * @param string $value the input string
     *
     * @return string the normalized string
     */
    private function normalizeValue(string $value): string
    {
        if (str_contains($value, '&')) {
            $value = html_entity_decode($value, \ENT_QUOTES | \ENT_XML1, SvgDefaults::XML_ENCODING);
        }

        $value = preg_replace(self::C_STYLE_COMMENT_REGEX, '', $value) ?? $value;

        $value = preg_replace_callback(
            self::CSS_ESCAPE_REGEX,
            static function (array $match): string {
                if ('' === $match[1]) {
                    return $match[2];
                }

                $code = \intval($match[1], 16);

                return $code > 0x10_FF_FF || ($code >= 0xD8_00 && $code <= 0xDF_FF)
                    ? ''
                    : mb_chr($code, SvgDefaults::XML_ENCODING);
            },
            $value
        ) ?? $value;

        $value = preg_replace(self::CONTROL_CHARS_REGEX, '', $value) ?? $value;

        $value = preg_replace(self::INVISIBLE_CHARS_REGEX, '', $value) ?? $value;

        $value = $this->transliterateHomoglyphs($value);

        $value = preg_replace(self::WHITESPACE_REGEX, '', $value) ?? $value;

        return mb_strtolower($value, SvgDefaults::XML_ENCODING);
    }

    /**
     * @param string $value the input string
     *
     * @return string the transliterated string
     */
    private function transliterateHomoglyphs(string $value): string
    {
        return 1 === preg_match(self::NON_ASCII_REGEX, $value) ? strtr($value, self::HOMOGLYPHS) : $value;
    }

    /**
     * @param string $value   the string to check
     * @param string $pattern the regex pattern
     *
     * @return bool true if the value matches the pattern
     */
    private function matchesPattern(string $value, string $pattern): bool
    {
        return 1 === preg_match($pattern, $value);
    }

    /**
     * @param string $url the raw URL
     *
     * @return bool true if the URL uses a dangerous protocol or is a network-path reference, as decoded by CSS or by
     *              the URL parser
     */
    private function isDangerousUrl(string $url): bool
    {
        return $this->matchesPattern($this->normalizeValue($url), self::DANGEROUS_PROTOCOLS_REGEX)
            || $this->matchesPattern(preg_replace(self::URL_IGNORED_CHARS_REGEX, '', $url) ?? $url, self::DANGEROUS_PROTOCOLS_REGEX);
    }
}
