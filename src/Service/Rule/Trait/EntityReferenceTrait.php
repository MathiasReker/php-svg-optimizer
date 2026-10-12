<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * Resolves references to entities declared in the internal DTD subset. Documents are loaded without substituting
 * entities, and only the document element is written, so a reference left in the output would point to a declaration
 * that is no longer there.
 *
 * @no-named-arguments
 */
trait EntityReferenceTrait
{
    /**
     * Internal general entities; parameter entities and external entities (SYSTEM or PUBLIC) do not match.
     */
    private const string INTERNAL_ENTITY_REGEX = '/<!ENTITY\s+([^\s%"\'>]+)\s+(["\'])(.*?)\2\s*>/s';

    private const string ENTITY_REFERENCE_REGEX = '/&([^\s&;#]+);/';

    private const string CHARACTER_REFERENCE_REGEX = '/&#(?:x([0-9a-f]+)|(\d+));/i';

    private const string NAMESPACE_DECLARATION_REGEX = '/(\sxmlns(?::[^\s=]+)?=")([^"]*)(")/';

    /**
     * Bounds the resolution of entities that reference other entities, so a cycle cannot loop forever.
     */
    private const int MAX_ENTITY_DEPTH = 8;

    /**
     * Replaces entity references with their expansion. An entity whose replacement text contains markup is expanded
     * to elements in element content; otherwise, and in attributes, it is expanded to text. External entities, which
     * are never loaded, expand to nothing.
     *
     * @param \DOMNode $domNode the node whose descendants and attributes to expand
     */
    private function expandEntityReferences(\DOMNode $domNode): void
    {
        $domDocument = $domNode instanceof \DOMDocument ? $domNode : $domNode->ownerDocument;

        if ($domDocument instanceof \DOMDocument) {
            $this->expandEntityReferencesIn($domNode, $this->internalEntities($domDocument));
        }
    }

