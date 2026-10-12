<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Processor\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * Whitespace between elements is insignificant, except in text content, where whitespace between characters renders
 * as a space. Documents are parsed preserving whitespace, so that it survives in text content; elsewhere it is removed
 * where the parser would remove it when not preserving whitespace.
 *
 * @no-named-arguments
 */
trait BlankTextNodeTrait
{
    private const string BLANK_TEXT_QUERY = "//text()[normalize-space() = ''][string-length() > 0]";

    private const string TEXT_QUERY = './/text()';

    private const string LEADING_WHITESPACE_REGEX = '/^\s/';

    private const string SPACE_ATTRIBUTE = 'space';

    private const string SPACE_PRESERVE = 'preserve';

    /**
     * Collapses whitespace in text content and removes it elsewhere. CDATA sections are kept apart while parsing, so
     * whitespace around them is removed as the parser would, and are then merged into the text.
     *
     * @param \DOMDocument $domDocument the parsed document
     */
    private function removeBlankTextNodes(\DOMDocument $domDocument): void
    {
        foreach ($this->blankTextNodes($domDocument) as $domText) {
            if ($this->rendersAsSpace($domDocument, $domText)) {
                $domText->data = $this->collapseTextWhitespace($domText);
            } elseif ($this->isIgnorableBlank($domText)) {
                $domText->parentNode?->removeChild($domText);
            }
        }

        foreach ($this->textNodes($domDocument, $domDocument, self::TEXT_QUERY) as $domText) {
            if ($domText instanceof \DOMCdataSection) {
                $domText->parentNode?->replaceChild(new \DOMText($domText->data), $domText);
            }
        }

        $domDocument->documentElement?->normalize();
    }

    /**
     * Sets every blank text node to the whitespace it renders as: a space in text content, and nothing elsewhere.
     *
     * @param \DOMDocument $domDocument the document to serialize
     *
     * @return array{list<\DOMText>, list<string>} the blank text nodes and their whitespace, to restore it
     */
    private function collapseBlankTextNodes(\DOMDocument $domDocument): array
    {
        $blankTextNodes = $this->blankTextNodes($domDocument);
        $whitespace = [];

        foreach ($blankTextNodes as $blankTextNode) {
            $whitespace[] = $blankTextNode->data;
            $blankTextNode->data = $this->rendersAsSpace($domDocument, $blankTextNode) ? $this->collapseTextWhitespace($blankTextNode) : '';
        }

        return [$blankTextNodes, $whitespace];
    }

    /**
     * @param \DOMDocument $domDocument the document to search
     *
     * @return list<\DOMText> the text nodes holding only whitespace, without CDATA sections
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
     * @param \DOMNode     $context     the node to search from
     * @param string       $expression  the XPath expression selecting text nodes
     *
     * @return list<\DOMText> the text nodes, including CDATA sections
     */
    private function textNodes(\DOMDocument $domDocument, \DOMNode $context, string $expression): array
    {
        $domNodeList = (new \DOMXPath($domDocument))->query($expression, $context);
        $textNodes = [];

        foreach (false === $domNodeList ? [] : $domNodeList as $domNode) {
            if ($domNode instanceof \DOMText) {
                $textNodes[] = $domNode;
            }
        }

        return $textNodes;
    }

    /**
     * The parser keeps whitespace where `xml:space` is `preserve`, and whitespace that is the only content of an
     * element. As content is then mixed, it also keeps whitespace in an element whose first child is text, and, where
     * `xml:space` is not set, whitespace that follows text starting with whitespace. Nodes are checked in document
     * order, so the siblings before a node are those the parser would have kept.
     *
     * @param \DOMText $domText a text node holding only whitespace
     */
    private function isIgnorableBlank(\DOMText $domText): bool
    {
        $space = $this->xmlSpace($domText);

        if (self::SPACE_PRESERVE === $space || !$domText->previousSibling instanceof \DOMNode) {
            return self::SPACE_PRESERVE !== $space && $domText->nextSibling instanceof \DOMNode;
        }

        return !$domText->parentNode?->firstChild instanceof \DOMText
            && ('' !== $space || !$this->followsTextStartingWithWhitespace($domText));
    }

    /**
     * @param \DOMText $domText a text node holding only whitespace
     */
    private function followsTextStartingWithWhitespace(\DOMText $domText): bool
    {
        for ($node = $domText->previousSibling; $node instanceof \DOMNode; $node = $node->previousSibling) {
            if ($node instanceof \DOMText
                && !$node instanceof \DOMCdataSection
                && 1 === preg_match(self::LEADING_WHITESPACE_REGEX, $node->data)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whitespace in a `<text>` renders as a space where `xml:space` is `preserve`, and elsewhere between two
     * characters of the text; leading and trailing whitespace is removed when rendering.
     *
     * @param \DOMDocument $domDocument the document containing the node
     * @param \DOMText     $domText     a text node holding only whitespace
     */
    private function rendersAsSpace(\DOMDocument $domDocument, \DOMText $domText): bool
    {
        for ($node = $domText->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
            if (SvgTag::Text->value === $node->localName) {
                return $this->preservesSpace($domText) || $this->isBetweenText($domDocument, $domText, $node);
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument the document containing the node
     * @param \DOMText     $domText     a text node holding only whitespace
     * @param \DOMElement  $domElement  the `<text>` element containing the node
     */
    private function isBetweenText(\DOMDocument $domDocument, \DOMText $domText, \DOMElement $domElement): bool
    {
        $characters = '';
        $preceding = '';

        foreach ($this->textNodes($domDocument, $domElement, self::TEXT_QUERY) as $node) {
            if ($node === $domText) {
                $preceding = $characters;
            }

            $characters .= trim($node->data);
        }

        return '' !== $preceding && $characters !== $preceding;
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
            if ($node->hasAttributeNS(SvgNamespace::Xml->value, self::SPACE_ATTRIBUTE)) {
                return $node->getAttributeNS(SvgNamespace::Xml->value, self::SPACE_ATTRIBUTE);
            }
        }

        return '';
    }

    /**
     * Each whitespace character renders as a space where `xml:space` is `preserve`; elsewhere a run renders as one.
     *
     * @param \DOMText $domText a text node in text content holding only whitespace
     *
     * @return string the whitespace as spaces
     */
    private function collapseTextWhitespace(\DOMText $domText): string
    {
        return $this->preservesSpace($domText) ? str_repeat(' ', mb_strlen($domText->data)) : ' ';
    }
}
