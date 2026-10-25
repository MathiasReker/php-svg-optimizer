<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Css;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait StyleSheetTrait
{
    use ElementTrait;

    private const string STRUCTURAL_SELECTOR_REGEX = '/[+~]|:(?:nth-|first-|last-|only-|empty|has\b|\\\)/i';

    private const string IMPORTED_CSS_REGEX = '/@(?:import|\\\)/i';

    private const string XML_STYLESHEET = 'xml-stylesheet';

    private const string VALUE_ATTRIBUTE_SELECTOR_REGEX = '/\[[^\]]*=/';

    private const string ATTRIBUTE_SELECTOR_START = '[';

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $properties the property names as regular expression alternatives
     *
     * @return bool true if the inline style of the element declares one of the properties, also with a vendor prefix
     */
    private function inlineStyleDeclares(\DOMElement $domElement, string $properties): bool
    {
        return 1 === preg_match(
            '/(?<![\w-])(?:-[a-z]+-)?(?:' . $properties . ')\s*:/i',
            $domElement->getAttribute(Attribute::Style->value),
        );
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasStyleSheet(\DOMDocument $domDocument): bool
    {
        return $this->hasStyleSheetInstruction($domDocument)
            || [] !== $this->elementsByLocalName($domDocument, Element::Style->value)
            || [] !== $this->elementsByLocalName($domDocument, Element::Link->value);
    }

    /**
     * @param \DOMDocument $domDocument the document to read
     *
     * @return string the text of every `<style>` element
     */
    private function styleSheetText(\DOMDocument $domDocument): string
    {
        $css = '';

        foreach ($this->elementsByLocalName($domDocument, Element::Style->value) as $style) {
            $css .= $style->textContent;
        }

        return $css;
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if a style sheet may select elements by the value of an attribute, such as `[fill="red"]`
     */
    private function maySelectByAttributeValue(\DOMDocument $domDocument): bool
    {
        return $this->hasExternalStyleSheet($domDocument)
            || 1 === preg_match(self::VALUE_ATTRIBUTE_SELECTOR_REGEX, $this->styleSheetText($domDocument));
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if a style sheet may select elements by an attribute, such as `[transform]`
     */
    private function maySelectByAttribute(\DOMDocument $domDocument): bool
    {
        return $this->hasExternalStyleSheet($domDocument)
            || str_contains($this->styleSheetText($domDocument), self::ATTRIBUTE_SELECTOR_START);
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if the document loads style rules that cannot be read from its `<style>` elements, through an
     *              `xml-stylesheet` instruction, a `<link>` element or an `@import` rule
     */
    private function hasExternalStyleSheet(\DOMDocument $domDocument): bool
    {
        return $this->hasStyleSheetInstruction($domDocument)
            || [] !== $this->elementsByLocalName($domDocument, Element::Link->value)
            || 1 === preg_match(self::IMPORTED_CSS_REGEX, $this->styleSheetText($domDocument));
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasStyleSheetInstruction(\DOMDocument $domDocument): bool
    {
        foreach ($domDocument->childNodes as $childNode) {
            if ($childNode instanceof \DOMProcessingInstruction && self::XML_STYLESHEET === $childNode->target) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasStructureSensitiveStyleSheet(\DOMDocument $domDocument): bool
    {
        if ($this->hasExternalStyleSheet($domDocument)) {
            return true;
        }

        foreach ($this->elementsByLocalName($domDocument, Element::Style->value) as $style) {
            if (1 === preg_match(self::STRUCTURAL_SELECTOR_REGEX, $style->textContent)) {
                return true;
            }
        }

        return false;
    }
}
