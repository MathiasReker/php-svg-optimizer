<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait ElementTrait
{
    use SvgElementTrait;

    private const string SIBLING_SENSITIVE_CSS_REGEX = '/[+~]|:(?:nth-|first-|last-|only-|empty|has\b|\\\)/i';

    private const string IMPORTED_CSS_REGEX = '/@(?:import|\\\)/i';

    private const string XML_STYLESHEET = 'xml-stylesheet';

    private const string INHERIT_VALUE = 'inherit';

    private const string VALUE_ATTRIBUTE_SELECTOR_REGEX = '/\[[^\]]*=/';

    private const string ATTRIBUTE_SELECTOR_START = '[';

    private const string USER_EXPOSED_ATTRIBUTE_REGEX = '/^(?:role|tabindex|autofocus|aria-.*|on.*)$/i';

    private const string PARENT_VALUE_REGEX = '/(?<![\w-])(?:inherit|unset|revert(?:-layer)?)(?![\w-])|var\(/i';

    private const string XPATH_ANIMATED_ATTRIBUTE_NAMES = '//@attributeName';

    /**
     * @param string $value an attribute value
     *
     * @return bool true if the value is the `inherit` keyword
     */
    private function isInheritKeyword(string $value): bool
    {
        return 0 === strcasecmp(trim($value), self::INHERIT_VALUE);
    }

    /**
     * @param string $value an attribute value, inline style or animation value
     *
     * @return bool true if the value may take the value of the parent, through `inherit`, `unset`, `revert` or a
     *              custom property that may be invalid
     */
    private function mayUseParentValue(string $value): bool
    {
        return 1 === preg_match(self::PARENT_VALUE_REGEX, $value);
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param string      $properties the property names as regular expression alternatives
     *
     * @return bool true if the inline style of the element declares one of the properties, also with a vendor prefix
     */
    private function styleDeclares(\DOMElement $domElement, string $properties): bool
    {
        return 1 === preg_match(
            '/(?<![\w-])(?:-[a-z]+-)?(?:' . $properties . ')\s*:/i',
            $domElement->getAttribute(SvgAttribute::Style->value),
        );
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<string, true> the names of all attributes targeted by animation elements
     */
    private function animatedAttributeNames(\DOMDocument $domDocument): array
    {
        $names = [];

        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = (new \DOMXPath($domDocument))->query(self::XPATH_ANIMATED_ATTRIBUTE_NAMES, null, false);

        foreach ($attributes as $attribute) {
            $names[trim($attribute->value)] = true;
        }

        return $names;
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasStyleElement(\DOMDocument $domDocument): bool
    {
        return $this->hasStylesheetInstruction($domDocument)
            || [] !== $this->elementsNamed($domDocument, SvgTag::Style->value)
            || [] !== $this->elementsNamed($domDocument, SvgTag::Link->value);
    }

    /**
     * @param \DOMDocument $domDocument the document to read
     *
     * @return string the text of every `<style>` element
     */
    private function styleSheetText(\DOMDocument $domDocument): string
    {
        $css = '';

        foreach ($this->elementsNamed($domDocument, SvgTag::Style->value) as $style) {
            $css .= $style->textContent;
        }

        return $css;
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if a style sheet may select elements by the value of an attribute, such as `[fill="red"]`
     */
    private function mayMatchAttributeValues(\DOMDocument $domDocument): bool
    {
        return $this->hasExternalStyleSheet($domDocument)
            || 1 === preg_match(self::VALUE_ATTRIBUTE_SELECTOR_REGEX, $this->styleSheetText($domDocument));
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     *
     * @return bool true if a style sheet may select elements by an attribute, such as `[transform]`
     */
    private function mayMatchAttributes(\DOMDocument $domDocument): bool
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
        return $this->hasStylesheetInstruction($domDocument)
            || [] !== $this->elementsNamed($domDocument, SvgTag::Link->value)
            || 1 === preg_match(self::IMPORTED_CSS_REGEX, $this->styleSheetText($domDocument));
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     */
    private function hasStylesheetInstruction(\DOMDocument $domDocument): bool
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
    private function hasSiblingSensitiveStyle(\DOMDocument $domDocument): bool
    {
        if ($this->hasExternalStyleSheet($domDocument)) {
            return true;
        }

        foreach ($this->elementsNamed($domDocument, SvgTag::Style->value) as $style) {
            if (1 === preg_match(self::SIBLING_SENSITIVE_CSS_REGEX, $style->textContent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMDocument $domDocument the document to search
     * @param string       $localName   the element name to look for
     *
     * @return list<\DOMElement> the matching elements in document order
     */
    private function elementsNamed(\DOMDocument $domDocument, string $localName): array
    {
        $elements = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if ($localName === $domNodeList->localName) {
                $elements[] = $domNodeList;
            }
        }

        return $elements;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasElementChildren(\DOMElement $domElement): bool
    {
        return $domElement->firstElementChild instanceof \DOMElement;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element has no child elements and no non-whitespace text
     */
    private function isEmptyElement(\DOMElement $domElement): bool
    {
        return !$this->hasElementChildren($domElement) && '' === trim($domElement->textContent);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is outside the SVG namespace or inside a `<foreignObject>`
     */
    private function isForeignContent(\DOMElement $domElement): bool
    {
        if (!$this->isSvgElement($domElement)) {
            return true;
        }

        for ($node = $domElement->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
            if (SvgTag::ForeignObject->value === $node->localName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element can be referenced by id
     */
    private function hasId(\DOMElement $domElement): bool
    {
        return $domElement->hasAttribute(SvgAttribute::Id->value)
            || $domElement->hasAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element can be referenced by id, is exposed to assistive technology, can take focus or
     *              handles events
     */
    private function isUserExposed(\DOMElement $domElement): bool
    {
        if ($this->hasId($domElement)) {
            return true;
        }

        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if (null === $attribute->namespaceURI && 1 === preg_match(self::USER_EXPOSED_ATTRIBUTE_REGEX, (string) $attribute->localName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element or a descendant can be referenced by id
     */
    private function hasIdInSubtree(\DOMElement $domElement): bool
    {
        if ($this->hasId($domElement)) {
            return true;
        }

        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if ($this->hasId($domNodeList)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is an animation element, in any ASCII case
     */
    private function isAnimationElement(\DOMElement $domElement): bool
    {
        return \in_array(mb_strtolower((string) $domElement->localName), SvgTag::animation(), true);
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<string, true> the ids targeted by an animation element
     */
    private function animationTargetIds(\DOMDocument $domDocument): array
    {
        $ids = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (!$this->isAnimationElement($domNodeList)) {
                continue;
            }

            foreach ([
                $domNodeList->getAttribute(SvgAttribute::Href->value),
                $domNodeList->getAttributeNS(SvgNamespace::Xlink->value, SvgAttribute::Href->value),
            ] as $href) {
                $href = trim($href);

                if (str_starts_with($href, '#')) {
                    $ids[mb_substr($href, 1)] = true;
                }
            }
        }

        return $ids;
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return list<\DOMElement> the elements whose `id` or `xml:id` is targeted by an animation element
     */
    private function animationTargets(\DOMDocument $domDocument): array
    {
        $targetIds = $this->animationTargetIds($domDocument);
        $elements = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $ids = array_flip([
                $domNodeList->getAttribute(SvgAttribute::Id->value),
                $domNodeList->getAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value),
            ]);
            unset($ids['']);

            if ([] !== array_intersect_key($ids, $targetIds)) {
                $elements[] = $domNodeList;
            }
        }

        return $elements;
    }

    /**
     * @param \DOMDocument $domDocument the document to clean
     */
    private function removeEmptyDefs(\DOMDocument $domDocument): void
    {
        foreach ($this->elementsNamed($domDocument, SvgTag::Defs->value) as $defs) {
            if (!$this->hasId($defs) && $this->isEmptyElement($defs)) {
                $defs->remove();
            }
        }
    }
}
