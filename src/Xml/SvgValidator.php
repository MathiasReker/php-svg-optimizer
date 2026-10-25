<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Xml;

use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class SvgValidator
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * @param string $content The SVG content to be validated
     *
     * @return bool True if the content is a well-formed XML document with an SVG root element, false otherwise
     */
    public function isValid(string $content): bool
    {
        try {
            $domDocument = $this->parser->parse($content);
        } catch (ParseException) {
            return false;
        }

        return $this->isValidDocument($domDocument);
    }

    /**
     * @param \DOMDocument $domDocument The loaded document to be validated
     *
     * @return bool True if the document element is an svg element in the SVG namespace or in no namespace, false otherwise
     */
    public function isValidDocument(\DOMDocument $domDocument): bool
    {
        $root = $domDocument->documentElement;

        return $root instanceof \DOMElement
            && Element::Svg->value === ($root->localName ?? $root->tagName)
            && \in_array($root->namespaceURI, [null, NamespaceUri::Svg->value], true);
    }
}
