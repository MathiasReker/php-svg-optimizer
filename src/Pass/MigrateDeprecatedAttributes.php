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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2\XlinkMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MigrateDeprecatedAttributes implements PassInterface
{
    use AttributeMigrationTrait;
    use ElementTrait;
    use StyleSheetTrait;
    use XlinkMigrationTrait;

    private const array DEPRECATED_ATTRIBUTES = [Attribute::GlyphOrientationVertical->value];

    private const string XLINK_TITLE = 'title';

    private const string XML_LANG = 'lang';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $css = $this->styleSheetText($domDocument);

        if ($this->mayMigrateXlink($domDocument, $css)) {
            $this->convertXlinkTitles($domDocument);
            $this->migrateXlinkAttributes($domXPath);
        }

        $removable = $this->unselectedDroppedAttributes($domDocument, $css, self::DEPRECATED_ATTRIBUTES);
        $xmlAttributes = [self::XML_LANG, self::SVG2_XML_BASE];
        $migratesXmlAttributes = $xmlAttributes === $this->unselectedAttributes($domDocument, $css, $xmlAttributes);

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if ($this->isForeignContent($element)) {
                continue;
            }

            $this->removeObsoleteAttributes($element, $removable);

            if ($migratesXmlAttributes) {
                $this->migrateXmlLang($element);
                $this->removeXmlBase($element);
            }
        }

        $this->removeUnusedNamespaceDeclarations($domDocument, $domXPath, NamespaceUri::Xlink->value);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to modify
     */
    private function convertXlinkTitles(\DOMDocument $domDocument): void
    {
        $insertsTitles = !$this->hasStructureSensitiveStyleSheet($domDocument);

        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            if ($this->isForeignContent($domElement)) {
                continue;
            }

            if (Element::A->value !== $domElement->localName) {
                $domElement->removeAttributeNS(NamespaceUri::Xlink->value, self::XLINK_TITLE);
            } elseif ($insertsTitles && !$this->hasTitleChild($domElement)) {
                $this->convertXlinkTitle($domDocument, $domElement);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to modify
     * @param \DOMElement  $domElement  the link whose `xlink:title` becomes a `<title>` child
     */
    private function convertXlinkTitle(\DOMDocument $domDocument, \DOMElement $domElement): void
    {
        $title = $domElement->getAttributeNS(NamespaceUri::Xlink->value, self::XLINK_TITLE);
        $domElement->removeAttributeNS(NamespaceUri::Xlink->value, self::XLINK_TITLE);

        if ('' !== trim($title)) {
            $titleElement = $domDocument->createElementNS($domElement->namespaceURI, Element::Title->value);
            $titleElement->textContent = $title;
            $domElement->insertBefore($titleElement, $domElement->firstChild);
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasTitleChild(\DOMElement $domElement): bool
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement && Element::Title->value === $child->localName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element whose `xml:lang` becomes `lang`, which it takes precedence over
     */
    private function migrateXmlLang(\DOMElement $domElement): void
    {
        if (!$domElement->hasAttributeNS(NamespaceUri::Xml->value, self::XML_LANG)) {
            return;
        }

        $domElement->setAttributeNS(null, Attribute::Lang->value, $domElement->getAttributeNS(NamespaceUri::Xml->value, self::XML_LANG));
        $domElement->removeAttributeNS(NamespaceUri::Xml->value, self::XML_LANG);
    }
}
