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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\InheritanceBoundaryTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveHiddenElements implements PassInterface
{
    use AsciiCaseTrait;
    use DocumentScriptTrait;
    use ElementTrait;
    use IdReferenceTrait;
    use InheritanceBoundaryTrait;
    use NumberFormatterTrait;

    private const array RENDERED_TAGS = [
        'a', 'circle', 'ellipse', 'g', 'image', 'line', 'path', 'polygon', 'polyline', 'rect', 'svg', 'text', 'use',
    ];

    private const array SHAPE_TAGS = ['circle', 'ellipse', 'line', 'path', 'polygon', 'polyline', 'rect'];

    private const array NON_RENDERING_CONTAINER_TAGS = [
        'defs', 'clipPath', 'mask', 'pattern', 'marker', 'symbol', 'switch', 'text', 'tspan', 'textPath', 'linearGradient',
        'radialGradient', 'filter', 'font', 'glyph', 'missing-glyph',
    ];

    private const array ANIMATION_TAGS = ['animate', 'set', 'animatecolor', 'animatemotion', 'animatetransform', 'discard'];

    private const array POINTER_TAGS = ['a', 'desc', 'text', 'tspan', 'textPath', 'title'];

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
        if ($this->hasStyleElement($domDocument) || $this->hasSiblingSensitiveStyle($domDocument) || $this->hasScriptOrEventHandler($domDocument)) {
            return;
        }

        $elements = iterator_to_array($domDocument->getElementsByTagName('*'), false);
        $ancestry = $this->ancestryFlags($elements, $this->collectReferencedIds($domDocument));
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
            || $this->styleDeclares($domElement, self::STYLE_PROPERTIES);

        return $this->flag(\in_array($localName, self::NON_RENDERING_CONTAINER_TAGS, true), self::IN_NON_RENDERING_CONTAINER)
            | $this->flag(SvgTag::ForeignObject->value === $localName, self::IN_FOREIGN_OBJECT)
            | $this->flag($kept, self::KEPT)
            | $this->flag(SvgTag::A->value === $localName || $this->hasAnyAttribute($domElement, self::INVISIBLE_KEEPER_ATTRIBUTES), self::KEPT_WHEN_INVISIBLE)
            | $this->flag($this->hasChild($domElement, static fn (\DOMElement $domElement): bool => SvgTag::Title->value === $domElement->localName), self::TOOLTIP)
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
        $blocking = $domElement->hasAttribute(SvgAttribute::Id->value)
            || $this->hasXmlId($domElement)
            || SvgTag::ForeignObject->value === $localName
            || !\in_array($domElement->namespaceURI, [null, SvgNamespace::Svg->value], true)
            || $this->isAnimation($domElement);

        return $this->flag($blocking, self::BLOCKING_DESCENDANT)
            | $this->flag($this->hasAnyAttribute($domElement, self::EXPOSING_ATTRIBUTES) || $this->hasAriaAttribute($domElement), self::EXPOSED_DESCENDANT)
            | $this->flag(\in_array($localName, self::POINTER_TAGS, true) || $this->hasAnyAttribute($domElement, self::POINTER_ATTRIBUTES), self::POINTER_DESCENDANT);
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
                $this->asciiLowercase($this->ownInheritedValue($element, SvgAttribute::Fill->value, $fill)),
                $this->asciiLowercase($this->ownInheritedValue($element, SvgAttribute::Stroke->value, $stroke)),
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
        return \in_array($domElement->localName, self::RENDERED_TAGS, true)
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
        if (self::NONE === $this->asciiLowercase(trim($domElement->getAttribute(SvgAttribute::Display->value)))) {
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
        return $this->isZeroNumber(trim($domElement->getAttribute(SvgAttribute::Opacity->value)))
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
        return \in_array($domElement->localName, self::SHAPE_TAGS, true)
            && [self::NONE, self::NONE] === $paint
            && 0 === ($ancestry & self::KEPT_WHEN_UNPAINTED);
    }

    /**
     * @param \DOMElement                 $domElement the element whose children to check
     * @param \Closure(\DOMElement): bool $matches    the test for a child element
     */
    private function hasChild(\DOMElement $domElement, \Closure $matches): bool
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement && $matches($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isAnimation(\DOMElement $domElement): bool
    {
        return \in_array($this->asciiLowercase((string) $domElement->localName), self::ANIMATION_TAGS, true);
    }

    /**
     * @param \DOMElement  $domElement the element to check
     * @param list<string> $names      the names of attributes without a namespace
     */
    private function hasAnyAttribute(\DOMElement $domElement, array $names): bool
    {
        foreach ($names as $name) {
            if ($domElement->hasAttribute($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasXmlId(\DOMElement $domElement): bool
    {
        return $domElement->hasAttributeNS(SvgNamespace::Xml->value, SvgAttribute::Id->value);
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
