<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait XlinkMigrationTrait
{
    use ElementTrait;

    private const string SVG2_XPATH_XLINK_ATTRIBUTES = '//@*[namespace-uri() = "http://www.w3.org/1999/xlink"]';

    private const string SVG2_XPATH_ATTRIBUTE_NAMES = '//@attributeName[contains(., ":")]';

    private const string SVG2_XPATH_NAMESPACE_NODES = 'boolean(//*[namespace-uri() = "%1$s"] | //@*[namespace-uri() = "%1$s"])';

    private const string SVG2_NAMESPACE_DECLARATION_REGEX = '/\sxmlns:([^\s=]+)="%s"/';

    private const string SVG2_XPATH_PREFIX_IN_VALUE = 'boolean(//@*[contains(., "%s:")])';

    private const string SVG2_XLINK_SHOW_NEW = 'new';

    private const string SVG2_TARGET_BLANK = '_blank';

    private const array SVG2_REMOVED_XLINK_ATTRIBUTES = ['actuate', 'arcrole', 'role', 'show', 'type'];

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function migrateXlinkAttributes(\DOMXPath $domXPath): void
    {
        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = $domXPath->query(self::SVG2_XPATH_XLINK_ATTRIBUTES, null, false);

        foreach ($attributes as $attribute) {
            $domElement = $attribute->ownerElement;

            if ($domElement instanceof \DOMElement && !$this->isForeignContent($domElement)) {
                $this->migrateXlinkAttribute($domElement, $attribute);
            }
        }

        /** @var \DOMNodeList<\DOMAttr> $attributeNames */
        $attributeNames = $domXPath->query(self::SVG2_XPATH_ATTRIBUTE_NAMES, null, false);

        foreach ($attributeNames as $attributeName) {
            $this->migrateAnimatedXlinkHref($attributeName);
        }
    }

    /**
     * @param \DOMElement $domElement the element that holds the attribute
     * @param \DOMAttr    $domAttr    the attribute in the XLink namespace
     */
    private function migrateXlinkAttribute(\DOMElement $domElement, \DOMAttr $domAttr): void
    {
        $localName = (string) $domAttr->localName;

        if (Attribute::Href->value === $localName) {
            $this->replaceXlinkAttribute($domElement, $domAttr, Attribute::Href->value, $domAttr->value);

            return;
        }

        if ('show' === $localName
            && Element::A->value === $domElement->localName
            && self::SVG2_XLINK_SHOW_NEW === $domAttr->value
            && '' === $domElement->getAttribute(Attribute::Target->value)
        ) {
            $domElement->setAttribute(Attribute::Target->value, self::SVG2_TARGET_BLANK);
        }

        if (\in_array($localName, self::SVG2_REMOVED_XLINK_ATTRIBUTES, true)) {
            $domElement->removeAttributeNode($domAttr);
        }
    }

    /**
     * @param \DOMElement $domElement the element that holds the attribute
     * @param \DOMAttr    $domAttr    the attribute in the XLink namespace
     * @param string      $name       the attribute that replaces it, unless the element already has it
     * @param string      $value      the value of the replacement
     */
    private function replaceXlinkAttribute(\DOMElement $domElement, \DOMAttr $domAttr, string $name, string $value): void
    {
        $domElement->removeAttributeNode($domAttr);

        if (!$domElement->hasAttributeNS(null, $name)) {
            $domElement->setAttributeNS(null, $name, $value);
        }
    }

    /**
     * @param \DOMAttr $domAttr the `attributeName` attribute of an animation element
     */
    private function migrateAnimatedXlinkHref(\DOMAttr $domAttr): void
    {
        $domElement = $domAttr->ownerElement;
        [$prefix, $localName] = explode(':', trim($domAttr->value), 2);

        if ($domElement instanceof \DOMElement
            && Attribute::Href->value === $localName
            && NamespaceUri::Xlink->value === $domElement->lookupNamespaceURI($prefix)
            && !$this->isForeignContent($domElement)
        ) {
            $domAttr->value = Attribute::Href->value;
        }
    }

    /**
     * @param \DOMDocument $domDocument  the DOM document to modify
     * @param \DOMXPath    $domXPath     the XPath object for querying the document
     * @param string       $namespaceUri the namespace whose unused declarations are removed
     */
    private function removeUnusedNamespaceDeclarations(\DOMDocument $domDocument, \DOMXPath $domXPath, string $namespaceUri): void
    {
        if (true === $domXPath->evaluate(\sprintf(self::SVG2_XPATH_NAMESPACE_NODES, $namespaceUri), null, false)) {
            return;
        }

        preg_match_all(
            \sprintf(self::SVG2_NAMESPACE_DECLARATION_REGEX, preg_quote($namespaceUri, '/')),
            (string) $domDocument->saveXML(),
            $matches,
        );

        foreach (array_unique($matches[1]) as $prefix) {
            if (true === $domXPath->evaluate(\sprintf(self::SVG2_XPATH_PREFIX_IN_VALUE, $prefix), null, false)) {
                continue;
            }

            foreach ($domDocument->getElementsByTagName('*') as $domElement) {
                $domElement->removeAttributeNS($namespaceUri, $prefix);
            }
        }
    }
}
