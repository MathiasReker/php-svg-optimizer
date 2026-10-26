<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Xml;

use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait TextWhitespaceTrait
{
    private const string BLANK_TEXT_QUERY = '//text()[normalize-space() = ""][string-length() > 0][not(ancestor::*['
        . 'local-name() = "foreignObject" or (namespace-uri() != "" and namespace-uri() != "http://www.w3.org/2000/svg")'
        . '])]';

    private const string TEXT_ELEMENT_QUERY
        = '//*[local-name() = "text" and (namespace-uri() = "http://www.w3.org/2000/svg" or namespace-uri() = "")]';

    private const string TEXT_QUERY = './/text()';

    private const string LEADING_WHITESPACE_REGEX = '/^\s/';

    private const string SPACE_ATTRIBUTE = 'space';

    private const string SPACE_PRESERVE = 'preserve';

    private const string RENDERED_TEXT_QUERY
        = '//*[local-name() = "text" and (namespace-uri() = "http://www.w3.org/2000/svg" or namespace-uri() = "")]//text()';

    private const string COLLAPSIBLE_WHITESPACE_REGEX = '/(?<![\n\r])[\n\r]*[\t ][\t\n\r ]*/';

    private const array WHITE_SPACE_SOURCES = ['white-space', '@import', '\\'];

    private const string STYLESHEET_INSTRUCTION = 'xml-stylesheet';

    /**
     * @param \DOMDocument $domDocument the parsed document
     */
    private function removeBlankTextNodes(\DOMDocument $domDocument): void
    {
        $textContent = $this->textContentPositions($domDocument);
        $blankTextNodes = [];
        $parents = [];

        foreach ($this->blankTextNodes($domDocument) as $blankTextNode) {
            $parent = $blankTextNode->parentNode;

            if ($parent instanceof \DOMNode) {
                $blankTextNodes[spl_object_id($blankTextNode)] = $blankTextNode;
                $parents[spl_object_id($parent)] = $parent;
            }
        }

        foreach ($parents as $parent) {
            $this->removeBlankChildren($parent, $blankTextNodes, $textContent);
        }

        foreach ($this->textNodes($domDocument, $domDocument, self::TEXT_QUERY) as $domText) {
            if ($domText instanceof \DOMCdataSection) {
                $domText->replaceWith($domText->data);
            }
        }

        $domDocument->documentElement?->normalize();
    }

    /**
     * @param \DOMNode                          $domNode        the element whose blank children to remove
     * @param array<int, \DOMText>              $blankTextNodes the blank text nodes, by object id
     * @param array<int, array{\DOMText, bool}> $textContent    the text nodes in text content, by object id, with
     *                                                          whether text precedes and follows them
     */
    private function removeBlankChildren(\DOMNode $domNode, array $blankTextNodes, array $textContent): void
    {
        $followsWhitespace = false;

        foreach (iterator_to_array($domNode->childNodes, false) as $child) {
            if ($child instanceof \DOMText && \array_key_exists(spl_object_id($child), $blankTextNodes)) {
                if ($this->rendersAsSpace($child, $textContent)) {
                    $child->data = $this->collapseTextWhitespace($child);
                } elseif ($this->isIgnorableBlank($child, $followsWhitespace)) {
                    $domNode->removeChild($child);

                    continue;
                }
            }

            $followsWhitespace = $followsWhitespace || $this->startsWithWhitespace($child);
        }
    }

    /**
     * @param \DOMNode $domNode the node to check
     */
    private function startsWithWhitespace(\DOMNode $domNode): bool
    {
        return $domNode instanceof \DOMText
            && !$domNode instanceof \DOMCdataSection
            && 1 === preg_match(self::LEADING_WHITESPACE_REGEX, $domNode->data);
    }

    /**
     * @param \DOMDocument $domDocument the parsed document
     */
    private function collapseTextContentWhitespace(\DOMDocument $domDocument): void
    {
        if ($this->mayStyleWhiteSpace($domDocument)) {
            return;
        }

        foreach ($this->textNodes($domDocument, $domDocument, self::RENDERED_TEXT_QUERY) as $domText) {
            if (!$this->preservesSpace($domText)) {
                $domText->data = (string) preg_replace(self::COLLAPSIBLE_WHITESPACE_REGEX, ' ', $domText->data);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the parsed document
     *
     * @return bool true if the white-space property may be set, by the document or by a style sheet it links to
     */
    private function mayStyleWhiteSpace(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->childNodes as $child) {
            if ($child instanceof \DOMProcessingInstruction && self::STYLESHEET_INSTRUCTION === mb_strtolower($child->target)) {
                return true;
            }
        }

        $markup = mb_strtolower((string) $domDocument->saveXML($domDocument->documentElement));

        foreach (self::WHITE_SPACE_SOURCES as $source) {
            if (str_contains($markup, $source)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument the document to serialize
     *
     * @return array{list<\DOMText>, list<string>} the blank text nodes and their whitespace, to restore it
     */
    private function collapseBlankTextNodes(\DOMDocument $domDocument): array
    {
        $textContent = $this->textContentPositions($domDocument);
        $blankTextNodes = $this->blankTextNodes($domDocument);
        $whitespace = [];

        foreach ($blankTextNodes as $blankTextNode) {
            $whitespace[] = $blankTextNode->data;
            $blankTextNode->data = $this->rendersAsSpace($blankTextNode, $textContent) ? $this->collapseTextWhitespace($blankTextNode) : '';
        }

        return [$blankTextNodes, $whitespace];
    }

    /**
     * @param \DOMDocument $domDocument the document to search
     *
     * @return list<\DOMText> the text nodes holding only whitespace outside foreign content, without CDATA sections
     */
    private function blankTextNodes(\DOMDocument $domDocument): array
    {
        return array_values(array_filter(
            $this->textNodes($domDocument, $domDocument, self::BLANK_TEXT_QUERY),
            static fn (\DOMText $domText): bool => !$domText instanceof \DOMCdataSection,
        ));
    }

    /**
     * @param \DOMDocument $domDocument the document to search
     *
     * @return array<int, array{\DOMText, bool}> each text node in a `<text>` element, by object id, with whether text
     *                                           both precedes and follows it in its nearest `<text>` element
     */
    private function textContentPositions(\DOMDocument $domDocument): array
    {
        $positions = [];
        $domNodeList = (new \DOMXPath($domDocument))->query(self::TEXT_ELEMENT_QUERY, null, false);

        foreach (false === $domNodeList ? [] : $domNodeList as $textElement) {
            $textNodes = $textElement instanceof \DOMElement ? $this->textNodes($domDocument, $textElement, self::TEXT_QUERY) : [];
            $filled = array_map(static fn (\DOMText $domText): bool => '' !== trim($domText->data), $textNodes);
            $remaining = \count(array_filter($filled));
            $preceding = 0;

            foreach ($textNodes as $index => $textNode) {
                $remaining -= (int) $filled[$index];
                $positions[spl_object_id($textNode)] = [$textNode, $preceding > 0 && $remaining > 0];
                $preceding += (int) $filled[$index];
            }
        }

        return $positions;
    }

    /**
     * @param \DOMDocument $domDocument the document to search
     * @param \DOMNode     $context     the node to search from
     * @param string       $expression  the XPath expression selecting text nodes
     *
     * @return list<\DOMText> the text nodes, including CDATA sections
     */
    private function textNodes(\DOMDocument $domDocument, \DOMNode $context, string $expression): array
    {
        $domNodeList = (new \DOMXPath($domDocument))->query($expression, $context, false);
        $textNodes = [];

        foreach (false === $domNodeList ? [] : $domNodeList as $domNode) {
            if ($domNode instanceof \DOMText) {
                $textNodes[] = $domNode;
            }
        }

        return $textNodes;
    }

    /**
     * @param \DOMText $domText           a text node holding only whitespace
     * @param bool     $followsWhitespace true if a preceding sibling is text starting with whitespace
     */
    private function isIgnorableBlank(\DOMText $domText, bool $followsWhitespace): bool
    {
        $space = $this->xmlSpace($domText);

        if (self::SPACE_PRESERVE === $space || !$domText->previousSibling instanceof \DOMNode) {
            return self::SPACE_PRESERVE !== $space && $domText->nextSibling instanceof \DOMNode;
        }

        return !$domText->parentNode?->firstChild instanceof \DOMText && ('' !== $space || !$followsWhitespace);
    }

    /**
     * @param \DOMText                          $domText     a text node holding only whitespace
     * @param array<int, array{\DOMText, bool}> $textContent the text nodes in text content, by object id, with whether
     *                                                       text precedes and follows them
     */
    private function rendersAsSpace(\DOMText $domText, array $textContent): bool
    {
        $position = $textContent[spl_object_id($domText)] ?? null;

        return null !== $position && ($position[1] || $this->preservesSpace($domText));
    }

    /**
     * @param \DOMText $domText the text node to check
     */
    private function preservesSpace(\DOMText $domText): bool
    {
        return self::SPACE_PRESERVE === $this->xmlSpace($domText);
    }

    /**
     * @param \DOMText $domText the text node to check
     *
     * @return string the `xml:space` value in scope, or an empty string if no ancestor sets it
     */
    private function xmlSpace(\DOMText $domText): string
    {
        for ($node = $domText->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
            if ($node->hasAttributeNS(NamespaceUri::Xml->value, self::SPACE_ATTRIBUTE)) {
                return $node->getAttributeNS(NamespaceUri::Xml->value, self::SPACE_ATTRIBUTE);
            }
        }

        return '';
    }

    /**
     * @param \DOMText $domText a text node in text content holding only whitespace
     *
     * @return string the whitespace as spaces
     */
    private function collapseTextWhitespace(\DOMText $domText): string
    {
        return $this->preservesSpace($domText) ? strtr($domText->data, "\t\n\r", '   ') : ' ';
    }
}
