<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Security;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait DangerousUrlTrait
{
    private const string DANGEROUS_URL_ENCODING = 'UTF-8';

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

    private const string DANGEROUS_URL_REGEX = '~^\s*(?:(?:javascript|livescript|mocha|vbscript|vbs|file|http|https|mailto|ftp|tel|sms|callto|cis|xmpp|blob|jar|mhtml|ms-its|res|view-source|wyciwyg):|data:(?!image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,)|[/\\\]{2})~ix';

    private const string URL_FUNCTION_REGEX = '/url\(\s*([\'"]?)([^\'")]*)/i';

    private const string CSS_ESCAPE_REGEX = '/\\\(?:([0-9a-f]{1,6})|([^\r\n\f]))/i';

    private const string INVISIBLE_CHARS_REGEX = '/[\p{Cf}\x{2028}\x{2029}]+/u';

    private const string URL_IGNORED_CHARS_REGEX = '/[\x00-\x20\x7F\p{Cf}\x{2028}\x{2029}]+/u';

    private const string CONTROL_CHARS_REGEX = '/[\x00-\x1F\x7F]+/u';

    private const string WHITESPACE_REGEX = '/\s+/u';

    private const string C_STYLE_COMMENT_REGEX = '/\/\*(?:.*?\*\/|(*COMMIT)(*FAIL))/s';

    private const string UNDECODABLE_URL = 'javascript:';

    /**
     * @param list<string> $urls the raw URLs to check
     *
     * @return bool true if any URL uses a dangerous protocol
     */
    private function containsDangerousUrl(array $urls): bool
    {
        foreach ($urls as $url) {
            if (0 !== preg_match(self::DANGEROUS_URL_REGEX, $url) || 0 !== preg_match(self::DANGEROUS_URL_REGEX, $this->decodeUrlForCheck($url))) {
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
        return false === preg_match_all(self::URL_FUNCTION_REGEX, $value, $matches) || $this->containsDangerousUrl($matches[2]);
    }

    /**
     * @param string $value the input string
     *
     * @return string the normalized string, or a dangerous URL if the string cannot be normalized
     */
    private function decodeUrlForCheck(string $value): string
    {
        if (str_contains($value, '&')) {
            $value = html_entity_decode($value, \ENT_QUOTES | \ENT_XML1, self::DANGEROUS_URL_ENCODING);
        }

        $value = preg_replace(self::C_STYLE_COMMENT_REGEX, '', $value);
        $value = null === $value ? null : preg_replace_callback(self::CSS_ESCAPE_REGEX, $this->decodeCssEscape(...), $value);
        $value = null === $value ? null : preg_replace([self::CONTROL_CHARS_REGEX, self::INVISIBLE_CHARS_REGEX], '', $value);
        $value = null === $value ? null : preg_replace(self::WHITESPACE_REGEX, '', $this->transliterateHomoglyphs($value));

        return null === $value ? self::UNDECODABLE_URL : mb_strtolower($value, self::DANGEROUS_URL_ENCODING);
    }

    /**
     * @param array<int|string, string> $match a CSS escape, with its hexadecimal code point or its escaped character
     *
     * @return string the character the escape stands for, or nothing for an invalid code point
     */
    private function decodeCssEscape(array $match): string
    {
        if ('' === $match[1]) {
            return $match[2];
        }

        $code = \intval($match[1], 16);

        return $code > 0x10_FF_FF || ($code >= 0xD8_00 && $code <= 0xDF_FF)
            ? ''
            : mb_chr($code, self::DANGEROUS_URL_ENCODING);
    }

    /**
     * @param string $value the input string
     *
     * @return string the transliterated string
     */
    private function transliterateHomoglyphs(string $value): string
    {
        return 0 !== preg_match(self::NON_ASCII_REGEX, $value) ? strtr($value, self::HOMOGLYPHS) : $value;
    }

    /**
     * @param string $url the raw URL
     *
     * @return bool true if the URL uses a dangerous protocol or is a network-path reference, as decoded by CSS or by
     *              the URL parser
     */
    private function isDangerousUrl(string $url): bool
    {
        return 0 !== preg_match(self::DANGEROUS_URL_REGEX, $this->decodeUrlForCheck($url))
            || 0 !== preg_match(self::DANGEROUS_URL_REGEX, preg_replace(self::URL_IGNORED_CHARS_REGEX, '', $url) ?? self::UNDECODABLE_URL);
    }
}
