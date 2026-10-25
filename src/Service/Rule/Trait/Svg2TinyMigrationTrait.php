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
trait Svg2TinyMigrationTrait
{
    use CssDeclarationTrait;
    use ElementTrait;
    use Svg2AttributeMigrationTrait;

    private const array SVG2_TIMELINE_ATTRIBUTES = [
        'playbackOrder' => ['playbackorder', ['forwardOnly' => 'forwardonly', 'all' => 'all']],
        'timelineBegin' => ['timelinebegin', ['onLoad' => 'loadend', 'onStart' => 'loadbegin']],
    ];

    private const string SVG2_XPATH_XML_ID_ELEMENTS = '//*[@xml:id]';

    /**
     * @param \DOMDocument $domDocument      the DOM document to modify
     * @param bool         $hasExternalStyle true if an external style sheet is used
     * @param string       $css              the style sheets of the document
     */
    private function removeXmlIds(\DOMDocument $domDocument, bool $hasExternalStyle, string $css): void
    {
        if ($hasExternalStyle || $this->hasAttributeSelector($css, SvgAttribute::Id->value)) {
            return;
        }

        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = (new \DOMXPath($domDocument))->query(self::SVG2_XPATH_XML_ID_ELEMENTS, null, false);

        foreach ($elements as $element) {
            if (!$this->isForeignContent($element)) {
                $element->removeAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value);
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
                && SvgTag::Svg->value === $domElement->localName
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
