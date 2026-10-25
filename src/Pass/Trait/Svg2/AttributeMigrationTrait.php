<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Svg2;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait AttributeMigrationTrait
{
    use DeclarationTrait;
    use SelectorTrait;
    use StyleSheetTrait;

    private const array SVG2_DEPRECATED_ATTRIBUTES = [
        Attribute::BaseProfile->value,
        Attribute::ContentScriptType->value,
        Attribute::ContentStyleType->value,
        Attribute::CurrentView->value,
        Attribute::ExternalResourcesRequired->value,
        Attribute::GlyphOrientationHorizontal->value,
        Attribute::RequiredFeatures->value,
        Attribute::SuspendRedraw->value,
        Attribute::UnsuspendRedraw->value,
        Attribute::UnsuspendRedrawAll->value,
        Attribute::UseCurrentView->value,
        Attribute::Version->value,
        Attribute::ViewTarget->value,
        Attribute::Viewport->value,
        Attribute::ZoomAndPan->value,
    ];

    private const array SVG2_PAINT_PROPERTIES = [Attribute::Fill->value, Attribute::Stroke->value];

    private const string SVG2_PAINT_REFERENCE_REGEX = '/^\s*url\(\s*(["\']?)#([^"\')\s]+)\1\s*\)(.*)$/is';

    private const string SVG2_NONE = 'none';

    private const string SVG2_IMPORTANT_START = '!';

    private const string SVG2_XLINK_SELECTOR_REGEX = '/href|xlink/i';

    private const string SVG2_COMMENT_REGEX = '/(?:"(?:[^"\\\]|\\\.)*"|\'(?:[^\'\\\]|\\\.)*\')(*SKIP)(*FAIL)|\/\*.*?\*\//s';

    private const string SVG2_DECLARATION_SEPARATOR = ';';

    private const string SVG2_PROPERTY_SEPARATOR = ':';

    private const string SVG2_XML_BASE = 'base';

    /**
     * @param \DOMDocument $domDocument the document to check
     * @param string       $css         the style sheets of the document
     *
     * @return bool true if no style sheet may select XLink attributes or `href`
     */
    private function mayMigrateXlink(\DOMDocument $domDocument, string $css): bool
    {
        return !$this->hasExternalStyleSheet($domDocument) && 1 !== preg_match(self::SVG2_XLINK_SELECTOR_REGEX, $css);
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     * @param string       $css         the style sheets of the document
     * @param list<string> $attributes  the attributes to remove
     *
     * @return list<string> the attributes that no style sheet may select
     */
    private function unselectedAttributes(\DOMDocument $domDocument, string $css, array $attributes): array
    {
        if ($this->hasExternalStyleSheet($domDocument)) {
            return [];
        }

        return array_values(array_filter(
            $attributes,
            fn (string $attribute): bool => !$this->hasAttributeSelector($css, $attribute),
        ));
    }

    /**
     * @param \DOMDocument $domDocument the document to check
     * @param string       $css         the style sheets of the document
     * @param list<string> $attributes  the attributes to remove besides the deprecated attributes that SVG 2 dropped
     *
     * @return list<string> the attributes that no style sheet may select
     */
    private function unselectedDroppedAttributes(\DOMDocument $domDocument, string $css, array $attributes): array
    {
        return $this->unselectedAttributes($domDocument, $css, [...self::SVG2_DEPRECATED_ATTRIBUTES, ...$attributes]);
    }

    /**
     * @param \DOMElement  $domElement the element to clean
     * @param list<string> $attributes the attributes in no namespace to remove
     */
    private function removeObsoleteAttributes(\DOMElement $domElement, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $domElement->removeAttributeNS(null, $attribute);
        }
    }

    /**
     * @param \DOMElement           $domElement  the element whose inline style is migrated
     * @param list<string>          $properties  the properties to remove
     * @param array<string, string> $obsoleteIds the ids of the obsolete elements, whose references fall back
     */
    private function migrateStyleAttribute(\DOMElement $domElement, array $properties, array $obsoleteIds): void
    {
        $style = (string) preg_replace(self::SVG2_COMMENT_REGEX, '', $domElement->getAttribute(Attribute::Style->value));

        if (str_contains($style, self::CSS_COMMENT_START) || !$this->isSplittableDeclarationBlock($style)) {
            return;
        }

        $declarations = $this->splitDeclarations($style);
        $migrated = array_values(array_filter(
            array_map(fn (string $declaration): string => $this->migrateDeclaration($declaration, $properties, $obsoleteIds), $declarations),
            static fn (string $declaration): bool => '' !== $declaration,
        ));

        if ($migrated === $declarations) {
            return;
        }

        $domElement->setAttribute(Attribute::Style->value, implode(self::SVG2_DECLARATION_SEPARATOR, $migrated));

        if ([] === $migrated) {
            $domElement->removeAttribute(Attribute::Style->value);
        }
    }

    /**
     * @param string                $declaration a declaration of an inline style
     * @param list<string>          $properties  the properties to remove
     * @param array<string, string> $obsoleteIds the ids of the obsolete elements, whose references fall back
     *
     * @return string the SVG 2 declaration, or an empty string if the property was removed
     */
    private function migrateDeclaration(string $declaration, array $properties, array $obsoleteIds): string
    {
        [$property, $value] = explode(self::SVG2_PROPERTY_SEPARATOR, $declaration, 2) + ['', ''];
        $property = mb_strtolower(trim($property));

        if (\in_array($property, $properties, true)) {
            return '';
        }

        if (\in_array($property, self::SVG2_PAINT_PROPERTIES, true)) {
            $paint = $this->withoutObsoleteReference($value, $obsoleteIds);

            return $paint === $value ? $declaration : $property . self::SVG2_PROPERTY_SEPARATOR . $paint;
        }

        return $declaration;
    }

    /**
     * @param string                $paint       a paint value
     * @param array<string, string> $obsoleteIds the ids of the obsolete elements, whose references fall back
     *
     * @return string the fallback of a reference to an obsolete element, `none` without a fallback, or the paint
     */
    private function withoutObsoleteReference(string $paint, array $obsoleteIds): string
    {
        if (1 !== preg_match(self::SVG2_PAINT_REFERENCE_REGEX, $paint, $matches)
            || !\array_key_exists($matches[2], $obsoleteIds)
        ) {
            return $paint;
        }

        $fallback = trim($matches[3]);

        return '' === $fallback || str_starts_with($fallback, self::SVG2_IMPORTANT_START)
            ? trim(self::SVG2_NONE . ' ' . $fallback)
            : $fallback;
    }

    /**
     * @param \DOMElement $domElement the element to clean
     */
    private function removeXmlBase(\DOMElement $domElement): void
    {
        $domElement->removeAttributeNS(NamespaceUri::Xml->value, self::SVG2_XML_BASE);
    }
}
