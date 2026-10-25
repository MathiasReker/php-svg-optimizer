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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberListTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConvertShapesToPaths implements RevertIfLargerPassInterface
{
    use AnimationTrait;
    use ElementRenameTrait;
    use ElementTrait;
    use NumberListTrait;
    use NumberLiteralTrait;
    use PathDataSerializerTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    private const array GEOMETRY_ATTRIBUTES = [
        'rect' => ['x', 'y', 'width', 'height'],
        'line' => ['x1', 'y1', 'x2', 'y2'],
        'polyline' => ['points'],
        'polygon' => ['points'],
    ];

    private const array PATH_REFERENCING_ELEMENTS = [Element::TextPath->value, Element::Mpath->value];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument) || $this->hasScriptOrEventHandler($domDocument) || [] !== $this->elementsByLocalName($domDocument, Element::Marker->value)) {
            return;
        }

        $referencedIds = $this->animationTargetIds($domDocument) + $this->pathReferenceIds($domDocument);

        foreach (self::GEOMETRY_ATTRIBUTES as $tag => $attributes) {
            foreach ($this->elementsByLocalName($domDocument, $tag) as $domElement) {
                if (!$this->isReferenced($domElement, $referencedIds)) {
                    $this->convert($domDocument, $domElement, $tag, $attributes);
                }
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<string, true> the ids referenced by a `<textPath>` or `<mpath>`, which may treat a path differently
     */
    private function pathReferenceIds(\DOMDocument $domDocument): array
    {
        $ids = [];

        foreach (self::PATH_REFERENCING_ELEMENTS as $tag) {
            foreach ($this->elementsByLocalName($domDocument, $tag) as $domElement) {
                $ids += $this->hrefTargetIds($domElement);
            }
        }

        return $ids;
    }

    /**
     * @param \DOMElement $domElement the referencing element
     *
     * @return array<string, true> the ids its `href` and `xlink:href` point to
     */
    private function hrefTargetIds(\DOMElement $domElement): array
    {
        $ids = [];

        foreach ([
            $domElement->getAttribute(Attribute::Href->value),
            $domElement->getAttributeNS(NamespaceUri::Xlink->value, Attribute::Href->value),
        ] as $href) {
            $href = trim($href);

            if (str_starts_with($href, '#')) {
                $ids[mb_substr($href, 1)] = true;
            }
        }

        return $ids;
    }

    /**
     * @param \DOMElement         $domElement    the shape element
     * @param array<string, true> $referencedIds the ids that must keep their element
     */
    private function isReferenced(\DOMElement $domElement, array $referencedIds): bool
    {
        foreach ([
            $domElement->getAttribute(Attribute::Id->value),
            $domElement->getAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value),
        ] as $id) {
            if ('' !== $id && ($referencedIds[$id] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement  $domElement the shape element
     * @param string       $tag        the shape name
     * @param list<string> $attributes the geometry attributes that the path data replaces
     */
    private function convert(\DOMDocument $domDocument, \DOMElement $domElement, string $tag, array $attributes): void
    {
        if (!$this->isSvgElement($domElement)
            || $this->hasElementChildren($domElement)
            || $domElement->hasAttribute(Attribute::Style->value)
        ) {
            return;
        }

        $segments = match ($tag) {
            Element::Rect->value => $this->rectSegments($domElement),
            Element::Line->value => $this->lineSegments($domElement),
            default => $this->pointsSegments($domElement, Element::Polygon->value === $tag),
        };

        if ([] === $segments) {
            return;
        }

        foreach ($attributes as $attribute) {
            $domElement->removeAttribute($attribute);
        }

        $domElement->setAttribute(Attribute::D->value, $this->serializePathData($segments));
        $this->renameElement($domDocument, $domElement, Element::Path->value);
    }

    /**
     * @param \DOMElement $domElement the rect element
     *
     * @return list<array{string, list<string>}>
     */
    private function rectSegments(\DOMElement $domElement): array
    {
        if ($domElement->hasAttribute(Attribute::Rx->value) || $domElement->hasAttribute(Attribute::Ry->value)) {
            return [];
        }

        $values = $this->numbers($domElement, self::GEOMETRY_ATTRIBUTES['rect']);

        if ([] === $values || !$this->isPositiveNumber($values[2]) || !$this->isPositiveNumber($values[3])) {
            return [];
        }

        [$x, $y, $width, $height] = $values;

        return [['M', [$x, $y]], ['h', [$width]], ['v', [$height]], ['h', ['-' . $width]], ['z', []]];
    }

    /**
     * @param \DOMElement $domElement the line element
     *
     * @return list<array{string, list<string>}>
     */
    private function lineSegments(\DOMElement $domElement): array
    {
        $values = $this->numbers($domElement, self::GEOMETRY_ATTRIBUTES['line']);

        if ([] === $values) {
            return [];
        }

        return [['M', [$values[0], $values[1]]], ['L', [$values[2], $values[3]]]];
    }

    /**
     * @param \DOMElement $domElement the polyline or polygon element
     * @param bool        $close      whether to close the path
     *
     * @return list<array{string, list<string>}>
     */
    private function pointsSegments(\DOMElement $domElement, bool $close): array
    {
        $coordinates = $this->parseNumberList($domElement->getAttribute(Attribute::Points->value), true, true);

        if ([] === $coordinates || 0 !== \count($coordinates) % 2) {
            return [];
        }

        return $close ? [['M', $coordinates], ['z', []]] : [['M', $coordinates]];
    }

    /**
     * @param \DOMElement  $domElement the element
     * @param list<string> $attributes the attributes to read, defaulting to zero when absent
     *
     * @return list<string> the minified values, or an empty list if any value is not a plain number
     */
    private function numbers(\DOMElement $domElement, array $attributes): array
    {
        $values = [];

        foreach ($attributes as $attribute) {
            $value = $domElement->hasAttribute($attribute) ? $domElement->getAttribute($attribute) : '0';

            if (!$this->isPlainNumber($value)) {
                return [];
            }

            $values[] = $this->minifyNumber(trim($value));
        }

        return $values;
    }
}
