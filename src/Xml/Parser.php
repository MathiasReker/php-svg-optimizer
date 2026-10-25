<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Xml;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class Parser
{
    use EntityReferenceTrait;
    use TextWhitespaceTrait;

    private const string XML_VERSION = '1.0';

    private const int LOAD_FLAGS = \LIBXML_NONET;

    private const string ATTRIBUTE_LIST_DECLARATION = '<!ATTLIST';

    private const string ASCII_UTF16_DECLARATION_REGEX = '/^((?:\xEF\xBB\xBF)?<\?xml\s[^>]*?\bencoding\s*=\s*)(["\'])utf-16(?:le|be)?\2/i';

    private const string UTF8_DECLARATION = '$1$2UTF-8$2';

    private const string LOAD_FAILURE = 'Failed to load DOMDocument.';

    private const string LOAD_FAILURE_DETAIL = 'Failed to load DOMDocument: %s on line %d, column %d.';

    private const string ENTITY_EXPANSION_FAILURE = 'Entity expansion exceeds the allowed size.';

    private const string LIBXML_ENTITY_LIMIT_REGEX = '/\bentity (?:reference loop|amplification)/i';

    private const int MAX_ENTITY_EXPANSION_FLOOR = 524_288;

    private const int MAX_ENTITY_AMPLIFICATION = 5;

    /**
     * @param \DOMDocument $domDocument the document to copy
     *
     * @return \DOMDocument an independent copy of the document, parsed again from its complete serialization, which
     *                      keeps every node, unlike a clone, which may lose the prefix of `xml:` attributes
     *
     * @throws ParseException If the serialization cannot be parsed again
     */
    public function copy(\DOMDocument $domDocument): \DOMDocument
    {
        return $this->load($this->serializeDocument($domDocument), self::LOAD_FLAGS);
    }

    /**
     * @param \DOMDocument $domDocument the document to serialize
     *
     * @return non-empty-string the complete document as libxml writes it
     *
     * @throws ParseException If the document cannot be serialized
     */
    private function serializeDocument(\DOMDocument $domDocument): string
    {
        $xml = $domDocument->saveXML();

        return false === $xml || '' === $xml ? throw new ParseException(self::LOAD_FAILURE) : $xml;
    }

    /**
     * @param string $content The XML content as a string
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws ParseException If the XML content cannot be loaded
     */
    public function parse(string $content): \DOMDocument
    {
        $content = (string) preg_replace(self::ASCII_UTF16_DECLARATION_REGEX, self::UTF8_DECLARATION, $content);

        if ('' === $content) {
            throw new ParseException('XML content cannot be empty.');
        }

        $domDocument = $this->load($content, self::LOAD_FLAGS);
        $this->assertBoundedEntityExpansion($domDocument);
        $this->expandEntityReferences($domDocument);

        if ($this->declaresAttributeDefaults($domDocument)) {
            $domDocument = $this->load($this->serializeDocument($domDocument), self::LOAD_FLAGS | \LIBXML_DTDATTR);
        }

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
     * @throws ParseException If expanding the entity references would exceed the allowed size
     */
    private function assertBoundedEntityExpansion(\DOMDocument $domDocument): void
    {
        $entities = $this->internalGeneralEntities($domDocument);

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
                throw new ParseException(self::ENTITY_EXPANSION_FAILURE);
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
     * @throws ParseException If the resolved markup fails to load
     */
    private function resolveNamespaceEntityReferences(\DOMDocument $domDocument): \DOMDocument
    {
        $entities = $this->internalGeneralEntities($domDocument);

        if ([] === $entities) {
            return $domDocument;
        }

        $markup = (string) $domDocument->saveXML($domDocument->documentElement);
        $resolved = $this->resolveNamespaceEntities($markup, $entities);

        return '' === $resolved || $resolved === $markup ? $domDocument : $this->load($resolved, self::LOAD_FLAGS);
    }

    /**
     * @param non-empty-string $content the XML to parse
     * @param int              $flags   the libxml options
     *
     * @throws ParseException If the \DOMDocument fails to load
     */
    private function load(string $content, int $flags): \DOMDocument
    {
        $domDocument = $this->createDomDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $domDocument->loadXML($content, $flags);
            $failure = $this->loadFailure();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded) {
            throw new ParseException($failure);
        }

        $domDocument->encoding ??= self::XML_ENCODING;

        return $domDocument;
    }

    /**
     * @return string the message for a failed load, which reports an exceeded entity expansion limit when libxml
     *                stopped expanding entities itself
     */
    private function loadFailure(): string
    {
        $libXMLErrors = libxml_get_errors();

        foreach ($libXMLErrors as $libXMLError) {
            if (1 === preg_match(self::LIBXML_ENTITY_LIMIT_REGEX, $libXMLError->message)) {
                return self::ENTITY_EXPANSION_FAILURE;
            }
        }

        if ([] === $libXMLErrors) {
            return self::LOAD_FAILURE;
        }

        return \sprintf(self::LOAD_FAILURE_DETAIL, trim($libXMLErrors[0]->message), $libXMLErrors[0]->line, $libXMLErrors[0]->column);
    }

    private function createDomDocument(): \DOMDocument
    {
        $domDocument = new \DOMDocument(self::XML_VERSION, self::XML_ENCODING);
        $domDocument->formatOutput = false;
        $domDocument->preserveWhiteSpace = true;

        $domDocument->resolveExternals = false;
        $domDocument->substituteEntities = false;
        $domDocument->validateOnParse = false;
        $domDocument->recover = false;

        return $domDocument;
    }
}
