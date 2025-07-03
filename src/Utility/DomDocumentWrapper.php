<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Utility;

use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;

/**
 * @no-named-arguments
 */
final readonly class DomDocumentWrapper
{
    /**
     * Default XML version used when saving the \DOMDocument.
     */
    private const string DEFAULT_XML_VERSION = '1.0';

    /**
     * Default encoding used when saving the \DOMDocument.
     */
    private const string DEFAULT_ENCODING = 'UTF-8';

    /**
     * Regex pattern to match whitespace between XML tags.
     *
     * This pattern matches any whitespace (spaces, tabs, newlines) that appears
     * between closing and opening tags, allowing for removal of unnecessary
     * whitespace in the final XML output.
     *
     * @see https://regex101.com/r/lyKDnR/1
     */
    private const string WHITESPACE_BETWEEN_TAGS_REGEX = '/>\s+</';

    /**
     * Saves the current \DOMDocument content as an XML string.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance to be saved
     *
     * @return string Returns the XML content as a string
     *
     * @throws XmlProcessingException If the XML content cannot be saved
     */
    public function saveToString(\DOMDocument $domDocument): string
    {
        $saveXML = $domDocument->saveXML();

        if (false === $saveXML) {
            throw new XmlProcessingException('Failed to save XML content.');
        }

        $clean = $this->removeLineFeedsAndTabs($saveXML);

        return $this->removeWhitespaceBetweenTags($clean);
    }

    /**
     * Removes line feeds (newlines, carriage returns) and tabs from the given content.
     *
     * This method removes all newline characters, including `\n` (LF), `\r` (CR), and tabs (`\t`),
     * while preserving spaces.
     *
     * @param string $content The content from which line feeds and tabs will be removed
     *
     * @return string The cleaned content with line feeds and tabs removed
     */
    private function removeLineFeedsAndTabs(string $content): string
    {
        return str_replace(["\r", "\n", "\t"], '', $content);
    }

    /**
     * Removes unnecessary whitespace (spaces, tabs, newlines) between XML tags.
     *
     * Converts patterns like `>   <` into `><` to compact the XML output.
     *
     * @param string $content The XML content with potential inter-tag whitespace
     *
     * @return string The XML content with collapsed inter-tag whitespace
     */
    private function removeWhitespaceBetweenTags(string $content): string
    {
        return (string) preg_replace(self::WHITESPACE_BETWEEN_TAGS_REGEX, '><', $content);
    }

    /**
     * Loads an XML file into a \DOMDocument, suppressing errors.
     *
     * @param string $filePath The path to the XML file
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws XmlProcessingException If the XML file cannot be loaded
     */
    public function loadFromFile(string $filePath): \DOMDocument
    {
        return $this->loadDomDocument(static fn (\DOMDocument $domDocument): bool => $domDocument->load($filePath));
    }

    /**
     * Common method for loading a \DOMDocument with error handling.
     *
     * @param callable $loader A callback that loads the \DOMDocument (file or string)
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws XmlProcessingException If the \DOMDocument fails to load
     */
    private function loadDomDocument(callable $loader): \DOMDocument
    {
        $domDocument = $this->createDomDocument();
        libxml_use_internal_errors(true);

        if (!$loader($domDocument)) {
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            throw new XmlProcessingException('Failed to load DOMDocument.');
        }

        libxml_use_internal_errors(false);

        return $domDocument;
    }

    /**
     * Creates and returns a new \DOMDocument instance with default settings.
     */
    private function createDomDocument(): \DOMDocument
    {
        $domDocument = new \DOMDocument(self::DEFAULT_XML_VERSION, self::DEFAULT_ENCODING);
        $domDocument->formatOutput = false;
        $domDocument->preserveWhiteSpace = false;

        // Security: Prevent XXE
        $domDocument->resolveExternals = false;
        $domDocument->substituteEntities = false;

        return $domDocument;
    }

    /**
     * Loads XML from a string into a \DOMDocument.
     *
     * @param string $xmlContent The XML content as a string
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws XmlProcessingException If the XML content cannot be loaded
     */
    public function loadFromString(string $xmlContent): \DOMDocument
    {
        return $this->loadDomDocument(static fn (\DOMDocument $domDocument): bool => $domDocument->loadXML($xmlContent, \LIBXML_NONET | \LIBXML_NOCDATA | \LIBXML_NOEMPTYTAG));
    }
}
