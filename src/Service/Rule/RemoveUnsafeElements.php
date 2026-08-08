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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DangerousAttributeValueTrait;

/**
 * @no-named-arguments
 */
final readonly class RemoveUnsafeElements implements SvgOptimizerRuleInterface
{
    use DangerousAttributeValueTrait;

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
     * Regular expression for detecting dangerous protocols in URLs.
     *
     * This pattern matches protocols that are considered unsafe, such as javascript, file, http, https, and protocol-relative URLs.
     * It allows data URIs for images (data:image/...) but blocks other data URIs.
     *
     * @see https://regex101.com/r/Kd3TRU/1
     */
    private const string DANGEROUS_PROTOCOLS_REGEX = '~^\s*(?:(?:javascript|file|vbscript|http|https|mailto|ftp|tel|sms|callto|cis|xmpp|blob):|data:(?!image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,)|//)~ix';

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

    /**
     * Regular expression for removing C-style comments.
     *
     * @see https://regex101.com/r/ZieTB0/1
     */
    private const string C_STYLE_COMMENT_REGEX = '/\/\*.*?\*\//s';

    /**
     * Regular expression for detecting dangerous styles.
     *
     * @see https://regex101.com/r/fBom15/1
     */
    private const string DANGEROUS_STYLE_REGEX = '/@import|expression/i';

    /**
     * Regular expression for splitting srcset candidates.
     *
     * @see https://regex101.com/r/C5Lghy/1
     */
    private const string SRCSET_SPLIT_REGEX = '/\s+/';

    /**
     * Regular expression for removing safe data URIs from SMIL values.
     *
     * @see https://regex101.com/r/tCMDDi/1
     */
    private const string SMIL_VALUES_SAFE_DATA_URI_REGEX = '~data:image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,.*?(?=;|$)~i';

    /**
     * XPath query for selecting all processing instructions.
     */
    private const string PROCESSING_INSTRUCTION_QUERY = '//processing-instruction()';

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
        $domXPath = new \DOMXPath($domDocument);
        $pis = $domXPath->query(self::PROCESSING_INSTRUCTION_QUERY);

        if (false === $pis) {
            return;
        }

        foreach ($pis as $pi) {
            if ($pi instanceof \DOMProcessingInstruction
                && str_contains(mb_strtolower($pi->nodeName), self::XML_STYLESHEET_PI)
                && $pi->parentNode instanceof \DOMNode
            ) {
                $pi->parentNode->removeChild($pi);
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
        $domNodeList = $domDocument->getElementsByTagName('*');
        $nodesToRemove = [];

        foreach ($domNodeList as $node) {
            $localName = $node->localName ?? $node->tagName;

            if (0 === strcasecmp($localName, $tagName)) {
                $nodesToRemove[] = $node;
            }
        }

        foreach ($nodesToRemove as $nodeToRemove) {
            $nodeToRemove->parentNode?->removeChild($nodeToRemove);
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

                $element = $nodes->item($i);
                $attributeName = mb_strtolower($element->getAttribute(SvgAttribute::AttributeName->value));
                if (\in_array($attributeName, [SvgAttribute::Href->value, SvgAttribute::XlinkHref->value], true)) {
                    $element->parentNode?->removeChild($element);
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

        foreach (SvgAttribute::dangerousExact() as $attrName) {
            if ($domNode->hasAttribute($attrName)) {
                $value = $this->normalizeValue($domNode->getAttribute($attrName));

                if ($this->matchesPattern($value, self::DANGEROUS_PROTOCOLS_REGEX)) {
                    $this->unwrapNode($domNode);

                    return;
                }
            }
        }
    }

    /**
     * Unwraps a DOM element by moving its children to its parent and removing the element itself.
     *
     * @param \DOMElement $domElement the DOM element to unwrap
     */
    private function unwrapNode(\DOMElement $domElement): void
    {
        $parent = $domElement->parentNode;
        if (!$parent instanceof \DOMNode) {
            return;
        }

        if ($domElement->hasChildNodes()) {
            while ($domElement->firstChild instanceof \DOMNode) {
                $parent->insertBefore($domElement->firstChild, $domElement);
            }
        }

        $parent->removeChild($domElement);
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
}
