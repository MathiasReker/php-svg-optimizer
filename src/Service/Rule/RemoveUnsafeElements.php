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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DangerousAttributeValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TagMatcherTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnsafeElements implements SvgOptimizerRuleInterface
{
    use DangerousAttributeValueTrait;
    use EntityReferenceTrait;
    use TagMatcherTrait;

    private const array DANGEROUS_ATTR_PREFIXES = [
        'on',
    ];

    private const array URI_ATTRIBUTES = [
        'action',
        'background',
        'base',
        'cite',
        'codebase',
        'data',
        'dynsrc',
        'formaction',
        'lowsrc',
        'ping',
        'poster',
    ];

    private const array CLOBBERING_ATTRIBUTES = [
        'name',
    ];

    private const array HTML_BREAKOUT_TAGS = [
        'b', 'big', 'blockquote', 'body', 'br', 'center', 'code', 'dd', 'div', 'dl', 'dt', 'em', 'embed',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'hr', 'i', 'img', 'li', 'listing', 'menu', 'meta', 'nobr',
        'ol', 'p', 'pre', 'ruby', 's', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tt', 'u',
        'ul', 'var',
    ];

    private const array FONT_BREAKOUT_ATTRIBUTES = ['color', 'face', 'size'];

    private const string FONT_TAG = 'font';

    private const string DANGEROUS_PROTOCOLS_REGEX = '~^\s*(?:(?:javascript|livescript|mocha|vbscript|vbs|file|http|https|mailto|ftp|tel|sms|callto|cis|xmpp|blob|jar|mhtml|ms-its|res|view-source|wyciwyg):|data:(?!image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,)|//)~ix';

    private const string DATA_URI_REGEX = '/^\s*data:/i';

    private const string UNSAFE_COMMENT_REGEX = '~^-?>|<[/\w!?]~';

    private const string CSS_IMAGE_SET_REGEX = '/image-set\s*\(((?:[^()]++|\([^()]*+\)?)*+)/i';

    private const string CSS_QUOTED_STRING_REGEX = '/([\'"])(.*?)\1/s';

    private const string STYLE_NODE_DANGEROUS_TAG_REGEX = '/<\s*(script|iframe|object|textarea|embed|link|svg)/i';

    private const string URL_FUNCTION_REGEX = '/url\(\s*([\'"]?)([^\'")]*)/i';

    private const string CSS_HEX_ESCAPE_REGEX = '/\\\([0-9a-f]{2,6})/i';

    private const string CONTROL_CHARS_REGEX = '/[\x00-\x1F\x7F]+/u';

    private const string WHITESPACE_REGEX = '/\s+/u';

    private const string C_STYLE_COMMENT_REGEX = '/\/\*.*?\*\//s';

    private const string DANGEROUS_STYLE_REGEX = '/@import|expression/i';

    private const string SRCSET_SPLIT_REGEX = '/\s+/';

    private const string SMIL_VALUES_SAFE_DATA_URI_REGEX = '~data:image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,.*?(?=;|$)~i';

    private const string PROCESSING_INSTRUCTION_QUERY = '//processing-instruction()';

    private const string COMMENT_QUERY = '//comment()';

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
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->expandEntityReferences($domDocument);
        $this->removeNodesByQuery($domDocument, self::PROCESSING_INSTRUCTION_QUERY, static fn (): bool => true);
        $this->removeNodesByQuery(
            $domDocument,
            self::COMMENT_QUERY,
            fn (\DOMNode $domNode): bool => $this->matchesPattern((string) $domNode->nodeValue, self::UNSAFE_COMMENT_REGEX)
        );
        $this->removeElements($domDocument, fn (\DOMElement $domElement): bool => $this->isForeignElement($domElement));
        $this->removeElements($domDocument, fn (\DOMElement $domElement): bool => $this->isTagIn($domElement, SvgTag::dangerous()));
        $this->removeElements($domDocument, fn (\DOMElement $domElement): bool => $this->isHrefAnimation($domElement));
        $this->removeElements($domDocument, fn (\DOMElement $domElement): bool => $this->isHtmlBreakout($domElement));
        $this->removeConditionallyDangerousTags($domDocument);
        $this->removeDangerousAttributes($domDocument);
        $this->removeStyleWithImport($domDocument);
    }

    /**
     * @param \DOMDocument             $domDocument the DOM document to clean
     * @param string                   $query       the XPath query selecting candidate nodes
     * @param callable(\DOMNode): bool $predicate   returns true for nodes to remove
     */
    private function removeNodesByQuery(\DOMDocument $domDocument, string $query, callable $predicate): void
    {
        $domNodeList = (new \DOMXPath($domDocument))->query($query);

        if (false === $domNodeList) {
            return;
        }

        foreach (iterator_to_array($domNodeList, false) as $domNode) {
            if ($domNode instanceof \DOMNode && $predicate($domNode)) {
                $domNode->parentNode?->removeChild($domNode);
            }
        }
    }

    /**
     * @param \DOMDocument                $domDocument the DOM document to clean
     * @param callable(\DOMElement): bool $predicate   returns true for elements to remove
     */
    private function removeElements(\DOMDocument $domDocument, callable $predicate): void
    {
        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            if ($domElement !== $domDocument->documentElement && $predicate($domElement)) {
                $domElement->parentNode?->removeChild($domElement);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isHtmlBreakout(\DOMElement $domElement): bool
    {
        $fontBreakout = $this->isTagIn($domElement, [self::FONT_TAG])
            && [] !== array_filter(self::FONT_BREAKOUT_ATTRIBUTES, $domElement->hasAttribute(...));

        return $fontBreakout || $this->isTagIn($domElement, self::HTML_BREAKOUT_TAGS);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isForeignElement(\DOMElement $domElement): bool
    {
        return !\in_array($domElement->namespaceURI, [null, '', SvgNamespace::Svg->value], true);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isHrefAnimation(\DOMElement $domElement): bool
    {
        return $this->isTagIn($domElement, [...SvgTag::animation(), ...SvgTag::conditionalDangerous()])
            && str_contains(mb_strtolower($domElement->getAttribute(SvgAttribute::AttributeName->value)), SvgAttribute::Href->value);
    }

    /**
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
            if ($this->isStyleNodeDangerous($text) && $style->parentNode instanceof \DOMNode) {
                $style->parentNode->removeChild($style);
            }
        }
    }

    /**
     * @param string $text the text content of a `<style>` element
     *
     * @return bool true if the style element's content is dangerous
     */
    private function isStyleNodeDangerous(string $text): bool
    {
        if ($this->matchesPattern($text, self::STYLE_NODE_DANGEROUS_TAG_REGEX)) {
            return true;
        }

        return $this->isStyleAttributeDangerous($this->normalizeValue($text));
    }
}
