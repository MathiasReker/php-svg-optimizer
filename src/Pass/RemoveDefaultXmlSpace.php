<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDefaultXmlSpace implements PassInterface
{
    use StyleSheetTrait;

    private const string SPACE_ATTRIBUTE = 'space';

    private const string DEFAULT_VALUE = 'default';

    private const string WHITE_SPACE_PROPERTY = 'white-space';

    private const string CSS_ESCAPE = '\\';

    private const string XPATH_XML_SPACE_ELEMENTS = '//*[@xml:space]';

    private const string XPATH_NEAREST_XML_SPACE_ANCESTOR = 'ancestor::*[@xml:space][1]';

    private const string XPATH_STYLESHEET_INSTRUCTIONS = '//processing-instruction("xml-stylesheet")';

    private const string XPATH_WHITE_SPACE_STYLING = 'ancestor-or-self::*[@white-space or @style[contains(translate(., "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "white-space")]]';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        if ($this->hasDocumentWideStyling($domDocument, $domXPath)) {
            return;
        }

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_XML_SPACE_ELEMENTS, null, false);

        foreach ($domNodeList as $domElement) {
            if ($this->isRedundantDefault($domXPath, $domElement)) {
                $domElement->removeAttributeNS(NamespaceUri::Xml->value, self::SPACE_ATTRIBUTE);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to check
     * @param \DOMXPath    $domXPath    the XPath object for querying the document
     *
     * @return bool true if a style sheet may set white-space on any element
     */
    private function hasDocumentWideStyling(\DOMDocument $domDocument, \DOMXPath $domXPath): bool
    {
        /** @var \DOMNodeList<\DOMNode> $instructions */
        $instructions = $domXPath->query(self::XPATH_STYLESHEET_INSTRUCTIONS, null, false);

        if ($instructions->length > 0 || $this->hasExternalStyleSheet($domDocument)) {
            return true;
        }

        $css = mb_strtolower($this->styleSheetText($domDocument));

        return str_contains($css, self::WHITE_SPACE_PROPERTY) || str_contains($css, self::CSS_ESCAPE);
    }

    /**
     * @param \DOMXPath   $domXPath   the XPath object for querying the document
     * @param \DOMElement $domElement the element to inspect
     *
     * @return bool true if the element's xml:space="default" has no effect
     */
    private function isRedundantDefault(\DOMXPath $domXPath, \DOMElement $domElement): bool
    {
        if (!$this->hasDefaultValue($domElement)) {
            return false;
        }

        /** @var \DOMNodeList<\DOMElement> $styledElements */
        $styledElements = $domXPath->query(self::XPATH_WHITE_SPACE_STYLING, $domElement, false);

        if ($styledElements->length > 0) {
            return false;
        }

        /** @var \DOMNodeList<\DOMElement> $ancestors */
        $ancestors = $domXPath->query(self::XPATH_NEAREST_XML_SPACE_ANCESTOR, $domElement, false);
        $ancestor = $ancestors->item(0);

        return !$ancestor instanceof \DOMElement || $this->hasDefaultValue($ancestor);
    }

    /**
     * @param \DOMElement $domElement the element to inspect
     *
     * @return bool true if the element's xml:space value is "default"
     */
    private function hasDefaultValue(\DOMElement $domElement): bool
    {
        return self::DEFAULT_VALUE === trim($domElement->getAttributeNS(NamespaceUri::Xml->value, self::SPACE_ATTRIBUTE));
    }
}
