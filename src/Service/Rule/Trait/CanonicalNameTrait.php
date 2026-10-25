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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgElementAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait CanonicalNameTrait
{
    private const array SVG_TAG_ADJUSTMENTS = [
        'altglyph' => 'altGlyph',
        'altglyphdef' => 'altGlyphDef',
        'altglyphitem' => 'altGlyphItem',
        'animatecolor' => 'animateColor',
        'animatemotion' => 'animateMotion',
        'animatetransform' => 'animateTransform',
        'clippath' => 'clipPath',
        'feblend' => 'feBlend',
        'fecolormatrix' => 'feColorMatrix',
        'fecomponenttransfer' => 'feComponentTransfer',
        'fecomposite' => 'feComposite',
        'feconvolvematrix' => 'feConvolveMatrix',
        'fediffuselighting' => 'feDiffuseLighting',
        'fedisplacementmap' => 'feDisplacementMap',
        'fedistantlight' => 'feDistantLight',
        'fedropshadow' => 'feDropShadow',
        'feflood' => 'feFlood',
        'fefunca' => 'feFuncA',
        'fefuncb' => 'feFuncB',
        'fefuncg' => 'feFuncG',
        'fefuncr' => 'feFuncR',
        'fegaussianblur' => 'feGaussianBlur',
        'feimage' => 'feImage',
        'femerge' => 'feMerge',
        'femergenode' => 'feMergeNode',
        'femorphology' => 'feMorphology',
        'feoffset' => 'feOffset',
        'fepointlight' => 'fePointLight',
        'fespecularlighting' => 'feSpecularLighting',
        'fespotlight' => 'feSpotLight',
        'fetile' => 'feTile',
        'feturbulence' => 'feTurbulence',
        'foreignobject' => 'foreignObject',
        'glyphref' => 'glyphRef',
        'lineargradient' => 'linearGradient',
        'radialgradient' => 'radialGradient',
        'textpath' => 'textPath',
    ];

    private const array FOREIGN_ATTRIBUTE_NAMESPACES = [
        'xlink:actuate' => SvgNamespace::Xlink->value,
        'xlink:arcrole' => SvgNamespace::Xlink->value,
        'xlink:href' => SvgNamespace::Xlink->value,
        'xlink:role' => SvgNamespace::Xlink->value,
        'xlink:show' => SvgNamespace::Xlink->value,
        'xlink:title' => SvgNamespace::Xlink->value,
        'xlink:type' => SvgNamespace::Xlink->value,
        'xml:lang' => SvgNamespace::Xml->value,
        'xml:space' => SvgNamespace::Xml->value,
    ];

    private const string NAMESPACE_SEPARATOR = ':';

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the name of the element folds to a known element name but is not written in its canonical
     *              case, so an HTML parser would read it as that element
     */
    private function isNonCanonicalElement(\DOMElement $domElement): bool
    {
        $localName = $domElement->localName ?? '';
        $folded = mb_strtolower($localName);

        return $localName !== (self::SVG_TAG_ADJUSTMENTS[$folded] ?? $folded)
            && \array_key_exists($folded, $this->knownElementNames());
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the name of the attribute folds to a known attribute name but is not written in its
     *              canonical case or namespace, so an HTML parser would read it as that attribute
     */
    private function isNonCanonicalAttribute(\DOMAttr $domAttr): bool
    {
        $qualifiedName = $domAttr->nodeName;

        if (str_contains($qualifiedName, self::NAMESPACE_SEPARATOR)) {
            return $this->isNonCanonicalPrefixedAttribute($domAttr, mb_strtolower($qualifiedName));
        }

        $canonical = $this->canonicalAttributeNames()[mb_strtolower($qualifiedName)] ?? $qualifiedName;

        return $qualifiedName !== $canonical;
    }

    /**
     * @param \DOMAttr $domAttr the attribute with a prefix
     * @param string   $folded  the qualified name of the attribute, folded to lowercase
     *
     * @return bool true if an HTML parser would read the attribute as a known XLink or XML attribute that it is not
     */
    private function isNonCanonicalPrefixedAttribute(\DOMAttr $domAttr, string $folded): bool
    {
        if (!\array_key_exists($folded, self::FOREIGN_ATTRIBUTE_NAMESPACES)) {
            return false;
        }

        return match ($domAttr->namespaceURI) {
            self::FOREIGN_ATTRIBUTE_NAMESPACES[$folded] => $domAttr->localName !== $this->asciiLowercase($domAttr->localName ?? ''),
            null => $domAttr->nodeName !== $folded,
            default => true,
        };
    }

    /**
     * @param \DOMElement $domElement the element to read
     * @param string      $foldedName the lowercase name of the attribute
     *
     * @return list<string> the values of the attributes without a namespace whose name folds to the given name
     */
    private function foldedAttributeValues(\DOMElement $domElement, string $foldedName): array
    {
        $values = [];

        foreach ($domElement->attributes as $domAttr) {
            if (null === $domAttr->namespaceURI && $foldedName === $this->asciiLowercase($domAttr->nodeName)) {
                $values[] = $domAttr->value;
            }
        }

        return $values;
    }

    /**
     * @return array<string, true> the known element names, folded to lowercase
     */
    private function knownElementNames(): array
    {
        /** @var array<string, true>|null $names */
        static $names = null;

        return $names ??= array_fill_keys(array_map(strtolower(...), SvgElementAttributes::elements()), true);
    }

    /**
     * @return array<string, string> the canonical name of each known attribute without a prefix, by its lowercase name
     */
    private function canonicalAttributeNames(): array
    {
        /** @var array<string, string>|null $names */
        static $names = null;

        return $names ??= array_combine(array_map(strtolower(...), SvgAttribute::values()), SvgAttribute::values());
    }
}
