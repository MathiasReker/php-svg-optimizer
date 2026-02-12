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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Support\SvgDefaults;

/**
 * @no-named-arguments
 */
final readonly class RemoveUnsafeElements implements SvgOptimizerRuleInterface
{
    /**
     * List of dangerous attribute prefixes and exact names that should be removed.
     *
     * Attributes with these prefixes or exact names are considered unsafe and should not be present in the SVG content.
     */
    private const array DANGEROUS_ATTR_PREFIXES = [
        'on',
    ];

    /**
     * Constant for the XML stylesheet processing instruction.
     */
    private const string XML_STYLESHEET_PI = 'xml-stylesheet';

    /**
     * Regular expressions for detecting unsafe patterns in attribute values.
     *
     * These patterns are used to identify potentially dangerous content in attributes.
     *
     * @see https://regex101.com/r/QHNWJG/1
     */
    private const string URI_PROTOCOL_REGEX = '~^[a-z][a-z0-9+.-]*:~i';

    /**
     * Regular expression for detecting dangerous protocols in URLs.
     *
     * This pattern matches protocols that are considered unsafe, such as javascript, data, file, http, https, and protocol-relative URLs.
     *
     * @seehttps://regex101.com/r/bfijom/1
     */
    private const string DANGEROUS_PROTOCOLS_REGEX =
        '~^\s*(?:(?:javascript|data|file|vbscript|http|https|mailto|ftp|tel|sms):|//)~i';

    /**
     * Regular expressions for detecting unsafe styles in SVG content.
     *
     * These patterns are used to identify potentially dangerous CSS styles that should be removed.
     *
     * @see https://regex101.com/r/vi3UHt/1
     */
    private const string STYLE_DANGEROUS_REGEX = '/@import|expression|url\(\s*javascript:/i';

    /**
     * Regular expression for detecting dangerous content in style nodes.
     *
     * This pattern matches @import statements and certain HTML tags that should not be present in style nodes.
     *
     * @see https://regex101.com/r/hN9M9b/1
     */
    private const string STYLE_NODE_DANGEROUS_REGEX = '/@import\s+url\(|<\s*(script|iframe|object|textarea|embed|link|svg)/i';

    /**
     * Regular expression for detecting URLs in SVG content.
     *
     * This pattern matches the url() function in CSS styles, allowing for both quoted and unquoted URLs.
     *
     * @see https://regex101.com/r/WQHx9p/1
     */
    private const string URL_FUNCTION_REGEX = '/url\(\s*([\'"]?)(.*?)\1\s*\)/i';

    /**
     * Regular expression for detecting URLs that start with a protocol or are relative.
     *
     * This pattern matches URLs that start with a scheme (e.g., http:) or are protocol-relative (e.g., //example.com).
     *
     * @see https://regex101.com/r/Wpra41/1
     */
    private const string URL_PROTOCOL_OR_RELATIVE_REGEX = '~^(?:[a-z][a-z0-9+.-]*:|//)~i';

    /**
     * Regular expression for decoding CSS hexadecimal escapes (e.g. \6a\61\76\61).
     *
     * @see https://regex101.com/r/Wpra41/1
     */
    private const string CSS_HEX_ESCAPE_REGEX = '/\\\([0-9a-f]{2,6})/i';

    /**
     * Regular expression for removing control characters and DEL.
     *
     * @see https://regex101.com/r/hNSaol/1
     */
    private const string CONTROL_CHARS_REGEX = '/[\x00-\x1F\x7F]+/u';

    /**
     * Regular expression for collapsing whitespace.
     *
     * @see https://regex101.com/r/6DKmg3/1
     */
    private const string WHITESPACE_REGEX = '/\s+/u';

    #[\Override]
    public static function isRisky(): bool
    {
        return true;
    }

    /**
     * Sanitizes the SVG document by removing potentially unsafe elements and attributes.
     *
     * This is a critical security rule that removes scripts, event handlers (e.g.,
     * `onclick`), external references, and other constructs that could be
     * exploited for cross-site scripting (XSS) attacks.
     *
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeProcessingInstructions($domDocument);
        $this->removeDangerousElements($domDocument);
        $this->removeDangerousAttributes($domDocument);
        $this->removeStyleWithImport($domDocument);
    }

    /**
     * Removes potentially harmful XML processing instructions.
     *
     * Specifically, this targets `<?xml-stylesheet ... ?>` instructions, which
     * can be used to load external resources.
     *
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeProcessingInstructions(\DOMDocument $domDocument): void
    {
        for ($node = $domDocument->firstChild; $node instanceof \DOMNode; $node = $node->nextSibling) {
            if ($node instanceof \DOMProcessingInstruction
                && str_contains(mb_strtolower($node->nodeName), self::XML_STYLESHEET_PI)
            ) {
                $domDocument->removeChild($node);
            }
        }
    }

    /**
     * Removes dangerous elements from the SVG document.
     *
     * This method removes elements that are always considered unsafe, as well as conditionally dangerous elements
     * based on their attributes. It ensures that the SVG content does not contain any potentially harmful elements.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeDangerousElements(\DOMDocument $domDocument): void
    {
        $this->removeAlwaysDangerousTags($domDocument);
        $this->removeConditionallyDangerousTags($domDocument);
    }

    /**
     * Removes tags that are always considered a security risk (e.g., `<script>`).
     *
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeAlwaysDangerousTags(\DOMDocument $domDocument): void
    {
        foreach (SvgTag::dangerous() as $tag) {
            $this->removeAllElementsByTagName($domDocument, $tag);
        }
    }

    /**
     * Helper function to remove all elements with a given tag name.
     *
     * @param \DOMDocument $domDocument the DOM document to modify
     * @param string       $tagName     the name of the tag to remove
     */
    private function removeAllElementsByTagName(\DOMDocument $domDocument, string $tagName): void
    {
        while (true) {
            $nodes = $domDocument->getElementsByTagName($tagName);
            if (0 === $nodes->length) {
                break;
            }

            $node = $nodes->item(0);
            if ($node instanceof \DOMNode && $node->parentNode instanceof \DOMNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Removes tags that are dangerous only under certain conditions.
     *
     * For example, an `<a>` tag is removed if its `href` attribute points to a
     * potentially malicious destination (e.g., using a `javascript:` URI).
     *
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeConditionallyDangerousTags(\DOMDocument $domDocument): void
    {
        foreach (SvgTag::conditionalDangerous() as $tag) {
            $nodes = $domDocument->getElementsByTagName($tag);

            for ($i = $nodes->length - 1; $i >= 0; --$i) {
                if (!$nodes->item($i) instanceof \DOMElement) {
                    continue;
                }

                $this->removeIfDangerous($nodes->item($i));
            }
        }
    }

    /**
     * Removes a DOM node if it contains dangerous attributes.
     *
     * @param \DOMNode $domNode the node to check
     */
    private function removeIfDangerous(\DOMNode $domNode): void
    {
        if (!$domNode instanceof \DOMElement) {
            return;
        }

        foreach (SvgAttribute::dangerousExact() as $attrCase) {
            $value = $domNode->getAttribute($attrCase);

            if ($this->isExactDangerousAttribute($attrCase, $value)) {
                if ($domNode->parentNode instanceof \DOMNode) {
                    $domNode->parentNode->removeChild($domNode);
                }

                break;
            }
        }
    }

    /**
     * Checks if an attribute's value contains a dangerous protocol.
     *
     * @param string $name  the attribute name
     * @param string $value the attribute value
     *
     * @return bool true if the attribute is considered dangerous
     */
    private function isExactDangerousAttribute(string $name, string $value): bool
    {
        $value = $this->normalizeValue($value);

        return \in_array($name, SvgAttribute::dangerousExact(), true)
            && $this->matchesPattern($value, self::DANGEROUS_PROTOCOLS_REGEX);
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
                $name = $attribute->name;
                $value = $this->normalizeValue($attribute->value);

                if ($this->isDangerousAttribute($name, $value)) {
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
     * @param string $name  the attribute name
     * @param string $value the attribute value
     *
     * @return bool true if the attribute is dangerous
     */
    private function isDangerousAttribute(string $name, string $value): bool
    {
        $name = mb_strtolower($name);

        if ($this->hasDangerousPrefix($name)) {
            return true;
        }

        $value = $this->normalizeValue($value);

        if ($this->isExactDangerousAttribute($name, $value)) {
            return true;
        }

        if (SvgAttribute::Values->value === $name && $this->isSmilValuesDangerous($value)) {
            return true;
        }

        if ($this->isUrlAttributeDangerous($name, $value)) {
            return true;
        }

        if (
            SvgAttribute::Style->value === $name
            && $this->matchesPattern($value, self::STYLE_DANGEROUS_REGEX)
        ) {
            return true;
        }

        return SvgAttribute::Src->value === $name && $this->matchesPattern($value, self::URI_PROTOCOL_REGEX);
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
        $parts = explode(';', $value);

        foreach ($parts as $part) {
            if ($this->matchesPattern(trim($part), self::DANGEROUS_PROTOCOLS_REGEX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if an attribute name has a dangerous prefix (e.g., "on").
     *
     * @param string $name the attribute name
     *
     * @return bool true if the prefix is dangerous
     */
    private function hasDangerousPrefix(string $name): bool
    {
        foreach (self::DANGEROUS_ATTR_PREFIXES as $prefix) {
            if (str_starts_with(mb_strtolower($name), $prefix)) {
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
     * @param string $name  the attribute name
     * @param string $value the attribute value
     *
     * @return bool true if the URL is dangerous
     */
    private function isUrlAttributeDangerous(string $name, string $value): bool
    {
        if (!\in_array($name, SvgAttribute::dangerous(), true)) {
            return false;
        }

        if (1 === preg_match(self::URL_FUNCTION_REGEX, $value, $matches)) {
            $urlInside = trim($matches[2]);

            return 1 === preg_match(self::URL_PROTOCOL_OR_RELATIVE_REGEX, $urlInside);
        }

        return 1 === preg_match(self::URL_PROTOCOL_OR_RELATIVE_REGEX, trim($value));
    }

    /**
     * Removes `<style>` elements that contain potentially unsafe content.
     *
     * This includes styles that use `@import` to load external CSS or contain
     * other dangerous constructs.
     *
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeStyleWithImport(\DOMDocument $domDocument): void
    {
        $domNodeList = $domDocument->getElementsByTagName(SvgTag::Style->value);

        for ($i = $domNodeList->length - 1; $i >= 0; --$i) {
            $style = $domNodeList->item($i);
            if (!$style instanceof \DOMElement) {
                continue;
            }

            $text = $style->textContent ?? '';
            if ($this->matchesPattern($text, self::STYLE_NODE_DANGEROUS_REGEX)
                && $style->parentNode instanceof \DOMNode
            ) {
                $style->parentNode->removeChild($style);
            }
        }
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
        do {
            $prev = $value;
            $value = html_entity_decode($value, \ENT_QUOTES | \ENT_HTML5 | \ENT_XML1, SvgDefaults::XML_ENCODING);
        } while ($value !== $prev);

        $value = preg_replace_callback(self::CSS_HEX_ESCAPE_REGEX, static function (array $match): string {
            $code = (int) hexdec($match[1]);

            return ($code > 0 && $code <= 0x10_FF_FF) ? mb_chr($code, SvgDefaults::XML_ENCODING) : '';
        }, $value);

        $value = preg_replace(self::CONTROL_CHARS_REGEX, '', (string) $value);

        $value = $this->transliterateHomoglyphs((string) $value);

        $value = preg_replace(self::WHITESPACE_REGEX, '', $value);

        return mb_strtolower(trim((string) $value), SvgDefaults::XML_ENCODING);
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

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
