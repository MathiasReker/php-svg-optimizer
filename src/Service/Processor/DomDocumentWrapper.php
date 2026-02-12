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
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Support\SvgDefaults;

/**
 * @no-named-arguments
 */
final readonly class DomDocumentWrapper
{
    private const int LOAD_FLAGS =
        \LIBXML_NONET |
        \LIBXML_NOCDATA |
        \LIBXML_NOEMPTYTAG;

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
        if ($domDocument->documentElement instanceof \DOMElement) {
            $saveXML = $domDocument->saveXML($domDocument->documentElement);
        } else {
            $saveXML = $domDocument->saveXML();
        }

        if (false === $saveXML) {
            throw new XmlProcessingException('Failed to save XML content.');
        }

        $clean = XmlFormatter::removeLineFeedsAndTabs($saveXML);

        return XmlFormatter::removeWhitespaceBetweenTags($clean);
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
        return $this->loadDomDocument(static fn (\DOMDocument $domDocument): bool => $domDocument->load($filePath, self::LOAD_FLAGS));
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
        $domDocument = new \DOMDocument(SvgDefaults::XML_VERSION, SvgDefaults::XML_ENCODING);
        $domDocument->formatOutput = false;
        $domDocument->preserveWhiteSpace = false;

        // Security: Harden XXE
        $domDocument->resolveExternals = false;
        $domDocument->substituteEntities = false;
        $domDocument->validateOnParse = false;
        $domDocument->strictErrorChecking = true;
        $domDocument->recover = false;

        return $domDocument;
    }

    /**
     * Loads XML from a string into a \DOMDocument.
     *
     * @param string $content The XML content as a string
     *
     * @return \DOMDocument Returns the loaded \DOMDocument
     *
     * @throws XmlProcessingException If the XML content cannot be loaded
     */
    public function loadFromString(string $content): \DOMDocument
    {
        return $this->loadDomDocument(
            static fn (\DOMDocument $domDocument): bool => $domDocument->loadXML(
                $content,
                self::LOAD_FLAGS
            )
        );
    }
}
