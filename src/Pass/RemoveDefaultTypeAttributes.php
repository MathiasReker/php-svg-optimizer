<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDefaultTypeAttributes implements PassInterface
{
    private const string XPATH_TYPED_ELEMENTS = '//*[local-name()="%s" or local-name()="%s"][@%s]';

    private const string ASCII_WHITESPACE = " \t\n\f\r";

    private const string LANGUAGE_ATTRIBUTE = 'language';

    private const array STYLE_TYPES = [
        'text/css',
    ];

    private const array SCRIPT_TYPES = [
        'application/ecmascript',
        'application/javascript',
        'application/x-ecmascript',
        'application/x-javascript',
        'text/ecmascript',
        'text/javascript',
        'text/javascript1.0',
        'text/javascript1.1',
        'text/javascript1.2',
        'text/javascript1.3',
        'text/javascript1.4',
        'text/javascript1.5',
        'text/jscript',
        'text/livescript',
        'text/x-ecmascript',
        'text/x-javascript',
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $query = \sprintf(
            self::XPATH_TYPED_ELEMENTS,
            Element::Style->value,
            Element::Script->value,
            Attribute::Type->value,
        );

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query($query, null, false);

        foreach ($domNodeList as $domElement) {
            if (!$this->isCandidate($domElement)) {
                continue;
            }

            $isStyle = Element::Style->value === $domElement->localName;
            $defaultTypes = $isStyle ? self::STYLE_TYPES : self::SCRIPT_TYPES;
            $type = $domElement->getAttribute(Attribute::Type->value);
            $type = $isStyle ? $type : trim($type, self::ASCII_WHITESPACE);

            if ($this->isDefaultType($type, $defaultTypes)) {
                $domElement->removeAttribute(Attribute::Type->value);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the `<style>` or `<script>` element
     *
     * @return bool true if the element is in the SVG or XHTML namespace and has no `language` attribute, which would
     *              decide the script language without a type
     */
    private function isCandidate(\DOMElement $domElement): bool
    {
        return \in_array($domElement->namespaceURI, [null, NamespaceUri::Svg->value, NamespaceUri::Xhtml->value], true)
            && !$domElement->hasAttribute(self::LANGUAGE_ATTRIBUTE);
    }

    /**
     * @param string       $type         the type attribute value, trimmed for a script
     * @param list<string> $defaultTypes the default types of the element
     *
     * @return bool true if the type is one of the default types
     */
    private function isDefaultType(string $type, array $defaultTypes): bool
    {
        foreach ($defaultTypes as $defaultType) {
            if (0 === strcasecmp($type, $defaultType)) {
                return true;
            }
        }

        return false;
    }
}
