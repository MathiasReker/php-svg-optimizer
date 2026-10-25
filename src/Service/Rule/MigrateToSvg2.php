<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2AttributeMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2ElementMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2TinyMigrationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\Svg2XlinkMigrationTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MigrateToSvg2 implements SvgOptimizerRuleInterface
{
    use CssDeclarationTrait;
    use ElementTrait;
    use Svg2AttributeMigrationTrait;
    use Svg2ElementMigrationTrait;
    use Svg2TinyMigrationTrait;
    use Svg2XlinkMigrationTrait;

    private const string XPATH_XML_EVENTS_NODES = '//*[namespace-uri() = "' . SvgNamespace::XmlEvents->value . '"]'
        . ' | //@*[namespace-uri() = "' . SvgNamespace::XmlEvents->value . '"]';

    private const array REMOVED_ATTRIBUTES = [
        SvgAttribute::Clip->value,
        SvgAttribute::ColorProfile->value,
        SvgAttribute::ColorRendering->value,
        SvgAttribute::EnableBackground->value,
        SvgAttribute::Kerning->value,
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
        SvgAttribute::ColorProfile->value,
        SvgAttribute::ColorRendering->value,
        SvgAttribute::EnableBackground->value,
        SvgAttribute::GlyphOrientationHorizontal->value,
        SvgAttribute::GlyphOrientationVertical->value,
        SvgAttribute::Kerning->value,
        'display-align',
        'line-increment',
        'solid-color',
        'solid-opacity',
    ];

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $hasExternalStyle = $this->hasExternalStyleSheet($domDocument);
        $css = $this->styleSheetText($domDocument);

        if ($this->mayMigrateXlink($domDocument, $css)) {
            $this->migrateXlinkAttributes($domXPath);
        }

        $this->removeXmlIds($domDocument, $hasExternalStyle, $css);
        $removesElements = !$this->hasSiblingSensitiveStyle($domDocument);
        $removedIds = $removesElements ? $this->removeObsoleteElements($domDocument) : [];

        $this->migrateAttributes($domDocument, $css, $removedIds);

        if (!$hasExternalStyle) {
            $this->removeXmlEventsNodes($domXPath, $css, $removesElements);
        }

        $this->removeUnusedNamespaceDeclarations($domDocument, $domXPath, SvgNamespace::Xlink->value);
        $this->removeUnusedNamespaceDeclarations($domDocument, $domXPath, SvgNamespace::XmlEvents->value);
    }

    /**
     * @param \DOMDocument          $domDocument the DOM document to modify
     * @param string                $css         the style sheets of the document
     * @param array<string, string> $removedIds  the ids of the removed elements
     */
    private function migrateAttributes(\DOMDocument $domDocument, string $css, array $removedIds): void
    {
        $removable = $this->removableDroppedAttributes($domDocument, $css, self::REMOVED_ATTRIBUTES);
        $migratesStyles = [] !== $this->removableAttributes($domDocument, $css, [SvgAttribute::Style->value]);

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if ($this->isForeignContent($domNodeList)) {
                continue;
            }

            $this->migrateTimelineAttributes($domNodeList, $removable);
            $this->removeObsoleteAttributes($domNodeList, $removable);

            $this->migratePaintAttributes($domNodeList, $removedIds);

            if ($migratesStyles) {
                $this->migrateStyleAttribute($domNodeList, self::REMOVED_PROPERTIES, $removedIds);
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
}
