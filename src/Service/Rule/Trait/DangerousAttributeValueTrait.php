<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Support\SvgDefaults;

/**
 * Provides attribute-level security analysis: whether an attribute's name or
 * value is dangerous, and removing dangerous attributes from a document.
 *
 * This is kept separate from the element-removal logic in
 * `RemoveUnsafeElements` so that each concern stays independently readable.
 *
 * @no-named-arguments
 */
trait DangerousAttributeValueTrait
{
    /**
     * Removes dangerous attributes from all elements in the document.
     *
     * This method iterates through every attribute of every element and removes
     * it if it is determined to be dangerous by `isDangerousAttribute`.
     *
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeDangerousAttributes(\DOMDocument $domDocument): void
    {
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $domElement) {
            if (!$domElement->hasAttributes()) {
                continue;
            }

            /** @var \DOMAttr $attribute */
            foreach (iterator_to_array($domElement->attributes, false) as $attribute) {
                if ($this->isDangerousAttribute($attribute)) {
                    $domElement->removeAttributeNode($attribute);
                }
            }
        }
    }

    /**
     * Determines if an attribute is dangerous.
     *
     * An attribute is considered dangerous if its name starts with "on" (e.g.,
     * `onclick`), if it contains a dangerous protocol (e.g., `javascript:`),
     * or if it's a `style` attribute with unsafe content.
     *
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the attribute is dangerous
     */
    private function isDangerousAttribute(\DOMAttr $domAttr): bool
    {
        $name = mb_strtolower($domAttr->name);

        if ($this->isDangerousAttributeName($name)) {
            return true;
        }

        $value = $this->normalizeValue($domAttr->value);

        return $this->isDangerousAttributeValue($name, $value);
    }

    /**
     * Checks if an attribute name is dangerous.
     *
     * @param string $name the attribute name
     *
     * @return bool true if the name is dangerous
     */
    private function isDangerousAttributeName(string $name): bool
    {
        foreach (self::DANGEROUS_ATTR_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalizes a string value for security analysis.
     *
     * This method performs several steps to canonicalize the input string,
     * making it harder to bypass security checks with obfuscation techniques.
     * This includes decoding HTML entities, decoding CSS escapes, removing
     * control characters, and transliterating homoglyphs.
     *
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
            self::CSS_HEX_ESCAPE_REGEX,
            static function (array $match): string {
                $code = (int) hexdec($match[1]);

                return ($code > 0 && $code <= 0x10_FF_FF) ? mb_chr($code, SvgDefaults::XML_ENCODING) : '';
            },
            $value
        ) ?? $value;

        $value = preg_replace(self::CONTROL_CHARS_REGEX, '', $value) ?? $value;

        $value = $this->transliterateHomoglyphs($value);

        $value = preg_replace(self::WHITESPACE_REGEX, '', $value) ?? $value;

        return mb_strtolower(trim($value), SvgDefaults::XML_ENCODING);
    }

    /**
     * Replaces characters that look like letters/numbers with their ASCII equivalents.
     *
     * This is used to counter obfuscation attempts where an attacker might use
     * full-width characters or other homoglyphs to disguise malicious code.
     *
     * @param string $value the input string
     *
     * @return string the transliterated string
     */
    private function transliterateHomoglyphs(string $value): string
    {
        $map = [
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z',
            'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd', 'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j',
            'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n', 'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't',
            'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z',
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        ];

        return strtr($value, $map);
    }

    /**
     * Checks if a string matches a given regular expression pattern.
     *
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
     * Checks if an attribute value is dangerous.
     *
     * @param string $name  the attribute name
     * @param string $value the normalized attribute value
     *
     * @return bool true if the value is dangerous
     */
    private function isDangerousAttributeValue(string $name, string $value): bool
    {
        if (\in_array($name, SvgAttribute::dangerousExact(), true) && $this->matchesPattern($value, self::DANGEROUS_PROTOCOLS_REGEX)) {
            return true;
        }

        if (SvgAttribute::Values->value === $name && $this->isSmilValuesDangerous($value)) {
            return true;
        }

        if (\in_array($name, SvgAttribute::dangerous(), true) && $this->isUrlAttributeValueDangerous($value)) {
            return true;
        }

        if (SvgAttribute::Style->value === $name) {
            return $this->isStyleAttributeDangerous($value);
        }

        if ($name === SvgAttribute::Srcset->value) {
            return $this->isSrcsetDangerous($value);
        }

        return SvgAttribute::Src->value === $name && $this->matchesPattern($value, self::DANGEROUS_PROTOCOLS_REGEX);
    }

    /**
     * Checks if a SMIL `values` attribute contains any dangerous protocols.
     *
     * The `values` attribute can contain a semicolon-separated list of values,
     * so each part must be checked.
     *
     * @param string $value the attribute value
     *
     * @return bool true if a dangerous protocol is found
     */
    private function isSmilValuesDangerous(string $value): bool
    {
        $value = preg_replace(self::SMIL_VALUES_SAFE_DATA_URI_REGEX, '', $value) ?? $value;

        $parts = explode(';', $value);

        foreach ($parts as $part) {
            if ($this->matchesPattern(trim($part), self::DANGEROUS_PROTOCOLS_REGEX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if an attribute that can contain a URL has a dangerous value.
     *
     * This applies to attributes like `href` and `xlink:href`.
     *
     * @param string $value the attribute value
     *
     * @return bool true if the URL is dangerous
     */
    private function isUrlAttributeValueDangerous(string $value): bool
    {
        if (1 === preg_match(self::URL_FUNCTION_REGEX, $value, $matches)) {
            $urlInside = trim($matches[2]);

            return 1 === preg_match(self::DANGEROUS_PROTOCOLS_REGEX, $urlInside);
        }

        return 1 === preg_match(self::DANGEROUS_PROTOCOLS_REGEX, trim($value));
    }

    /**
     * Checks if a style attribute value is dangerous.
     *
     * @param string $value the style attribute value
     *
     * @return bool true if the value is dangerous, false otherwise
     */
    private function isStyleAttributeDangerous(string $value): bool
    {
        if ($this->matchesPattern($value, self::DANGEROUS_STYLE_REGEX)) {
            return true;
        }

        if (1 === preg_match_all(self::URL_FUNCTION_REGEX, $value, $matches)) {
            foreach ($matches[2] as $url) {
                $normalized = $this->normalizeValue($url);

                if ($this->matchesPattern($normalized, self::DANGEROUS_PROTOCOLS_REGEX)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if a srcset attribute value is dangerous.
     *
     * @param string $value the srcset attribute value
     *
     * @return bool true if the value is dangerous, false otherwise
     */
    private function isSrcsetDangerous(string $value): bool
    {
        $candidates = explode(',', $value);

        foreach ($candidates as $candidate) {
            $parts = preg_split(self::SRCSET_SPLIT_REGEX, trim($candidate));
            $url = $parts[0] ?? '';

            $normalized = $this->normalizeValue($url);

            if ($this->matchesPattern($normalized, self::DANGEROUS_PROTOCOLS_REGEX)) {
                return true;
            }
        }

        return false;
    }
}
