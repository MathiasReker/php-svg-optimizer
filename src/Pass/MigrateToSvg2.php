<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
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
final readonly class MigrateToSvg2 implements PassInterface
{
    use AttributeMigrationTrait;
    use ElementRemovalTrait;
    use ElementTrait;
    use IdTrait;
    use SelectorTrait;
    use StyleSheetTrait;
    use XlinkMigrationTrait;

    private const string XPATH_XML_EVENTS_NODES = '//*[namespace-uri() = "' . NamespaceUri::XmlEvents->value . '"]'
        . ' | //@*[namespace-uri() = "' . NamespaceUri::XmlEvents->value . '"]';

    private const array REMOVED_ATTRIBUTES = [
        Attribute::Clip->value,
        Attribute::ColorProfile->value,
        Attribute::ColorRendering->value,
        Attribute::EnableBackground->value,
        Attribute::Kerning->value,
        'display-align',
        'editable',
        'focusHighlight',
        'focusable',
        'initialVisibility',
        'line-increment',
        'nav-down',
        'nav-down-left',
        'nav-down-right',
        'nav-left',
        'nav-next',
        'nav-prev',
        'nav-right',
        'nav-up',
        'nav-up-left',
        'nav-up-right',
        'overlay',
        'playbackOrder',
        'requiredFonts',
        'requiredFormats',
        'snapshotTime',
        'solid-color',
        'solid-opacity',
        'syncBehavior',
        'syncBehaviorDefault',
        'syncMaster',
        'syncTolerance',
        'syncToleranceDefault',
        'timelineBegin',
        'transformBehavior',
    ];

    private const array REMOVED_PROPERTIES = [
        Attribute::ColorProfile->value,
        Attribute::ColorRendering->value,
        Attribute::EnableBackground->value,
        Attribute::GlyphOrientationHorizontal->value,
        Attribute::GlyphOrientationVertical->value,
        Attribute::Kerning->value,
        'display-align',
        'line-increment',
        'solid-color',
        'solid-opacity',
    ];

    private const array SVG2_REMOVED_ELEMENTS = [
        'altGlyph', 'altGlyphDef', 'altGlyphItem', 'animateColor', 'animation', 'audio', 'color-profile', 'cursor',
        'definition-src', 'font', 'font-face', 'font-face-format', 'font-face-name', 'font-face-src', 'font-face-uri',
        'glyph', 'glyphRef', 'handler', 'hkern', 'listener', 'missing-glyph', 'prefetch', 'solidColor', 'tbreak',
        'textArea', 'tref', 'video', 'vkern',
    ];

    private const array SVG2_TIMELINE_ATTRIBUTES = [
        'playbackOrder' => ['playbackorder', ['forwardOnly' => 'forwardonly', 'all' => 'all']],
        'timelineBegin' => ['timelinebegin', ['onLoad' => 'loadend', 'onStart' => 'loadbegin']],
    ];

    private const string SVG2_XPATH_XML_ID_ELEMENTS = '//*[@xml:id]';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $hasExternalStyle = $this->hasExternalStyleSheet($domDocument);
        $css = $this->styleSheetText($domDocument);

        if ($this->mayMigrateXlink($domDocument, $css)) {
            $this->migrateXlinkAttributes($domXPath);
        }

        $this->removeXmlIds($domDocument, $hasExternalStyle, $css);
        $removesElements = !$this->hasStructureSensitiveStyleSheet($domDocument);
        $obsoleteIds = $removesElements ? $this->removeObsoleteElements($domDocument) : [];

        $this->migrateAttributes($domDocument, $css, $obsoleteIds);

        if (!$hasExternalStyle) {
            $this->removeXmlEventsNodes($domXPath, $css, $removesElements);
        }

        $this->removeUnusedNamespaceDeclarations($domDocument, $domXPath, NamespaceUri::Xlink->value);
        $this->removeUnusedNamespaceDeclarations($domDocument, $domXPath, NamespaceUri::XmlEvents->value);
    }

    /**
     * @param \DOMDocument          $domDocument the DOM document to modify
     * @param string                $css         the style sheets of the document
     * @param array<string, string> $obsoleteIds the ids of the obsolete elements, whose references fall back
     */
    private function migrateAttributes(\DOMDocument $domDocument, string $css, array $obsoleteIds): void
    {
        $removable = $this->unselectedDroppedAttributes($domDocument, $css, self::REMOVED_ATTRIBUTES);
        $migratesStyles = [] !== $this->unselectedAttributes($domDocument, $css, [Attribute::Style->value]);

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if ($this->isForeignContent($element)) {
                continue;
            }

            $this->migrateTimelineAttributes($element, $removable);
            $this->removeObsoleteAttributes($element, $removable);

            $this->migratePaintAttributes($element, $obsoleteIds);

            if ($migratesStyles) {
                $this->migrateStyleAttribute($element, self::REMOVED_PROPERTIES, $obsoleteIds);
            }
        }
    }

    /**
     * @param \DOMXPath $domXPath        the XPath object for querying the document
     * @param string    $css             the style sheets of the document
     * @param bool      $removesElements true if no style sheet depends on the siblings of an element
     */
    private function removeXmlEventsNodes(\DOMXPath $domXPath, string $css, bool $removesElements): void
    {
        /** @var \DOMNodeList<\DOMAttr|\DOMElement> $nodes */
        $nodes = $domXPath->query(self::XPATH_XML_EVENTS_NODES, null, false);

        foreach ($nodes as $node) {
            $parent = $node->parentNode;

            if (!$parent instanceof \DOMElement
                || $this->isForeignContent($parent)
                || $this->hasAttributeSelector($css, (string) $node->localName)
            ) {
                continue;
            }

            if ($node instanceof \DOMAttr) {
                $parent->removeAttributeNode($node);
            } elseif ($removesElements && !$this->hasReferenceableDescendant($node, [])) {
                $parent->removeChild($node);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to modify
     *
     * @return array<string, string> the unique ids of the obsolete elements, which are kept, by themselves
     */
    private function removeObsoleteElements(\DOMDocument $domDocument): array
    {
        $idCounts = [];

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            $id = $element->getAttribute(Attribute::Id->value);
            $idCounts[$id] = ($idCounts[$id] ?? 0) + 1;
        }

        $obsoleteIds = [];

        foreach (self::SVG2_REMOVED_ELEMENTS as $localName) {
            foreach ($this->elementsByLocalName($domDocument, $localName) as $element) {
                $id = $element->getAttribute(Attribute::Id->value);

                if (('' === $id || 1 === $idCounts[$id])
                    && $this->isRemovableUnrenderedElement($element, self::SVG2_REMOVED_ELEMENTS)
                ) {
                    $obsoleteIds += $this->removeObsoleteElement($element, $id);
                }
            }
        }

        return $obsoleteIds;
    }

    /**
     * @param \DOMElement $domElement an obsolete element
     * @param string      $id         the id of the element, or an empty string
     *
     * @return array<string, string> the id of the element by itself, as an element with an id is kept, or nothing
     */
    private function removeObsoleteElement(\DOMElement $domElement, string $id): array
    {
        if ($this->hasId($domElement)) {
            return '' === $id ? [] : [$id => $id];
        }

        $domElement->remove();

        return [];
    }

    /**
     * @param \DOMDocument $domDocument      the DOM document to modify
     * @param bool         $hasExternalStyle true if an external style sheet is used
     * @param string       $css              the style sheets of the document
     */
    private function removeXmlIds(\DOMDocument $domDocument, bool $hasExternalStyle, string $css): void
    {
        if ($hasExternalStyle || $this->hasAttributeSelector($css, Attribute::Id->value)) {
            return;
        }

        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = (new \DOMXPath($domDocument))->query(self::SVG2_XPATH_XML_ID_ELEMENTS, null, false);

        foreach ($elements as $element) {
            if (!$this->isForeignContent($element)) {
                $element->removeAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value);
            }
        }
    }

    /**
     * @param \DOMElement  $domElement the element to modify
     * @param list<string> $removable  the attributes that may be removed
     */
    private function migrateTimelineAttributes(\DOMElement $domElement, array $removable): void
    {
        foreach (self::SVG2_TIMELINE_ATTRIBUTES as $attribute => [$replacement, $values]) {
            $value = $values[trim($domElement->getAttribute($attribute))] ?? null;

            if (null !== $value
                && Element::Svg->value === $domElement->localName
                && \in_array($attribute, $removable, true)
                && !$domElement->hasAttribute($replacement)
            ) {
                $domElement->setAttribute($replacement, $value);
            }
        }
    }

    /**
     * @param \DOMElement           $domElement  the element to modify
     * @param array<string, string> $obsoleteIds the ids of the obsolete elements
     */
    private function migratePaintAttributes(\DOMElement $domElement, array $obsoleteIds): void
    {
        foreach (self::SVG2_PAINT_PROPERTIES as $property) {
            $value = $domElement->getAttribute($property);
            $migrated = $this->withoutObsoleteReference($value, $obsoleteIds);

            if ($migrated !== $value) {
                $domElement->setAttribute($property, $migrated);
            }
        }
    }
}
