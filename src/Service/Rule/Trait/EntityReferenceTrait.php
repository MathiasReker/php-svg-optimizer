<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Processor\Trait\XmlLoaderTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait EntityReferenceTrait
{
    use XmlLoaderTrait;

    private const string INTERNAL_ENTITY_REGEX = '/<!ENTITY\s+([^\s%"\'>]+)\s+(["\'])(.*?)\2\s*>/s';

    private const string ENTITY_REFERENCE_REGEX = '/&([^\s&;#]+);/';

    private const string CHARACTER_REFERENCE_REGEX = '/&#(?:x([0-9a-f]+)|(\d+));/i';

    private const string NAMESPACE_DECLARATION_REGEX = '/(\sxmlns(?::[^\s=]+)?=")([^"]*)(")/';

    private const int MAX_ENTITY_DEPTH = 8;

    /**
     * @param \DOMNode $domNode the node whose descendants and attributes to expand
     */
    private function expandEntityReferences(\DOMNode $domNode): void
    {
        $domDocument = $domNode instanceof \DOMDocument ? $domNode : $domNode->ownerDocument;

        if ($domDocument instanceof \DOMDocument && $domDocument->doctype instanceof \DOMDocumentType) {
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
        $nodes = $domNode instanceof \DOMElement && $domNode->ownerDocument instanceof \DOMDocument && str_contains($markup, '<')
            ? $this->parseEntityMarkup($domNode->ownerDocument, $domNode, $markup)
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
     * @param \DOMElement $domElement the element the reference appears in
     * @param string      $markup     the replacement text of the entity
     *
     * @return list<\DOMNode> the parsed nodes, imported into the context document, or an empty list if the markup is
     *                        not well-formed
     */
    private function parseEntityMarkup(\DOMDocument $document, \DOMElement $domElement, string $markup): array
    {
        $domDocument = new \DOMDocument();
        $this->loadXmlSilently(
            $domDocument,
            '<fragment' . $this->namespaceDeclarations($document, $domElement) . '>' . $markup . '</fragment>',
            \LIBXML_NONET,
        );

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
