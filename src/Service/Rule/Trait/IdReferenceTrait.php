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

/**
 * @internal
 *
 * @no-named-arguments
 */
trait IdReferenceTrait
{
    use DocumentScriptTrait;

    private const string FRAGMENT_REFERENCE_REGEX = '/#((?:\\\[0-9a-fA-F]{1,6}[ \t\n\r\f]?|\\\.|[^\s#,;\'"()\[\]{}<>\\\])+)/';

    private const string CSS_ESCAPE_REGEX = '/\\\(?:([0-9a-fA-F]{1,6})[ \t\n\r\f]?|(.))/s';

    private const string REPLACEMENT_CHARACTER = "\u{FFFD}";

    private const int MAX_CODE_POINT = 0x10_FF_FF;

    private const int SURROGATE_CODE_POINTS = 0xD8_00;

    private const int SURROGATE_MASK = 0x1F_F8_00;

    private const string ID_PART_REGEX = '/[^\w-]?[\w-]*/';

    private const string ID_LIST_ITEM_REGEX = '/\S+/';

    private const array IGNORED_REFERENCE_TAGS = ['altGlyph', 'glyphRef', 'tref'];

    private const string ID_ATTRIBUTE_SELECTOR_REGEX = '/\[\s*(?:[\w*-]*\|)?id\b/i';

    private const string EXACT_ID_ATTRIBUTE_SELECTOR_REGEX
        = '/\[\s*(?:[\w*-]*\|)?id\s*=\s*(?<id>"[^"]*"|\'[^\']*\'|[^\s\]"\']+)\s*\]/i';

    private const string SMIL_ITEM_SEPARATOR = ';';

    private const array ID_LIST_ATTRIBUTES = [
        'aria-activedescendant', 'aria-controls', 'aria-describedby', 'aria-details', 'aria-errormessage',
        'aria-flowto', 'aria-labelledby', 'aria-owns', 'for', 'form', 'headers', 'list',
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return array<string, true> the ids referenced by attributes, for rules that leave documents with a style sheet
     *                             untouched
     */
    private function collectReferencedIds(\DOMDocument $domDocument): array
    {
        $referencedIds = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach ($domNodeList->attributes as $attribute) {
                $referencedIds += $this->attributeReferences($attribute);
            }
        }

        return $referencedIds;
    }

