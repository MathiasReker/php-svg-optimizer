<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2XlinkMigrationTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveDeprecatedAttributes implements PassInterface
{
    use Svg2AttributeMigrationTrait;
    use Svg2XlinkMigrationTrait;

    private const array DEPRECATED_ATTRIBUTES = [SvgAttribute::GlyphOrientationVertical->value];

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

        $removable = $this->removableDroppedAttributes($domDocument, $css, self::DEPRECATED_ATTRIBUTES);
        $xmlAttributes = [self::XML_LANG, self::SVG2_XML_BASE];
        $migratesXmlAttributes = $xmlAttributes === $this->removableAttributes($domDocument, $css, $xmlAttributes);

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if ($this->isForeignContent($domNodeList)) {
                continue;
            }

            $this->removeObsoleteAttributes($domNodeList, $removable);

            if ($migratesXmlAttributes) {
                $this->migrateXmlLang($domNodeList);
                $this->removeXmlBase($domNodeList);
            }
        }

        $this->removeUnusedNamespaceDeclarations($domDocument, $domXPath, SvgNamespace::Xlink->value);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to modify
     */
    private function convertXlinkTitles(\DOMDocument $domDocument): void
    {
        $insertsTitles = !$this->hasSiblingSensitiveStyle($domDocument);

        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            if ($this->isForeignContent($domElement)) {
                continue;
            }

            if (SvgTag::A->value !== $domElement->localName) {
                $domElement->removeAttributeNS(SvgNamespace::Xlink->value, self::XLINK_TITLE);
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
        $title = $domElement->getAttributeNS(SvgNamespace::Xlink->value, self::XLINK_TITLE);
        $domElement->removeAttributeNS(SvgNamespace::Xlink->value, self::XLINK_TITLE);

        if ('' !== trim($title)) {
            $titleElement = $domDocument->createElementNS($domElement->namespaceURI, SvgTag::Title->value);
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
            if ($child instanceof \DOMElement && SvgTag::Title->value === $child->localName) {
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
        if (!$domElement->hasAttributeNS(SvgNamespace::Xml->value, self::XML_LANG)) {
            return;
        }

        $domElement->setAttributeNS(null, SvgAttribute::Lang->value, $domElement->getAttributeNS(SvgNamespace::Xml->value, self::XML_LANG));
        $domElement->removeAttributeNS(SvgNamespace::Xml->value, self::XML_LANG);
    }
}
