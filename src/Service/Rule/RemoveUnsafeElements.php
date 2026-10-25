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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DangerousAttributeValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\StyleSheetSanitizerTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TagMatcherTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\UseBombTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnsafeElements implements SvgOptimizerRuleInterface
{
    use CanonicalNameTrait;
    use DangerousAttributeValueTrait;
    use EntityReferenceTrait;
    use StyleSheetSanitizerTrait;
    use TagMatcherTrait;
    use UseBombTrait;

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

    private const string FOLDED_ATTRIBUTE_NAME = 'attributename';

    private const array EVENT_LISTENER_TAGS = ['handler', 'listener'];

    private const string DANGEROUS_PROTOCOLS_REGEX = '~^\s*(?:(?:javascript|livescript|mocha|vbscript|vbs|file|http|https|mailto|ftp|tel|sms|callto|cis|xmpp|blob|jar|mhtml|ms-its|res|view-source|wyciwyg):|data:(?!image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,)|[/\\\]{2})~ix';

    private const string DATA_URI_REGEX = '/^\s*data:/i';

    private const string UNSAFE_COMMENT_REGEX = '~^-?>|<[/\w!?]~';

    private const string CSS_IMAGE_SET_REGEX = '/image-set\s*\((?:[^()]++|\([^()]*+\)?)*+/i';

    private const string CSS_QUOTED_STRING_REGEX = '/([\'"])(.*?)\1/s';

    private const string STYLE_NODE_DANGEROUS_TAG_REGEX = '/<\s*(script|iframe|object|textarea|embed|link|svg)/i';

    private const string URL_FUNCTION_REGEX = '/url\(\s*([\'"]?)([^\'")]*)/i';

    private const string CSS_ESCAPE_REGEX = '/\\\(?:([0-9a-f]{1,6})|([^\r\n\f]))/i';

    private const string INVISIBLE_CHARS_REGEX = '/[\p{Cf}\x{2028}\x{2029}]+/u';

    private const string URL_IGNORED_CHARS_REGEX = '/[\x00-\x20\x7F\p{Cf}\x{2028}\x{2029}]+/u';

    private const string CONTROL_CHARS_REGEX = '/[\x00-\x1F\x7F]+/u';

    private const string WHITESPACE_REGEX = '/\s+/u';

    private const string C_STYLE_COMMENT_REGEX = '/\/\*.*?\*\//s';

    private const string DANGEROUS_STYLE_REGEX = '/@import|expression|(?<![\w-])behavior\s*:|-o-link|-moz-binding|javascript:/i';

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
        $this->removeElements($domDocument, fn (\DOMElement $domElement): bool => $this->isNonCanonicalElement($domElement));
        $this->removeElements($domDocument, fn (\DOMElement $domElement): bool => $this->isTagIn($domElement, [...SvgTag::dangerous(), ...self::EVENT_LISTENER_TAGS]));
        $this->removeElements($domDocument, fn (\DOMElement $domElement): bool => $this->isDangerousAnimation($domElement));
        $this->removeElements($domDocument, fn (\DOMElement $domElement): bool => $this->isHtmlBreakout($domElement));
        $this->removeConditionallyDangerousTags($domDocument);
        $this->removeDangerousAttributes($domDocument);
        $this->removeStyleWithImport($domDocument);
        $this->removeUseBombs($domDocument);
    }

    /**
     * @param \DOMDocument             $domDocument the DOM document to clean
     * @param string                   $query       the XPath query selecting candidate nodes
     * @param callable(\DOMNode): bool $predicate   returns true for nodes to remove
     */
    private function removeNodesByQuery(\DOMDocument $domDocument, string $query, callable $predicate): void
    {
        $domNodeList = (new \DOMXPath($domDocument))->query($query, null, false);

        foreach (false === $domNodeList ? [] : iterator_to_array($domNodeList, false) as $domNode) {
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
    private function isDangerousAnimation(\DOMElement $domElement): bool
    {
        return $this->isTagIn($domElement, [...SvgTag::animation(), ...SvgTag::conditionalDangerous()])
            && [] !== array_filter(
                $this->foldedAttributeValues($domElement, self::FOLDED_ATTRIBUTE_NAME),
                $this->isDangerousAnimatedName(...),
            );
    }

    /**
     * @param string $attributeName the value of an attributeName attribute
     *
     * @return bool true if animating the attribute could rewrite a link or an event handler
     */
    private function isDangerousAnimatedName(string $attributeName): bool
    {
        $attributeName = $this->normalizeValue($attributeName);
        $separator = mb_strrpos($attributeName, ':');
        $localName = false === $separator ? $attributeName : mb_substr($attributeName, $separator + 1);

        return str_contains($attributeName, SvgAttribute::Href->value) || $this->isDangerousAttributeName($localName);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeConditionallyDangerousTags(\DOMDocument $domDocument): void
    {
        foreach (SvgTag::conditionalDangerous() as $tag) {
            foreach (iterator_to_array($domDocument->getElementsByTagName($tag), false) as $domElement) {
                $this->removeIfDangerous($domElement);
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

        $dangerous = array_filter(
            SvgAttribute::dangerousExact(),
            fn (string $attrName): bool => $domNode->hasAttribute($attrName) && $this->isDangerousUrl($domNode->getAttribute($attrName)),
        );

        if ([] !== $dangerous) {
            $this->unwrapNode($domNode);
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

        foreach (array_reverse(iterator_to_array($domNodeList, false)) as $domElement) {
            $text = $domElement->textContent;

            if (!$this->isStyleNodeDangerous($text)) {
                continue;
            }

            $sanitized = $this->isCssTextStyle($domElement) ? $this->sanitizeStyleSheet($text) : false;

            if (false === $sanitized || '' === trim($sanitized, self::ASCII_WHITESPACE_CHARACTERS)) {
                $domElement->parentNode?->removeChild($domElement);
            } else {
                $domElement->textContent = $sanitized;
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