    /**
     * @param \DOMElement         $domElement    the element to check
     * @param array<string, true> $referencedIds the referenced ids
     *
     * @return bool true if the `id` or `xml:id` of the element is referenced
     */
    private function isReferencedElement(\DOMElement $domElement, array $referencedIds): bool
    {
        foreach ([
            $domElement->getAttribute(SvgAttribute::Id->value),
            $domElement->getAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value),
        ] as $id) {
            if ('' !== $id && \array_key_exists($id, $referencedIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement         $domElement    the root of the subtree to check
     * @param array<string, true> $referencedIds the referenced ids
     * @param list<string>        $ignoredTags   the elements whose content inherits from their own ancestors
     *
     * @return bool true if the element or a descendant other than the ignored elements is referenced by id
     */
    private function hasReferencedElement(\DOMElement $domElement, array $referencedIds, array $ignoredTags): bool
    {
        foreach ([[$domElement], $domElement->getElementsByTagName('*')] as $elements) {
            foreach ($elements as $element) {
                if (!\in_array($element->localName, $ignoredTags, true)
                    && $this->isReferencedElement($element, $referencedIds)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument        $domDocument the DOM document to scan
     * @param list<string>        $definitions the elements that only render when referenced
     * @param list<string>        $alwaysLive  the elements whose content counts as referenced, such as sprite symbols
     * @param array<string, true> $seeds       the ids that count as referenced from outside the document
     *
     * @return array<string, true> the referenced ids, and the ids referenced from within the elements they identify
     */
    private function collectLiveReferencedIds(\DOMDocument $domDocument, array $definitions, array $alwaysLive, array $seeds): array
    {
        $elementsById = [];
        $live = $seeds;

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $elementsById[$domNodeList->getAttribute('id')] ??= $domNodeList;

            if (SvgTag::Style->value === $domNodeList->localName
                || !$this->isInsideDefinition($domNodeList, $definitions, $alwaysLive)) {
                $live += $this->liveElementReferences($domNodeList);
            }
        }

        unset($elementsById['']);

        return $this->followReferences($live, $elementsById);
    }

    /**
     * @param array<string, true>        $live         the ids referenced so far
     * @param array<string, \DOMElement> $elementsById the elements by id
     *
     * @return array<string, true> the ids referenced so far and from within the elements they identify
     */
    private function followReferences(array $live, array $elementsById): array
    {
        $pending = $live;

        while ([] !== $pending) {
            $id = array_key_last($pending);
            unset($pending[$id]);

            if (\array_key_exists($id, $elementsById)) {
                $references = array_diff_key($this->subtreeReferences($elementsById[$id]), $live);
                $live += $references;
                $pending += $references;
            }
        }

        return $live;
    }

    /**
     * @param \DOMElement $domElement the root of the subtree to scan
     *
     * @return array<string, true> the ids referenced by the element and its descendants
     */
    private function subtreeReferences(\DOMElement $domElement): array
    {
        $referencedIds = $this->liveElementReferences($domElement);

        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            $referencedIds += $this->liveElementReferences($domNodeList);
        }

        return $referencedIds;
    }

    /**
     * @param \DOMElement $domElement the element to scan
     *
     * @return array<string, true> the ids referenced by the element, or none if browsers ignore the element, as they
     *                             ignore `<tref>`, `<altGlyph>` and `<glyphRef>`
     */
    private function liveElementReferences(\DOMElement $domElement): array
    {
        return \in_array($domElement->localName, self::IGNORED_REFERENCE_TAGS, true) ? [] : $this->elementReferences($domElement);
    }

    /**
     * @param \DOMElement $domElement the element to scan
     *
     * @return array<string, true> the ids referenced by its attributes, and by its text if it is a `<style>`
     */
    private function elementReferences(\DOMElement $domElement): array
    {
        $referencedIds = SvgTag::Style->value === $domElement->localName
            ? $this->styleSheetReferences($domElement->textContent)
            : [];

        foreach ($domElement->attributes as $attribute) {
            $referencedIds += $this->attributeReferences($attribute);
        }

        return $referencedIds;
    }

    /**
     * @param \DOMElement  $domElement  the element to check
     * @param list<string> $definitions the elements that only render when referenced
     * @param list<string> $alwaysLive  the elements whose content counts as referenced
     *
     * @return bool true if the element is, or is inside, a `<defs>` or a definition element, and the nearest such
     *              ancestor is not one whose content always counts as referenced
     */
    private function isInsideDefinition(\DOMElement $domElement, array $definitions, array $alwaysLive): bool
    {
        for ($node = $domElement; $node instanceof \DOMElement; $node = $node->parentNode) {
            if (\in_array($node->localName, $alwaysLive, true)) {
                return false;
            }

            if (SvgTag::Defs->value === $node->localName || \in_array($node->localName, $definitions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMAttr $domAttr the attribute to scan
     *
     * @return array<string, true> the ids referenced through a fragment, an id list or a SMIL timing value
     */
    private function attributeReferences(\DOMAttr $domAttr): array
    {
        return $this->attributeValueReferences($domAttr->nodeName, $domAttr->value);
    }

    /**
     * @param string $name  the qualified name of an attribute
     * @param string $value the value of the attribute
     *
     * @return array<string, true> the ids referenced through a fragment, an id list or a SMIL timing value
     */
    private function attributeValueReferences(string $name, string $value): array
    {
        $referencedIds = $this->fragmentReferences($value);

        if (\in_array($name, self::ID_LIST_ATTRIBUTES, true)) {
            preg_match_all(self::ID_LIST_ITEM_REGEX, $value, $items);
            $referencedIds += array_fill_keys($items[0], true);
        }

        if (SvgAttribute::Begin->value === $name || SvgAttribute::End->value === $name) {
            foreach (explode(self::SMIL_ITEM_SEPARATOR, $value) as $item) {
                $referencedIds += $this->idCandidates(trim($item));
            }
        }

        return $referencedIds;
    }

    /**
     * @param string $css the style sheet to scan
     *
     * @return array<string, true> the ids referenced through `#id` or an exact attribute selector such as `[id="a"]`
     */
    private function styleSheetReferences(string $css): array
    {
        $referencedIds = $this->fragmentReferences($css);

        preg_match_all(self::EXACT_ID_ATTRIBUTE_SELECTOR_REGEX, $css, $matches);

        foreach ($matches['id'] as $id) {
            $referencedIds[$this->cssUnescape(trim($id, '"\''))] = true;
        }

        return $referencedIds;
    }

    /**
     * @param string $css the style sheet to scan
     *
     * @return bool true if the style sheet has an attribute selector on `id` that may match other ids than its value,
     *              such as `[id]` or `[id^="a"]`
     */
    private function hasInexactIdAttributeSelector(string $css): bool
    {
        return preg_match_all(self::ID_ATTRIBUTE_SELECTOR_REGEX, $css)
            !== preg_match_all(self::EXACT_ID_ATTRIBUTE_SELECTOR_REGEX, $css);
    }

    /**
     * @param string $text the attribute value or style sheet to scan
     *
     * @return array<string, true> the ids referenced through `#id`
     */
    private function fragmentReferences(string $text): array
    {
        preg_match_all(self::FRAGMENT_REFERENCE_REGEX, $text, $matches);

        $referencedIds = [];

        foreach ($matches[1] as $token) {
            $referencedIds += $this->idCandidates($token) + $this->idCandidates(rawurldecode($token));
        }

        return $referencedIds;
    }

    /**
     * @param string $token a reference that may continue past the id, such as `a.click` or `a:hover`
     *
     * @return array<string, true> every prefix that may be the id, both escaped and unescaped
     */
    private function idCandidates(string $token): array
    {
        $candidates = [];
        $prefix = '';

        preg_match_all(self::ID_PART_REGEX, $token, $parts);

        foreach ($parts[0] as $part) {
            $prefix .= $part;
            $candidates[] = $prefix;
            $candidates[] = stripslashes($prefix);
            $candidates[] = $this->cssUnescape($prefix);
        }

        return array_fill_keys($candidates, true);
    }

    /**
     * @param string $value a CSS identifier that may contain escapes, such as `\31 23`
     *
     * @return string the identifier with its escapes decoded, such as `123`
     */
    private function cssUnescape(string $value): string
    {
        return preg_replace_callback(
            self::CSS_ESCAPE_REGEX,
            static function (array $match): string {
                if ('' === $match[1]) {
                    return $match[2];
                }

                $codePoint = (int) hexdec($match[1]);

                return 0 === $codePoint || $codePoint > self::MAX_CODE_POINT || self::SURROGATE_CODE_POINTS === ($codePoint & self::SURROGATE_MASK)
                    ? self::REPLACEMENT_CHARACTER
                    : mb_chr($codePoint);
            },
            $value,
        ) ?? $value;
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if the document has a script, or a style sheet with an attribute selector that may match ids
     *              other than its value
     */
    private function mayTargetAnyId(\DOMDocument $domDocument): bool
    {
        foreach ($this->elementsNamed($domDocument, SvgTag::Style->value) as $domElement) {
            if ($this->hasInexactIdAttributeSelector($domElement->textContent)) {
                return true;
            }
        }

        return $this->hasScriptOrEventHandler($domDocument);
    }
}
