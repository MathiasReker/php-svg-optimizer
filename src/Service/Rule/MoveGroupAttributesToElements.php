<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\RevertIfLargerPassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ParentValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TransformListTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MoveGroupAttributesToElements implements RevertIfLargerPassInterface
{
    use DocumentScriptTrait;
    use ElementTrait;
    use IdReferenceTrait;
    use ParentValueTrait;
    use TransformListTrait;

    private const string XPATH_TRANSFORMED_GROUPS = '//svg:g[@transform]';

    private const string GROUP_BLOCKING_PROPERTIES = '|clip(?:-path)?|mask(?:-[a-z]+)?|filter';

    private const array GROUP_BLOCKING_ATTRIBUTES = [
        SvgAttribute::ClipPath->value,
        SvgAttribute::Filter->value,
        SvgAttribute::Mask->value,
    ];

    private const array TRANSFORMABLE_TAGS = [
        SvgTag::Circle->value,
        SvgTag::Ellipse->value,
        SvgTag::Line->value,
        SvgTag::Path->value,
        SvgTag::Polygon->value,
        SvgTag::Polyline->value,
        SvgTag::Rect->value,
        SvgTag::Text->value,
        SvgTag::Use->value,
    ];

    private const array IGNORED_TAGS = [
        SvgTag::Desc->value,
        SvgTag::Title->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument) || $this->hasScriptOrEventHandler($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(SvgNamespace::Svg->prefix(), SvgNamespace::Svg->value);

        /** @var \DOMNodeList<\DOMElement> $groups */
        $groups = $domXPath->query(self::XPATH_TRANSFORMED_GROUPS);
        $referencedIds = $this->collectReferencedIds($domDocument);

        foreach ($groups as $group) {
            if (!$this->isMovableGroup($group)) {
                continue;
            }

            $children = $this->transformableChildren($group, $referencedIds);

            if ([] !== $children) {
                $this->moveTransform($group, $children);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the group element
     */
    private function isMovableGroup(\DOMElement $domElement): bool
    {
        if ($this->hasId($domElement)
            || $this->isForeignContent($domElement)
            || [] === $this->parseTransformList($domElement->getAttribute(SvgAttribute::Transform->value))
            || $this->styleDeclares($domElement, self::TRANSFORM_PROPERTIES . self::GROUP_BLOCKING_PROPERTIES)
        ) {
            return false;
        }

        foreach ([...self::GROUP_BLOCKING_ATTRIBUTES, ...self::TRANSFORM_ORIGIN_ATTRIBUTES] as $attributeName) {
            if ($domElement->hasAttribute($attributeName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement         $domElement    the group element
     * @param array<string, true> $referencedIds the ids referenced in the document
     *
     * @return list<\DOMElement> the element children that take the transform, or none if one of them cannot
     */
    private function transformableChildren(\DOMElement $domElement, array $referencedIds): array
    {
        $children = [];

        foreach ($domElement->childNodes as $child) {
            if (!$child instanceof \DOMElement || \in_array($child->localName, self::IGNORED_TAGS, true)) {
                continue;
            }

            if (!$this->isTransformableChild($domElement, $child, $referencedIds)) {
                return [];
            }

            $children[] = $child;
        }

        return $children;
    }

    /**
     * @param \DOMElement         $group         the group element
     * @param \DOMElement         $child         the child element
     * @param array<string, true> $referencedIds the ids referenced in the document
     */
    private function isTransformableChild(\DOMElement $group, \DOMElement $child, array $referencedIds): bool
    {
        return $this->isSvgElement($child)
            && \in_array($child->localName, self::TRANSFORMABLE_TAGS, true)
            && $this->isEmptyOrValidTransform($child->getAttribute(SvgAttribute::Transform->value))
            && !$this->isReferencedElement($child, $referencedIds)
            && !$this->dependsOnParentTransform($group, $child)
            && !$this->hasAnimationChild($child);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasAnimationChild(\DOMElement $domElement): bool
    {
        foreach ($domElement->childNodes as $childNode) {
            if ($childNode instanceof \DOMElement
                && $this->isAnimationElement($childNode)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement       $domElement the group element
     * @param list<\DOMElement> $children   the element children of the group
     */
    private function moveTransform(\DOMElement $domElement, array $children): void
    {
        $groupTransform = $domElement->getAttribute(SvgAttribute::Transform->value);

        foreach ($children as $child) {
            $child->setAttribute(
                SvgAttribute::Transform->value,
                $this->combineTransforms($groupTransform, $child->getAttribute(SvgAttribute::Transform->value)),
            );
        }

        $domElement->removeAttribute(SvgAttribute::Transform->value);
    }
}
