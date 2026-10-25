<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Processor;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\TextWhitespaceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Support\SvgDefaults;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class DomDocumentWrapper
{
    use EntityReferenceTrait;
    use TextWhitespaceTrait;

    private const int LOAD_FLAGS = \LIBXML_NONET;

    private const string STYLESHEET_INSTRUCTION = 'xml-stylesheet';

    private const string ATTRIBUTE_LIST_DECLARATION = '<!ATTLIST';

    private const string ASCII_UTF16_DECLARATION_REGEX = '/^((?:\xEF\xBB\xBF)?<\?xml\s[^>]*?\bencoding\s*=\s*)(["\'])utf-16(?:le|be)?\2/i';

    private const string UTF8_DECLARATION = '$1$2UTF-8$2';

    private const string LOAD_FAILURE = 'Failed to load DOMDocument.';

    private const string ENTITY_EXPANSION_FAILURE = 'Entity expansion exceeds the allowed size.';

    private const int MAX_ENTITY_EXPANSION_FLOOR = 1_048_576;

    private const int MAX_ENTITY_AMPLIFICATION = 10;

    private const string CHILDLESS_XHTML_ELEMENT_QUERY = '//*[namespace-uri() = "http://www.w3.org/1999/xhtml"][not(node())]';

    private const string HTML_VOID_ELEMENT_REGEX = '/^(?:area|base|br|col|embed|hr|img|input|link|meta|source|track|wbr)$/i';

    /**
     * @param \DOMDocument $domDocument The \DOMDocument instance to be saved
     *
     * @return string Returns the XML content as a string
     */
    public function saveToString(\DOMDocument $domDocument): string
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

        return $this->saveStylesheetInstructions($domDocument) . $saveXML;
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

        $domDocument->encoding = SvgDefaults::XML_ENCODING;

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
     * @return string the stylesheet instructions that precede the document element
     */
    private function saveStylesheetInstructions(\DOMDocument $domDocument): string
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

    /**
     * @param string $filePath The path to the XML file
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws XmlProcessingException If the XML file cannot be loaded
     */
    public function loadFromFile(string $filePath): \DOMDocument
    {
        if ('' === $filePath) {
            throw new XmlProcessingException('File path cannot be empty.');
        }

        return $this->loadDomDocument(static fn (\DOMDocument $domDocument, int $flags = self::LOAD_FLAGS): bool => $domDocument->load($filePath, $flags));
    }

    /**
     * @param string $content The XML content as a string
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws XmlProcessingException If the XML content cannot be loaded
     */
    public function loadFromString(string $content): \DOMDocument
    {
        $content = (string) preg_replace(self::ASCII_UTF16_DECLARATION_REGEX, self::UTF8_DECLARATION, $content);

        if ('' === $content) {
            throw new XmlProcessingException('XML content cannot be empty.');
        }

        return $this->loadDomDocument(
            static fn (\DOMDocument $domDocument, int $flags = self::LOAD_FLAGS): bool => $domDocument->loadXML($content, $flags)
        );
    }

    /**
     * @param callable(\DOMDocument, int=): bool $loader A callback that loads the \DOMDocument (file or string) with
     *                                                   the given libxml options
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws XmlProcessingException If the \DOMDocument fails to load
     */
    private function loadDomDocument(callable $loader): \DOMDocument
    {
        $domDocument = $this->parse($loader);

        if ($this->declaresAttributeDefaults($domDocument)) {
            $domDocument = $this->parse(static fn (\DOMDocument $domDocument): bool => $loader($domDocument, self::LOAD_FLAGS | \LIBXML_DTDATTR));
        }

        $this->assertBoundedEntityExpansion($domDocument);
        $this->expandEntityReferences($domDocument);
        $domDocument = $this->resolveNamespaceEntityReferences($domDocument);
        $this->removeBlankTextNodes($domDocument);
        $this->collapseTextContentWhitespace($domDocument);

        return $domDocument;
    }

    /**
     * @param \DOMDocument $domDocument the parsed document
     *
     * @return bool true if the internal subset declares attribute lists, whose default values apply, and the document
     *              has no external subset
     */
    private function declaresAttributeDefaults(\DOMDocument $domDocument): bool
    {
        $doctype = $domDocument->doctype;

        return $doctype instanceof \DOMDocumentType
            && '' === $doctype->systemId
            && '' === $doctype->publicId
            && str_contains((string) $doctype->internalSubset, self::ATTRIBUTE_LIST_DECLARATION);
    }

    /**
     * @param \DOMDocument $domDocument the parsed document, before its entity references are expanded
     *
     * @throws XmlProcessingException If expanding the entity references would exceed the allowed size
     */
    private function assertBoundedEntityExpansion(\DOMDocument $domDocument): void
    {
        $entities = $this->internalEntities($domDocument);

        if ([] === $entities) {
            return;
        }

        $limit = max(self::MAX_ENTITY_EXPANSION_FLOOR, self::MAX_ENTITY_AMPLIFICATION * mb_strlen((string) $domDocument->saveXML(), '8bit'));
        $lengths = $this->expandedEntityLengths($entities, $limit);
        preg_match_all(self::ENTITY_REFERENCE_REGEX, (string) $domDocument->saveXML($domDocument->documentElement), $matches);
        $expanded = 0;

        foreach ($matches[1] as $name) {
            $expanded += $lengths[$name] ?? 0;

            if ($expanded > $limit) {
                throw new XmlProcessingException(self::ENTITY_EXPANSION_FAILURE);
            }
        }
    }

    /**
     * @param array<string, string> $entities the replacement text of each internal entity, by name
     * @param int                   $limit    the largest length worth counting
     *
     * @return array<string, int> the length of each entity once its nested references are resolved, capped above the limit
     */
    private function expandedEntityLengths(array $entities, int $limit): array
    {
        $ownLengths = [];
        $references = [];

        foreach ($entities as $name => $literal) {
            $text = $this->decodeCharacterReferences($literal);
            preg_match_all(self::ENTITY_REFERENCE_REGEX, $text, $matches);
            $nested = array_filter($matches[1], fn (string $reference): bool => !$this->isPredefinedEntity($reference));
            $ownLengths[$name] = mb_strlen($text, '8bit') - array_sum(array_map(static fn (string $reference): int => mb_strlen($reference, '8bit') + 2, $nested));
            $references[$name] = $nested;
        }

        $lengths = $ownLengths;

        for ($depth = 0; $depth < self::MAX_ENTITY_DEPTH; ++$depth) {
            $lengths = $this->resolveEntityLengths($ownLengths, $references, $lengths, $limit);
        }

        return $lengths;
    }

    /**
     * @param array<string, int>                $ownLengths the length of each entity without its nested references
     * @param array<string, array<int, string>> $references the names of the entities each entity references
     * @param array<string, int>                $lengths    the length of each entity resolved one level less deep
     * @param int                               $limit      the largest length worth counting
     *
     * @return array<string, int> the length of each entity resolved one level deeper, capped above the limit
     */
    private function resolveEntityLengths(array $ownLengths, array $references, array $lengths, int $limit): array
    {
        $resolved = [];

        foreach ($ownLengths as $name => $ownLength) {
            $resolved[$name] = array_reduce(
                $references[$name],
                static fn (int $length, string $reference): int => min($limit + 1, $length + ($lengths[$reference] ?? 0)),
                $ownLength,
            );
        }

        return $resolved;
    }

    /**
     * @param \DOMDocument $domDocument the loaded document
     *
     * @return \DOMDocument the document, or a document parsed again without entity references
     *
     * @throws XmlProcessingException If the resolved markup fails to load
     */
    private function resolveNamespaceEntityReferences(\DOMDocument $domDocument): \DOMDocument
    {
        $entities = $this->internalEntities($domDocument);

        if ([] === $entities) {
            return $domDocument;
        }

        $markup = (string) $domDocument->saveXML($domDocument->documentElement);
        $resolved = $this->resolveNamespaceEntities($markup, $entities);

        return '' === $resolved || $resolved === $markup
            ? $domDocument
            : $this->parse(static fn (\DOMDocument $domDocument): bool => $domDocument->loadXML($resolved, self::LOAD_FLAGS));
    }

    /**
     * @param callable $loader A callback that loads the \DOMDocument (file or string)
     *
     * @throws XmlProcessingException If the \DOMDocument fails to load
     */
    private function parse(callable $loader): \DOMDocument
    {
        $domDocument = $this->createDomDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $loader($domDocument);
        } catch (\ValueError $valueError) {
            throw new XmlProcessingException(self::LOAD_FAILURE, 0, $valueError);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded) {
            throw new XmlProcessingException(self::LOAD_FAILURE);
        }

        $domDocument->encoding ??= SvgDefaults::XML_ENCODING;

        return $domDocument;
    }

    private function createDomDocument(): \DOMDocument
    {
        $domDocument = new \DOMDocument(SvgDefaults::XML_VERSION, SvgDefaults::XML_ENCODING);
        $domDocument->formatOutput = false;
        $domDocument->preserveWhiteSpace = true;

        $domDocument->resolveExternals = false;
        $domDocument->substituteEntities = false;
        $domDocument->validateOnParse = false;
        $domDocument->recover = false;

        return $domDocument;
    }
}
