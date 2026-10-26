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
final readonly class Serializer
{
    use TextWhitespaceTrait;

    private const string XML_ENCODING = 'UTF-8';

    private const string CHILDLESS_XHTML_ELEMENT_QUERY = '//*[namespace-uri() = "' . NamespaceUri::Xhtml->value . '"][not(node())]';

    private const string HTML_VOID_ELEMENT_REGEX = '/^(?:area|base|br|col|embed|hr|img|input|link|meta|source|track|wbr)$/i';

    /**
     * @param \DOMDocument $domDocument the document to serialize
     *
     * @return string the style sheet instructions and the document element as XML
     */
    public function serialize(\DOMDocument $domDocument): string
    {
        [$blankTextNodes, $whitespace] = $this->collapseBlankTextNodes($domDocument);
        $detached = $this->detachEmptyTextNodes($blankTextNodes);
        $placeholders = $this->addEndTagPlaceholders($domDocument);
        $saveXML = $this->saveDocumentElementAsUtf8($domDocument);

        foreach ($placeholders as $placeholder) {
            $placeholder->remove();
        }

        foreach (array_reverse($detached) as [$domText, $parent, $nextSibling]) {
            $parent->insertBefore($domText, $nextSibling);
        }

        foreach ($blankTextNodes as $index => $domText) {
            $domText->data = $whitespace[$index];
        }

        return $this->saveStyleSheetInstructions($domDocument) . $saveXML;
    }

    /**
     * @param \DOMDocument $domDocument the document to serialize
     *
     * @return string the document element, with characters outside ASCII written as UTF-8 if the document has an encoding
     */
    private function saveDocumentElementAsUtf8(\DOMDocument $domDocument): string
    {
        $encoding = $domDocument->encoding;

        if (null === $encoding) {
            return (string) $domDocument->saveXML($domDocument->documentElement);
        }

        $domDocument->encoding = self::XML_ENCODING;

        try {
            return (string) $domDocument->saveXML($domDocument->documentElement);
        } finally {
            $domDocument->encoding = $encoding;
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to serialize
     *
     * @return list<\DOMText> the empty text nodes added to childless XHTML elements that are not void elements, so
     *                        they are written with an end tag
     */
    private function addEndTagPlaceholders(\DOMDocument $domDocument): array
    {
        $placeholders = [];
        $domNodeList = (new \DOMXPath($domDocument))->query(self::CHILDLESS_XHTML_ELEMENT_QUERY, null, false);

        foreach (false === $domNodeList ? [] : $domNodeList as $domNode) {
            if ($domNode instanceof \DOMElement && 1 !== preg_match(self::HTML_VOID_ELEMENT_REGEX, $domNode->localName ?? $domNode->tagName)) {
                $placeholders[] = $domNode->appendChild(new \DOMText(''));
            }
        }

        return $placeholders;
    }

    /**
     * @param \DOMDocument $domDocument the document to read
     *
     * @return string the style sheet instructions that precede the document element
     */
    private function saveStyleSheetInstructions(\DOMDocument $domDocument): string
    {
        $instructions = '';

        foreach ($domDocument->childNodes as $child) {
            if ($child instanceof \DOMProcessingInstruction && self::STYLESHEET_INSTRUCTION === mb_strtolower($child->target)) {
                $instructions .= (string) $domDocument->saveXML($child);
            }
        }

        return $instructions;
    }

    /**
     * @param list<\DOMText> $domTexts the collapsed blank text nodes
     *
     * @return list<array{\DOMText, \DOMNode, \DOMNode|null}> the detached empty text nodes, with their parent and next sibling
     */
    private function detachEmptyTextNodes(array $domTexts): array
    {
        $detached = [];

        foreach ($domTexts as $domText) {
            $parent = $domText->parentNode;

            if ('' === $domText->data && $parent instanceof \DOMNode) {
                $detached[] = [$domText, $parent, $domText->nextSibling];
                $parent->removeChild($domText);
            }
        }

        return $detached;
    }
}
