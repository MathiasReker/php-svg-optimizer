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

/**
 * @no-named-arguments
 *
 * @phpstan-ignore-next-line
 */
final readonly class RemoveUnsafeElements implements SvgOptimizerRuleInterface
{
    /**
     * List of dangerous SVG tags that should be removed.
     *
     * These tags are known to pose security risks and should not be present in the SVG content.
     */
    private const array ALWAYS_REMOVE_TAGS = [
        'script',
        'foreignObject',
        'iframe',
        'object',
        'embed',
    ];

    private const array CONDITIONAL_TAGS = [
        'image',
        'use',
        'link',
        'tref',
    ];

    /**
     * List of dangerous attribute prefixes and exact names that should be removed.
     *
     * Attributes with these prefixes or exact names are considered unsafe and should not be present in the SVG content.
     */
    private const array DANGEROUS_ATTR_PREFIXES = [
        'on',
    ];

    /**
     * List of dangerous attributes that should be removed if they match specific patterns.
     *
     * These attributes are known to pose security risks when they contain certain values.
     */
    private const array DANGEROUS_ATTRS_EXACT = [
        'xlink:href',
        'href',
    ];

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
     * @see https://regex101.com/r/hp3GSh/1
     */
    private const string DANGEROUS_PROTOCOLS_REGEX = '~^(?:javascript|data|file|http|https|//)~i';

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
     * List of attributes that can contain URLs and should be checked for unsafe content.
     *
     * These attributes are commonly used in SVG files and may contain references to external resources.
     */
    private const array URL_ATTRIBUTES = [
        'fill',
        'stroke',
        'filter',
        'clip-path',
        'mask',
        'marker-start',
        'marker-mid',
        'marker-end',
        'begin',
        'end',
        'from',
        'to',
        'values',
        'style',
    ];

    /**
     * Optimize the SVG document by removing unsafe elements and attributes.
     *
     * This method processes the SVG content to remove processing instructions, dangerous elements,
     * and attributes that could lead to security vulnerabilities.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeProcessingInstructions($domDocument);
        $this->removeDangerousElements($domDocument);
        $this->removeDangerousAttributes($domDocument);
        $this->removeStyleWithImport($domDocument);
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Remove processing instructions that match specific criteria from the SVG document.
     *
     * This method iterates through the DOM nodes and removes any processing instructions
     * that contain 'xml-stylesheet' in their name, which are considered unsafe.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeProcessingInstructions(\DOMDocument $domDocument): void
    {
        for ($node = $domDocument->firstChild; $node instanceof \DOMNode; $node = $node->nextSibling) {
            if ($node instanceof \DOMProcessingInstruction
                && str_contains(mb_strtolower($node->nodeName), 'xml-stylesheet')
            ) {
                $domDocument->removeChild($node);
            }
        }
    }

    /**
     * Remove dangerous elements from the SVG document.
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
     * Remove always dangerous tags from the SVG document.
     *
     * This method iterates through a predefined list of tags that are always considered unsafe
     * and removes them from the SVG document.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeAlwaysDangerousTags(\DOMDocument $domDocument): void
    {
        foreach (self::ALWAYS_REMOVE_TAGS as $tag) {
            $this->removeAllElementsByTagName($domDocument, $tag);
        }
    }

    /**
     * Remove all elements with the specified tag name from the SVG document.
     *
     * This method iterates through all elements with the given tag name and removes them from their parent nodes.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     * @param string       $tagName     The name of the tag to remove from the SVG document
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
     * Remove conditionally dangerous tags from the SVG document.
     *
     * This method iterates through specific tags that may contain unsafe content and removes them
     * if they contain attributes that are considered dangerous, such as href or xlink:href.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeConditionallyDangerousTags(\DOMDocument $domDocument): void
    {
        foreach (self::CONDITIONAL_TAGS as $tag) {
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
     * Remove a node if it contains dangerous attributes.
     *
     * This method checks if the node is an element and has attributes that are considered unsafe.
     * If it does, the node is removed from its parent.
     *
     * @param \DOMNode $domNode The DOM node to check and potentially remove
     */
    private function removeIfDangerous(\DOMNode $domNode): void
    {
        if (!$domNode instanceof \DOMElement) {
            return;
        }

        foreach (['href', 'xlink:href'] as $attr) {
            $value = $domNode->getAttribute($attr);
            if ($this->isExactDangerousAttribute($attr, $value)) {
                if ($domNode->parentNode instanceof \DOMNode) {
                    $domNode->parentNode->removeChild($domNode);
                }

                break;
            }
        }
    }

    /**
     * Check if the attribute is an exact dangerous attribute based on its name and value.
     *
     * This method checks if the attribute name is in DANGEROUS_ATTRS_EXACT and if the value matches a specific URI protocol pattern.
     *
     * @param string $name  The name of the attribute to check
     * @param string $value The value of the attribute to check
     *
     * @return bool True if the attribute is an exact dangerous attribute, false otherwise
     */
    private function isExactDangerousAttribute(string $name, string $value): bool
    {
        return \in_array($name, self::DANGEROUS_ATTRS_EXACT, true)
            && $this->matchesPattern($value, self::DANGEROUS_PROTOCOLS_REGEX);
    }

    /**
     * Check if the given value matches a specific pattern.
     *
     * This method uses a regular expression to check if the value matches the provided pattern.
     *
     * @param string $value   The value to check
     * @param string $pattern The regex pattern to match against
     *
     * @return bool True if the value matches the pattern, false otherwise
     */
    private function matchesPattern(string $value, string $pattern): bool
    {
        return (bool) preg_match($pattern, $value);
    }

    /**
     * Remove dangerous attributes from the SVG document.
     *
     * This method iterates through all elements in the DOM and removes attributes that are considered unsafe
     * based on their names or values, as defined in DANGEROUS_ATTRS_EXACT and DANGEROUS_ATTR_PREFIXES.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeDangerousAttributes(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $elements = $domXPath->query('//*');

        if (!$elements instanceof \DOMNodeList) {
            return;
        }

        foreach ($elements as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }

            if (!$element->hasAttributes()) {
                continue;
            }

            /** @var \DOMAttr $attr */
            foreach (iterator_to_array($element->attributes, false) as $attr) {
                $name = $attr->name;
                $value = trim($attr->value);

                if ($this->isDangerousAttribute($name, $value)) {
                    $element->removeAttributeNode($attr);
                }
            }
        }
    }

    /**
     * Check if the attribute is dangerous based on its name and value.
     *
     * This method checks if the attribute name starts with a dangerous prefix, matches an exact dangerous attribute,
     * or contains unsafe content in its value.
     *
     * @param string $name  The name of the attribute to check
     * @param string $value The value of the attribute to check
     *
     * @return bool True if the attribute is considered dangerous, false otherwise
     */
    private function isDangerousAttribute(string $name, string $value): bool
    {
        $nameLower = mb_strtolower($name);

        if ($this->hasDangerousPrefix($nameLower)) {
            return true;
        }

        if ($this->isExactDangerousAttribute($nameLower, $value)) {
            return true;
        }

        if ($this->isUrlAttributeDangerous($nameLower, $value)) {
            return true;
        }

        if ('style' === $nameLower && $this->matchesPattern($value, self::STYLE_DANGEROUS_REGEX)) {
            return true;
        }

        return 'src' === $nameLower && $this->matchesPattern($value, self::URI_PROTOCOL_REGEX);
    }

    /**
     * Check if the attribute name starts with any of the dangerous prefixes.
     *
     * This method checks if the attribute name starts with any of the prefixes defined in DANGEROUS_ATTR_PREFIXES.
     *
     * @param string $name The name of the attribute to check
     *
     * @return bool True if the attribute name starts with a dangerous prefix, false otherwise
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
     * Check if the attribute is a URL attribute that contains dangerous content.
     *
     * This method checks if the attribute name is in URL_ATTRIBUTES and if the value matches a specific URL pattern.
     *
     * @param string $name  The name of the attribute to check
     * @param string $value The value of the attribute to check
     *
     * @return bool True if the attribute is a dangerous URL attribute, false otherwise
     */
    private function isUrlAttributeDangerous(string $name, string $value): bool
    {
        if (!\in_array($name, self::URL_ATTRIBUTES, true)) {
            return false;
        }

        if (1 === preg_match(self::URL_FUNCTION_REGEX, $value, $matches)) {
            $urlInside = trim($matches[2]);

            return 1 === preg_match(self::URL_PROTOCOL_OR_RELATIVE_REGEX, $urlInside);
        }

        return 1 === preg_match(self::URL_PROTOCOL_OR_RELATIVE_REGEX, trim($value));
    }

    /**
     * Remove style nodes that contain dangerous imports or expressions.
     *
     * This method iterates through all style elements in the SVG document and removes those
     * that contain unsafe patterns, such as @import statements or expressions that could lead to security vulnerabilities.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeStyleWithImport(\DOMDocument $domDocument): void
    {
        $domNodeList = $domDocument->getElementsByTagName('style');

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
}
