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

    private const array DANGEROUS_ATTR_PREFIXES = [
        'on',
    ];

    private const string XML_STYLESHEET_PI = 'xml-stylesheet';

    /**
     * @see https://regex101.com/r/Kd3TRU/1
     */
    private const string DANGEROUS_PROTOCOLS_REGEX = '~^\s*(?:(?:javascript|file|vbscript|http|https|mailto|ftp|tel|sms|callto|cis|xmpp|blob):|data:(?!image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,)|//)~ix';

    /**
     * @see https://regex101.com/r/hN9M9b/1
     */
    private const string STYLE_NODE_DANGEROUS_TAG_REGEX = '/<\s*(script|iframe|object|textarea|embed|link|svg)/i';

    /**
     * @see https://regex101.com/r/WQHx9p/1
     */
    private const string URL_FUNCTION_REGEX = '/url\(\s*([\'"]?)(.*?)\1\s*\)/i';

    /**
     * @see https://regex101.com/r/Wpra41/1
     */
    private const string CSS_HEX_ESCAPE_REGEX = '/\\\([0-9a-f]{2,6})/i';

    /**
     * @see https://regex101.com/r/hNSaol/1
     */
    private const string CONTROL_CHARS_REGEX = '/[\x00-\x1F\x7F]+/u';

    /**
     * @see https://regex101.com/r/6DKmg3/1
     */
    private const string WHITESPACE_REGEX = '/\s+/u';

    /**
     * @see https://regex101.com/r/ZieTB0/1
     */
    private const string C_STYLE_COMMENT_REGEX = '/\/\*.*?\*\//s';

    /**
     * @see https://regex101.com/r/fBom15/1
     */
    private const string DANGEROUS_STYLE_REGEX = '/@import|expression/i';

    /**
     * @see https://regex101.com/r/C5Lghy/1
     */
    private const string SRCSET_SPLIT_REGEX = '/\s+/';

    /**
     * @see https://regex101.com/r/tCMDDi/1
     */
    private const string SMIL_VALUES_SAFE_DATA_URI_REGEX = '~data:image/(?:png|gif|jpeg|jpg|webp|avif|svg\+xml);base64,.*?(?=;|$)~i';

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
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeDangerousElements(\DOMDocument $domDocument): void
    {
        $this->removeAlwaysDangerousTags($domDocument);
        $this->removeConditionallyDangerousTags($domDocument);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeAlwaysDangerousTags(\DOMDocument $domDocument): void
    {
        foreach (SvgTag::dangerous() as $tag) {
            $this->removeAllElementsByTagName($domDocument, $tag);
        }
    }

    /**
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
        return $this->matchesPattern($text, self::STYLE_NODE_DANGEROUS_TAG_REGEX)
            || $this->isStyleAttributeDangerous($text);
    }
}
