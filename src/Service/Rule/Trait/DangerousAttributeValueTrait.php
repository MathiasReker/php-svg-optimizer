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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Support\SvgDefaults;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait DangerousAttributeValueTrait
{
    private const string NON_ASCII_REGEX = '/[^\x00-\x7F]/';

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeDangerousAttributes(\DOMDocument $domDocument): void
    {
        $arrayObject = new \ArrayObject();

        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            $this->removeDangerousAttributesOf($domElement, $arrayObject);
        }
    }

    /**
     * @param \DOMElement                $domElement  the element to clean
     * @param \ArrayObject<string, bool> $arrayObject the verdicts for attributes checked before, by data URI permission, name and value
     */
    private function removeDangerousAttributesOf(\DOMElement $domElement, \ArrayObject $arrayObject): void
    {
        $allowsDataUri = $this->isTagIn($domElement, SvgTag::dataUri());

        foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
            $key = serialize([$allowsDataUri, $domAttr->namespaceURI, $domAttr->nodeName, $domAttr->value]);

            if (!$arrayObject->offsetExists($key)) {
                $arrayObject[$key] = $this->isDangerousAttribute($domAttr, $allowsDataUri);
            }

            if (true === $arrayObject[$key]) {
                $domElement->removeAttributeNode($domAttr);
            }
        }
    }

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
     * @param \DOMAttr $domAttr       the attribute to check
     * @param bool     $allowsDataUri whether the owning element may reference a data: image
     *
     * @return bool true if the attribute is dangerous
     */
    private function isDangerousAttribute(\DOMAttr $domAttr, bool $allowsDataUri): bool
    {
        $name = mb_strtolower($domAttr->name);

        if (SvgNamespace::XmlEvents->value === $domAttr->namespaceURI
            || $this->isDangerousAttributeName($name)
            || $this->isNonCanonicalAttribute($domAttr)
        ) {
            return true;
        }

        if ($this->isUrlAttribute($name) && $this->isDangerousUrl($domAttr->value)) {
            return true;
        }

        $value = $this->normalizeValue($domAttr->value);

        if (!$allowsDataUri
            && \in_array($name, SvgAttribute::dangerousExact(), true)
            && $this->matchesPattern($value, self::DATA_URI_REGEX)
        ) {
            return true;
        }

        return $this->isDangerousAttributeValue($name, $value);
    }

    /**
     * @param string $name the attribute name
     *
     * @return bool true if the name is dangerous
     */
    private function isDangerousAttributeName(string $name): bool
    {
        if (\in_array($name, self::CLOBBERING_ATTRIBUTES, true)) {
            return true;
        }

        foreach (self::DANGEROUS_ATTR_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
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
        if (1 !== preg_match(self::NON_ASCII_REGEX, $value)) {
            return $value;
        }

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
     * @param string $name  the attribute name
     * @param string $value the normalized attribute value
     *
     * @return bool true if the value is dangerous
     */
    private function isDangerousAttributeValue(string $name, string $value): bool
    {
        return (SvgAttribute::Values->value === $name && $this->isSmilValuesDangerous($value))
            || (\in_array($name, SvgAttribute::dangerous(), true) && $this->isUrlAttributeValueDangerous($value))
            || match ($name) {
                SvgAttribute::Style->value => $this->isStyleAttributeDangerous($value),
                SvgAttribute::Srcset->value => $this->isSrcsetDangerous($value),
                default => false,
            };
    }

    /**
     * @param string $name the attribute name
     *
     * @return bool true if the attribute holds a single URL
     */
    private function isUrlAttribute(string $name): bool
    {
        return \in_array($name, SvgAttribute::dangerousExact(), true)
            || \in_array($name, self::URI_ATTRIBUTES, true)
            || SvgAttribute::Src->value === $name;
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

    /**
     * @param string $value the attribute value
     *
     * @return bool true if a dangerous protocol is found
     */
    private function isSmilValuesDangerous(string $value): bool
    {
        $value = preg_replace(self::SMIL_VALUES_SAFE_DATA_URI_REGEX, '', $value) ?? $value;

        $parts = explode(';', $value);

        foreach ($parts as $part) {
            if ($this->matchesPattern($part, self::DANGEROUS_PROTOCOLS_REGEX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $value the attribute value
     *
     * @return bool true if the URL is dangerous
     */
    private function isUrlAttributeValueDangerous(string $value): bool
    {
        if ($this->matchesPattern($value, self::DANGEROUS_PROTOCOLS_REGEX)) {
            return true;
        }

        preg_match_all(self::URL_FUNCTION_REGEX, $value, $matches);

        return $this->containsDangerousUrl($matches[2]);
    }

    /**
     * @param string $value the style attribute value
     *
     * @return bool true if the value is dangerous, false otherwise
     */
    private function isStyleAttributeDangerous(string $value): bool
    {
        if ($this->matchesPattern($value, self::DANGEROUS_STYLE_REGEX)) {
            return true;
        }

        preg_match_all(self::URL_FUNCTION_REGEX, $value, $urls);

        if ($this->containsDangerousUrl($urls[2])) {
            return true;
        }

        preg_match_all(self::CSS_IMAGE_SET_REGEX, $value, $imageSets);
        preg_match_all(self::CSS_QUOTED_STRING_REGEX, implode(' ', $imageSets[0]), $imageSetUrls);

        return $this->containsDangerousUrl($imageSetUrls[2]);
    }

    /**
     * @param string $value the srcset attribute value
     *
     * @return bool true if the value is dangerous, false otherwise
     */
    private function isSrcsetDangerous(string $value): bool
    {
        $candidates = explode(',', $value);

        foreach ($candidates as $candidate) {
            if ($this->matchesPattern($this->normalizeValue($candidate), self::DANGEROUS_PROTOCOLS_REGEX)) {
                return true;
            }
        }

        return false;
    }
}
