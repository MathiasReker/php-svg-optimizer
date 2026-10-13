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

    /**
     * @param \DOMDocument $domDocument The \DOMDocument instance to be saved
     *
     * @return string Returns the XML content as a string
     *
     * @throws XmlProcessingException If the XML content cannot be saved
     */
    public function saveToString(\DOMDocument $domDocument): string
    {
        [$blankTextNodes, $whitespace] = $this->collapseBlankTextNodes($domDocument);
        $saveXML = $domDocument->saveXML($domDocument->documentElement);

        foreach ($blankTextNodes as $index => $domText) {
            $domText->data = $whitespace[$index];
        }

        if (false === $saveXML) {
            throw new XmlProcessingException('Failed to save XML content.');
        }

        return $this->saveStylesheetInstructions($domDocument) . $saveXML;
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

        return $this->loadDomDocument(static fn (\DOMDocument $domDocument): bool => $domDocument->load($filePath, self::LOAD_FLAGS));
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
        if ('' === $content) {
            throw new XmlProcessingException('XML content cannot be empty.');
        }

        return $this->loadDomDocument(
            static fn (\DOMDocument $domDocument): bool => $domDocument->loadXML(
                $content,
                self::LOAD_FLAGS
            )
        );
    }

    /**
     * @param callable $loader A callback that loads the \DOMDocument (file or string)
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws XmlProcessingException If the \DOMDocument fails to load
     */
    private function loadDomDocument(callable $loader): \DOMDocument
    {
        $domDocument = $this->parse($loader);
        $this->expandEntityReferences($domDocument);
        $domDocument = $this->resolveNamespaceEntityReferences($domDocument);
        $this->removeBlankTextNodes($domDocument);
        $this->collapseTextContentWhitespace($domDocument);

        return $domDocument;
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
        $loaded = $loader($domDocument);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw new XmlProcessingException('Failed to load DOMDocument.');
        }

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
