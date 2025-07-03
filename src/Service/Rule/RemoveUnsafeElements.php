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
 */
final readonly class RemoveUnsafeElements implements SvgOptimizerRuleInterface
{
    /**
     * List of dangerous SVG tags that should be removed.
     *
     * These tags are known to pose security risks and should not be present in the SVG content.
     */
    private const array DANGEROUS_TAGS = [
        'script',
        'foreignObject',
        'iframe',
        'object',
        'embed',
        'image',
        'use',
        'link',
        'tref',
        'form',
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
     * @see https://regex101.com/r/OCadvZ/1
     */
    private const string URI_PROTOCOL_REGEX = '/^\s*(javascript|data|vbscript):/i';

    /**
     * Regular expression for detecting fill URLs that are potentially unsafe.
     *
     * This pattern matches fill attributes that contain URLs, which may lead to security vulnerabilities.
     *
     * @see https://regex101.com/r/buQ6tP/1
     */
    private const string FILL_URL_REGEX = '/^url\(\s*[\'"]?.+[\'"]?\s*\)$/i';

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
                && str_contains(mb_strtolower($node->nodeName), 'xml-stylesheet')) {
                $domDocument->removeChild($node);
            }
        }
    }

    /**
     * Remove dangerous elements from the SVG document.
     *
     * This method iterates through the DOM and removes any elements that match the dangerous tags defined in DANGEROUS_TAGS.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeDangerousElements(\DOMDocument $domDocument): void
    {
        foreach (self::DANGEROUS_TAGS as $tag) {
            while (true) {
                $nodes = $domDocument->getElementsByTagName($tag);
                if (0 === $nodes->length) {
                    break;
                }

                $node = $nodes->item(0);
                if ($node instanceof \DOMNode && $node->parentNode instanceof \DOMNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
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
     * Check if an attribute is considered dangerous based on its name and value.
     *
     * This method checks if the attribute name starts with a dangerous prefix,
     * or if it matches specific dangerous patterns defined in DANGEROUS_ATTRS_EXACT.
     *
     * @param string $name  The name of the attribute to check
     * @param string $value The value of the attribute to check
     *
     * @return bool True if the attribute is dangerous, false otherwise
     */
    private function isDangerousAttribute(string $name, string $value): bool
    {
        return $this->hasDangerousPrefix($name)
            || (\in_array($name, self::DANGEROUS_ATTRS_EXACT, true) && $this->matchesPattern($value, self::URI_PROTOCOL_REGEX))
            || ('fill' === $name && $this->matchesPattern($value, self::FILL_URL_REGEX))
            || ('style' === $name && $this->matchesPattern($value, self::STYLE_DANGEROUS_REGEX))
            || ('src' === $name && $this->matchesPattern($value, self::URI_PROTOCOL_REGEX));
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
                && $style->parentNode instanceof \DOMNode) {
                $style->parentNode->removeChild($style);
            }
        }
    }
}
