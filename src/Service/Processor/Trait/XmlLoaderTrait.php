<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Processor\Trait;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait XmlLoaderTrait
{
    /**
     * @param \DOMDocument     $domDocument The document to load the XML into
     * @param non-empty-string $source      The XML to load
     * @param int              $options     The libxml options
     *
     * @return bool True if the XML was loaded, false otherwise
     */
    private function loadXmlSilently(\DOMDocument $domDocument, string $source, int $options = 0): bool
    {
        $previous = libxml_use_internal_errors(true);

        try {
            return $domDocument->loadXML($source, $options);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
