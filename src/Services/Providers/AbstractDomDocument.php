<?php
/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Providers;

use DOMDocument;

abstract class AbstractDomDocument
{
    /**
     * Saves the current DOMDocument content as an XML string.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance to be saved.
     *
     * @return string|false Returns the XML content as a string or null on failure.
     */
    public function saveToString(\DOMDocument $domDocument): false|string
    {
        return $domDocument->saveXML();
    }

    /**
     * Loads an XML file into a DOMDocument, suppressing errors.
     *
     * @param string $filePath The path to the XML file.
     *
     * @return \DOMDocument|null Returns the loaded DOMDocument or null on failure.
     */
    public function loadFromFile(string $filePath): ?\DOMDocument
    {
        return $this->loadDomDocument(static fn (\DOMDocument $domDocument): bool => $domDocument->load($filePath));
    }

    /**
     * Common method for loading a DOMDocument with error handling.
     *
     * @param callable $loader A callback that loads the DOMDocument (file or string).
     *
     * @return \DOMDocument|null Returns the loaded DOMDocument or null on failure.
     */
    private function loadDomDocument(callable $loader): ?\DOMDocument
    {
        $domDocument = $this->createDomDocument();
        libxml_use_internal_errors(true);

        if (!$loader($domDocument)) {
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            return null;
        }

        libxml_use_internal_errors(false);

        return $domDocument;
    }

    /**
     * Creates and returns a new DOMDocument instance with default settings.
     */
    private function createDomDocument(): \DOMDocument
    {
        $domDocument = new \DOMDocument();
        $domDocument->formatOutput = false;
        $domDocument->preserveWhiteSpace = false;

        return $domDocument;
    }

    /**
     * Loads XML from a string into a DOMDocument, suppressing errors.
     *
     * @param string $xmlContent The XML content as a string.
     *
     * @return \DOMDocument|null Returns the loaded DOMDocument or null on failure.
     */
    public function loadFromString(string $xmlContent): ?\DOMDocument
    {
        return $this->loadDomDocument(static fn (\DOMDocument $domDocument): bool => $domDocument->loadXML($xmlContent));
    }
}
