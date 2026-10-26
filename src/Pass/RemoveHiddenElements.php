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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritedValueTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveHiddenElements implements PassInterface
{
    use AsciiCaseTrait;
    use ElementTrait;
    use IdReferenceTrait;
    use InheritedValueTrait;
    use NumberLiteralTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    private const array RENDERED_ELEMENTS = [
        'a', 'circle', 'ellipse', 'g', 'image', 'line', 'path', 'polygon', 'polyline', 'rect', 'svg', 'text', 'use',
    ];

    private const array SHAPE_ELEMENTS = ['circle', 'ellipse', 'line', 'path', 'polygon', 'polyline', 'rect'];

    private const array NON_RENDERING_CONTAINER_ELEMENTS = [
        'defs', 'clipPath', 'mask', 'pattern', 'marker', 'symbol', 'switch', 'text', 'tspan', 'textPath', 'linearGradient',
        'radialGradient', 'filter', 'font', 'glyph', 'missing-glyph',
    ];

    private const array ANIMATION_ELEMENTS = ['animate', 'set', 'animatecolor', 'animatemotion', 'animatetransform', 'discard'];

    private const array POINTER_ELEMENTS = ['a', 'desc', 'text', 'tspan', 'textPath', 'title'];

    private const array INVISIBLE_KEEPER_ATTRIBUTES = [
        'cursor', 'pointer-events', 'filter', 'mask', 'clip-path', 'transform-box', 'transform-origin',
    ];

    private const array POINTER_ATTRIBUTES = ['cursor', 'pointer-events'];

    private const array EXPOSING_ATTRIBUTES = ['role', 'tabindex'];

    private const array UNPAINTED_KEEPER_ATTRIBUTES = ['id', 'marker', 'marker-start', 'marker-mid', 'marker-end'];

    private const string ARIA_PREFIX = 'aria-';

    private const string NONE = 'none';

    private const string STYLE_PROPERTIES = 'display|opacity|fill|stroke|visibility|marker(?:-[a-z]+)?|pointer-events|cursor|filter|mask(?:-[a-z]+)?|clip(?:-path)?|transform-(?:box|origin)|all';

    private const int IN_NON_RENDERING_CONTAINER = 1;

    private const int IN_FOREIGN_OBJECT = 2;

    private const int KEPT = 4;

    private const int KEPT_WHEN_INVISIBLE = 8;

    private const int TOOLTIP = 16;

    private const int KEPT_WHEN_UNPAINTED = 32;

    private const int BLOCKING_DESCENDANT = 1;

    private const int EXPOSED_DESCENDANT = 2;

    private const int POINTER_DESCENDANT = 4;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument) || $this->hasStructureSensitiveStyleSheet($domDocument) || $this->hasScriptOrEventHandler($domDocument)) {
            return;
        }

        $elements = $this->elementsByQualifiedName($domDocument, '*');
        $ancestry = $this->ancestryFlags($elements, $this->referencedIds($domDocument));
        $descendants = $this->descendantFlags($elements);
        $paint = $this->paintKeywords($elements);

        foreach ($elements as $element) {
            $parent = $element->parentNode;
            $id = spl_object_id($element);

            if ($parent instanceof \DOMElement
                && $this->isCandidate($element, $ancestry[spl_object_id($parent)], $ancestry[$id], $descendants[$id])
                && $this->isHidden($element, $ancestry[$id], $descendants[$id], $paint[$id])
            ) {
                $parent->removeChild($element);
            }
        }
    }

    /**
     * @param list<\DOMElement>   $elements      the elements of the document, in document order
     * @param array<string, true> $referencedIds the ids referenced anywhere in the document
     *
     * @return array<int, int> the flags that the element and its ancestors set, by object id
     */
    private function ancestryFlags(array $elements, array $referencedIds): array
    {
        $flags = [];

        foreach ($elements as $element) {
            $parent = $element->parentNode;
            $inherited = $parent instanceof \DOMElement ? $flags[spl_object_id($parent)] : 0;
            $flags[spl_object_id($element)] = $inherited | $this->ownAncestryFlags($element, $referencedIds);
        }

        return $flags;
    }

    /**
     * @param \DOMElement         $domElement    the element to read
     * @param array<string, true> $referencedIds the ids referenced anywhere in the document
     */
    private function ownAncestryFlags(\DOMElement $domElement, array $referencedIds): int
    {
        $localName = (string) $domElement->localName;
        $kept = $this->hasChild($domElement, fn (\DOMElement $domElement): bool => $this->isAnimation($domElement))
            || $this->isReferencedElement($domElement, $referencedIds)
            || $this->inlineStyleDeclares($domElement, self::STYLE_PROPERTIES);

        return $this->flag(\in_array($localName, self::NON_RENDERING_CONTAINER_ELEMENTS, true), self::IN_NON_RENDERING_CONTAINER)
            | $this->flag(Element::ForeignObject->value === $localName, self::IN_FOREIGN_OBJECT)
            | $this->flag($kept, self::KEPT)
            | $this->flag(Element::A->value === $localName || $this->hasAnyAttribute($domElement, self::INVISIBLE_KEEPER_ATTRIBUTES), self::KEPT_WHEN_INVISIBLE)
            | $this->flag($this->hasChild($domElement, static fn (\DOMElement $domElement): bool => Element::Title->value === $domElement->localName), self::TOOLTIP)
            | $this->flag($this->hasXmlId($domElement) || $this->hasAnyAttribute($domElement, self::UNPAINTED_KEEPER_ATTRIBUTES), self::KEPT_WHEN_UNPAINTED);
    }

    /**
     * @param list<\DOMElement> $elements the elements of the document, in document order
     *
     * @return array<int, int> the flags that the element and its descendants set, by object id
     */
    private function descendantFlags(array $elements): array
    {
        $flags = [];

        foreach (array_reverse($elements) as $domElement) {
            $id = spl_object_id($domElement);
            $flags[$id] = ($flags[$id] ?? 0) | $this->ownDescendantFlags($domElement);
            $parent = $domElement->parentNode;

            if ($parent instanceof \DOMElement) {
                $flags[spl_object_id($parent)] = ($flags[spl_object_id($parent)] ?? 0) | $flags[$id];
            }
        }

        return $flags;
    }

    /**
     * @param \DOMElement $domElement the element to read
     */
    private function ownDescendantFlags(\DOMElement $domElement): int
    {
        $localName = (string) $domElement->localName;
        $blocking = $domElement->hasAttribute(Attribute::Id->value)
            || $this->hasXmlId($domElement)
            || Element::ForeignObject->value === $localName
            || !\in_array($domElement->namespaceURI, [null, NamespaceUri::Svg->value], true)
            || $this->isAnimation($domElement);

        return $this->flag($blocking, self::BLOCKING_DESCENDANT)
            | $this->flag($this->hasAnyAttribute($domElement, self::EXPOSING_ATTRIBUTES) || $this->hasAriaAttribute($domElement), self::EXPOSED_DESCENDANT)
            | $this->flag(\in_array($localName, self::POINTER_ELEMENTS, true) || $this->hasAnyAttribute($domElement, self::POINTER_ATTRIBUTES), self::POINTER_DESCENDANT);
    }

    /**
     * @param list<\DOMElement> $elements the elements of the document, in document order
     *
     * @return array<int, array{string, string}> the lowercase fill and stroke keywords each element uses, or empty
     *                                           strings where they cannot be known, by object id
     */
    private function paintKeywords(array $elements): array
    {
        $paint = [];

        foreach ($elements as $element) {
            $parent = $element->parentNode;
            [$fill, $stroke] = $parent instanceof \DOMElement
                ? $paint[spl_object_id($parent)]
                : [self::UNKNOWN_INHERITED_VALUE, self::UNKNOWN_INHERITED_VALUE];
            $paint[spl_object_id($element)] = [
                $this->asciiLowercase($this->ownInheritedValue($element, Attribute::Fill->value, $fill)),
                $this->asciiLowercase($this->ownInheritedValue($element, Attribute::Stroke->value, $stroke)),
            ];
        }

        return $paint;
    }

    /**
     * @param \DOMElement $domElement     the element to check
     * @param int         $parentAncestry the flags that the parent and its ancestors set
     * @param int         $ancestry       the flags that the element and its ancestors set
     * @param int         $descendants    the flags that the element and its descendants set
     */
    private function isCandidate(\DOMElement $domElement, int $parentAncestry, int $ancestry, int $descendants): bool
    {
        return \in_array($domElement->localName, self::RENDERED_ELEMENTS, true)
            && 0 === ($parentAncestry & (self::IN_NON_RENDERING_CONTAINER | self::IN_FOREIGN_OBJECT))
            && $this->isSvgElement($domElement)
            && 0 === ($descendants & self::BLOCKING_DESCENDANT)
            && 0 === ($ancestry & self::KEPT);
    }

    /**
     * @param \DOMElement           $domElement  the element to check
     * @param int                   $ancestry    the flags that the element and its ancestors set
     * @param int                   $descendants the flags that the element and its descendants set
     * @param array{string, string} $paint       the fill and stroke keywords the element uses
     */
    private function isHidden(\DOMElement $domElement, int $ancestry, int $descendants, array $paint): bool
    {
        if (self::NONE === $this->asciiLowercase(trim($domElement->getAttribute(Attribute::Display->value)))) {
            return true;
        }

        if (0 !== ($ancestry & self::KEPT_WHEN_INVISIBLE) || 0 !== ($descendants & self::EXPOSED_DESCENDANT)) {
            return false;
        }

        return $this->isTransparent($domElement, $ancestry, $descendants) || $this->isUnpainted($domElement, $ancestry, $paint);
    }

    /**
     * @param \DOMElement $domElement  the element to check
     * @param int         $ancestry    the flags that the element and its ancestors set
     * @param int         $descendants the flags that the element and its descendants set
     */
    private function isTransparent(\DOMElement $domElement, int $ancestry, int $descendants): bool
    {
        return $this->isZeroNumber(trim($domElement->getAttribute(Attribute::Opacity->value)))
            && 0 === ($ancestry & self::TOOLTIP)
            && 0 === ($descendants & self::POINTER_DESCENDANT);
    }

    /**
     * @param \DOMElement           $domElement the element to check
     * @param int                   $ancestry   the flags that the element and its ancestors set
     * @param array{string, string} $paint      the fill and stroke keywords the element uses
     */
    private function isUnpainted(\DOMElement $domElement, int $ancestry, array $paint): bool
    {
        return \in_array($domElement->localName, self::SHAPE_ELEMENTS, true)
            && [self::NONE, self::NONE] === $paint
            && 0 === ($ancestry & self::KEPT_WHEN_UNPAINTED);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isAnimation(\DOMElement $domElement): bool
    {
        return \in_array($this->asciiLowercase((string) $domElement->localName), self::ANIMATION_ELEMENTS, true);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasXmlId(\DOMElement $domElement): bool
    {
        return $domElement->hasAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasAriaAttribute(\DOMElement $domElement): bool
    {
        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if (str_starts_with((string) $attribute->localName, self::ARIA_PREFIX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $condition whether the flag is set
     * @param int  $flag      the flag
     *
     * @return int the flag if the condition holds, or zero
     */
    private function flag(bool $condition, int $flag): int
    {
        return $condition ? $flag : 0;
    }
}