    /**
     * @param \DOMNode              $domNode  the node whose descendants and attributes to expand
     * @param array<string, string> $entities the replacement text of each internal entity, by name
     */
    private function expandEntityReferencesIn(\DOMNode $domNode, array $entities): void
    {
        $children = iterator_to_array($domNode->childNodes, false);

        if ($domNode instanceof \DOMElement) {
            array_push($children, ...iterator_to_array($domNode->attributes, false));
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMEntityReference) {
                $this->replaceEntityReference($domNode, $child, $entities);
            } elseif (!$child instanceof \DOMDocumentType) {
                // The entity declarations in the DOCTYPE are read-only and not written to the output.
                $this->expandEntityReferencesIn($child, $entities);
            }
        }
    }

    /**
     * @param \DOMNode              $domNode            the node containing the reference
     * @param \DOMEntityReference   $domEntityReference the reference to replace
     * @param array<string, string> $entities           the replacement text of each internal entity, by name
     */
    private function replaceEntityReference(\DOMNode $domNode, \DOMEntityReference $domEntityReference, array $entities): void
    {
        $markup = \array_key_exists($domEntityReference->nodeName, $entities)
            ? $this->resolveEntityText($this->decodeCharacterReferences($entities[$domEntityReference->nodeName]), $entities, 0)
            : '';
        $nodes = $domNode instanceof \DOMElement && str_contains($markup, '<')
            ? $this->parseEntityMarkup($domNode, $markup)
            : [];

        if ([] === $nodes) {
            $domNode->replaceChild(new \DOMText($domEntityReference->textContent), $domEntityReference);

            return;
        }

        foreach ($nodes as $node) {
            $domNode->insertBefore($node, $domEntityReference);
        }

        $domNode->removeChild($domEntityReference);
    }

    /**
     * The parser reads the markup of an entity without the namespaces declared around the reference, so it is parsed
     * again with those namespaces declared.
     *
     * @param \DOMElement $domElement the element the reference appears in
     * @param string      $markup     the replacement text of the entity
     *
     * @return list<\DOMNode> the parsed nodes, imported into the context document, or an empty list if the markup is
     *                        not well-formed
     */
    private function parseEntityMarkup(\DOMElement $domElement, string $markup): array
    {
        $document = $domElement->ownerDocument;

        if (!$document instanceof \DOMDocument) {
            return [];
        }

        $domDocument = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $domDocument->loadXML(
            '<fragment' . $this->namespaceDeclarations($document, $domElement) . '>' . $markup . '</fragment>',
            \LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        // A document that fails to load keeps no document element.
        if (!$domDocument->documentElement instanceof \DOMElement) {
            return [];
        }

        $nodes = [];

        foreach ($domDocument->documentElement->childNodes as $node) {
            $nodes[] = $document->importNode($node, true);
        }

        return $nodes;
    }

    /**
     * @param \DOMDocument $domDocument the document the element belongs to
     * @param \DOMElement  $domElement  the element whose namespaces in scope to declare
     *
     * @return string the namespace declarations in scope, as attribute markup with resolved URIs
     */
    private function namespaceDeclarations(\DOMDocument $domDocument, \DOMElement $domElement): string
    {
        $namespaces = (new \DOMXPath($domDocument))->query('namespace::*', $domElement);
        $declarations = '';

        foreach (false === $namespaces ? [] : $namespaces as $namespace) {
            if ('xmlns:xml' !== $namespace->nodeName) {
                $value = htmlspecialchars((string) $namespace->nodeValue, \ENT_QUOTES | \ENT_XML1);
                $declarations .= ' ' . $namespace->nodeName . '="' . $value . '"';
            }
        }

        return $declarations;
    }

    /**
     * Namespace declarations keep entity references unexpanded, so they are resolved in the serialized markup.
     *
     * @param string                $xml      the serialized document element
     * @param array<string, string> $entities the replacement text of each internal entity, by name
     *
     * @return string the markup with entity references in namespace declarations resolved
     */
    private function resolveNamespaceEntities(string $xml, array $entities): string
    {
        return preg_replace_callback(
            self::NAMESPACE_DECLARATION_REGEX,
            fn (array $match): string => $match[1] . htmlspecialchars(
                html_entity_decode($this->resolveEntityText($match[2], $entities, 0), \ENT_QUOTES | \ENT_XML1, 'UTF-8'),
                \ENT_QUOTES | \ENT_XML1,
            ) . $match[3],
            $xml,
        ) ?? $xml;
    }

    /**
     * @param \DOMDocument $domDocument the document to read
     *
     * @return array<string, string> the replacement text of each internal general entity, by name
     */
    private function internalEntities(\DOMDocument $domDocument): array
    {
        $subset = (string) $domDocument->doctype?->internalSubset;
        preg_match_all(self::INTERNAL_ENTITY_REGEX, $subset, $matches, \PREG_SET_ORDER);

        $entities = [];

        foreach ($matches as $match) {
            $entities[$match[1]] = $match[3];
        }

        return $entities;
    }

    /**
     * @param string                $text     text that may contain entity and character references
     * @param array<string, string> $entities the replacement text of each internal entity
     * @param int                   $depth    the number of nested entities resolved so far
     *
     * @return string the text with every entity reference resolved, keeping character references and predefined
     *                entities; unknown entities resolve to nothing
     */
    private function resolveEntityText(string $text, array $entities, int $depth): string
    {
        return preg_replace_callback(
            self::ENTITY_REFERENCE_REGEX,
            fn (array $match): string => $depth < self::MAX_ENTITY_DEPTH && \array_key_exists($match[1], $entities)
                ? $this->resolveEntityText($this->decodeCharacterReferences($entities[$match[1]]), $entities, $depth + 1)
                : ($this->isPredefinedEntity($match[1]) ? $match[0] : ''),
            $text,
        ) ?? $text;
    }

    /**
     * Character references in an entity value are expanded when the entity is declared, before its replacement text is
     * parsed where the entity is referenced.
     *
     * @param string $literal the entity value as written in the declaration
     *
     * @return string the replacement text of the entity
     */
    private function decodeCharacterReferences(string $literal): string
    {
        return preg_replace_callback(
            self::CHARACTER_REFERENCE_REGEX,
            static fn (array $match): string => mb_chr(
                (int) ('' === $match[1] ? $match[2] : hexdec($match[1])),
                'UTF-8',
            ),
            $literal,
        ) ?? $literal;
    }

    /**
     * @param string $name the entity name
     */
    private function isPredefinedEntity(string $name): bool
    {
        return \in_array($name, ['amp', 'lt', 'gt', 'quot', 'apos'], true);
    }
}
