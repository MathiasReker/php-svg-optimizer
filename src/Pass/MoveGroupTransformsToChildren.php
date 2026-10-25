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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Transform\TransformListTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MoveGroupTransformsToChildren implements RevertIfLargerPassInterface
{
    use AnimationTrait;
    use ElementTrait;
    use IdReferenceTrait;
    use IdTrait;
    use InheritanceTrait;
    use ScriptingTrait;
    use StyleSheetTrait;
    use TransformListTrait;

    private const string XPATH_TRANSFORMED_GROUPS = '//svg:g[@transform]';

    private const string GROUP_BLOCKING_PROPERTIES = '|clip(?:-path)?|mask(?:-[a-z]+)?|filter';

    private const array GROUP_BLOCKING_ATTRIBUTES = [
        Attribute::ClipPath->value,
        Attribute::Filter->value,
        Attribute::Mask->value,
    ];

    private const array TRANSFORMABLE_ELEMENTS = [
        Element::Circle->value,
        Element::Ellipse->value,
        Element::Line->value,
        Element::Path->value,
        Element::Polygon->value,
        Element::Polyline->value,
        Element::Rect->value,
        Element::Text->value,
        Element::Use->value,
    ];

    private const array DESCRIPTIVE_ELEMENTS = [
        Element::Desc->value,
        Element::Title->value,
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument) || $this->hasScriptOrEventHandler($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);
        $domXPath->registerNamespace(NamespaceUri::Svg->prefix(), NamespaceUri::Svg->value);

        /** @var \DOMNodeList<\DOMElement> $groups */
        $groups = $domXPath->query(self::XPATH_TRANSFORMED_GROUPS);
        $referencedIds = $this->referencedIds($domDocument);

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
            || [] === $this->parseTransformList($domElement->getAttribute(Attribute::Transform->value))
            || $this->inlineStyleDeclares($domElement, self::TRANSFORM_PROPERTIES . self::GROUP_BLOCKING_PROPERTIES)
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
            if (!$child instanceof \DOMElement || \in_array($child->localName, self::DESCRIPTIVE_ELEMENTS, true)) {
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
            && \in_array($child->localName, self::TRANSFORMABLE_ELEMENTS, true)
            && $this->isEmptyOrValidTransformList($child->getAttribute(Attribute::Transform->value))
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
        $groupTransform = $domElement->getAttribute(Attribute::Transform->value);

        foreach ($children as $child) {
            $child->setAttribute(
                Attribute::Transform->value,
                $this->combineTransforms($groupTransform, $child->getAttribute(Attribute::Transform->value)),
            );
        }

        $domElement->removeAttribute(Attribute::Transform->value);
    }
}
