<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
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
final readonly class FlattenGroups implements RevertIfLargerPassInterface
{
    use AnimationTrait;
    use AsciiCaseTrait;
    use DeclarationTrait;
    use IdReferenceTrait;
    use InheritanceTrait;
    use ScriptingTrait;
    use StyleSheetTrait;
    use TransformListTrait;

    private const string XPATH_GROUP_ELEMENTS = '//svg:g';

    private const string XPATH_TRANSFORM_BLOCKING_ELEMENTS = '//svg:g/svg:svg'
        . ' | //*[svg:animateMotion or *[normalize-space(@attributeName) = "transform"]]';

    private const array NEVER_RENDERED_ELEMENTS = [
        Element::ClipPath->value,
        Element::Defs->value,
        Element::Filter->value,
        Element::LinearGradient->value,
        Element::Marker->value,
        Element::Mask->value,
        Element::Pattern->value,
        Element::RadialGradient->value,
        Element::Symbol->value,
    ];

    private const array ANCESTOR_INHERITING_ELEMENTS = [
        Element::ClipPath->value,
        Element::Filter->value,
        Element::LinearGradient->value,
        Element::Marker->value,
        Element::Mask->value,
        Element::Pattern->value,
        Element::RadialGradient->value,
    ];

    private const array CHILD_SENSITIVE_PARENT_ELEMENTS = [
        Element::ClipPath->value,
        Element::Switch->value,
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

        /** @var \DOMNodeList<\DOMElement> $domElementList */
        $domElementList = $domXPath->query(self::XPATH_GROUP_ELEMENTS);
        $transformBlockingElements = $this->transformBlockingElements($domDocument, $domXPath);
        $referencedIds = $this->referencedIds($domDocument);

        foreach ($domElementList as $domElement) {
            $parentNode = $domElement->parentNode;

            if (!$parentNode instanceof \DOMElement
                || !$this->canFlatten($domElement, $parentNode, $transformBlockingElements, $referencedIds)
            ) {
                continue;
            }

            $this->applyGroupAttributesToChildren($domElement);
            $this->flattenGroup($domElement, $parentNode);
        }
    }

    /**
     * @param \DOMElement             $domElement                the group element
     * @param \DOMElement             $parentNode                the parent of the group element
     * @param array<int, \DOMElement> $transformBlockingElements the elements that a group transform cannot move onto, by object id
     * @param array<string, true>     $referencedIds             the ids referenced in the document
     */
    private function canFlatten(
        \DOMElement $domElement,
        \DOMElement $parentNode,
        array $transformBlockingElements,
        array $referencedIds,
    ): bool {
        if (\in_array($parentNode->localName, self::CHILD_SENSITIVE_PARENT_ELEMENTS, true)) {
            return false;
        }

        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if (!$this->isTransferableAttribute($attribute)) {
                return false;
            }
        }

        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement && $this->dependsOnGroup($domElement, $child, $transformBlockingElements)) {
                return false;
            }
        }

        return !$domElement->hasAttributes()
            || !$this->hasReferencedElement($domElement, $referencedIds, self::ANCESTOR_INHERITING_ELEMENTS);
    }

    /**
     * @param \DOMAttr $domAttr the group attribute
     */
    private function isTransferableAttribute(\DOMAttr $domAttr): bool
    {
        if (Attribute::Transform->value === $domAttr->nodeName) {
            return $this->isEmptyOrValidTransformList($domAttr->value);
        }

        return \in_array($domAttr->nodeName, Attribute::inheritedNames(), true);
    }

    /**
     * @param \DOMElement             $domElement                the group element
     * @param \DOMElement             $child                     the child element
     * @param array<int, \DOMElement> $transformBlockingElements the elements that a group transform cannot move onto, by object id
     */
    private function dependsOnGroup(\DOMElement $domElement, \DOMElement $child, array $transformBlockingElements): bool
    {
        if (\in_array($this->asciiLowercase($child->localName ?? ''), [...array_map(mb_strtolower(...), Element::animationNames()), ...self::DESCRIPTIVE_ELEMENTS], true)
            || $this->usesInheritInStyleOrAnimation($child)
        ) {
            return true;
        }

        if ('' !== trim($domElement->getAttribute(Attribute::Transform->value))
            && (\array_key_exists(spl_object_id($child), $transformBlockingElements) || !$this->acceptsGroupTransform($child))
        ) {
            return true;
        }

        return $this->dependsOnParentTransform($domElement, $child)
            || $this->dependsOnParentFont($domElement, $child)
            || $this->mayFallBackToParentValue($domElement, $child);
    }

    /**
     * @param \DOMElement $domElement the child element
     *
     * @return bool true if the group transform can be combined with the transform of the element, or is not applied to it
     */
    private function acceptsGroupTransform(\DOMElement $domElement): bool
    {
        return \in_array($domElement->localName, self::NEVER_RENDERED_ELEMENTS, true)
            || $this->isEmptyOrValidTransformList($domElement->getAttribute(Attribute::Transform->value));
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     * @param \DOMXPath    $domXPath    the XPath object with the svg prefix registered
     *
     * @return array<int, \DOMElement> the elements that a group transform cannot move onto, by object id
     */
    private function transformBlockingElements(\DOMDocument $domDocument, \DOMXPath $domXPath): array
    {
        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_TRANSFORM_BLOCKING_ELEMENTS);

        $elements = [];

        foreach ([...iterator_to_array($domNodeList, false), ...$this->animationTargets($domDocument)] as $element) {
            $elements[spl_object_id($element)] = $element;
        }

        return $elements;
    }

    /**
     * @param \DOMElement $domElement the group element
     */
    private function applyGroupAttributesToChildren(\DOMElement $domElement): void
    {
        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->applyAttributesToChild($domElement, $child);
            }
        }
    }

    /**
     * @param \DOMElement $parent the parent element
     * @param \DOMElement $child  the child element
     */
    private function applyAttributesToChild(\DOMElement $parent, \DOMElement $child): void
    {
        /** @var \DOMAttr $attribute */
        foreach ($parent->attributes as $attribute) {
            if (Attribute::Transform->value === $attribute->nodeName) {
                if (!\in_array($child->localName, self::NEVER_RENDERED_ELEMENTS, true)) {
                    $this->prependTransform($child, $attribute->value);
                }

                continue;
            }

            $this->setInheritedAttribute($child, $attribute);
        }
    }

    /**
     * @param \DOMElement $domElement the element to modify
     * @param \DOMAttr    $domAttr    the inherited attribute to set
     */
    private function setInheritedAttribute(\DOMElement $domElement, \DOMAttr $domAttr): void
    {
        if ($this->styleDeclaresSameValue($domElement, $domAttr)) {
            return;
        }

        $ownValue = $domElement->getAttribute($domAttr->nodeName);

        if (!$domElement->hasAttribute($domAttr->nodeName) || $this->isInheritKeyword($ownValue)) {
            $domElement->setAttribute($domAttr->nodeName, $domAttr->value);
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param \DOMAttr    $domAttr    the inherited attribute
     *
     * @return bool true if the `style` of the element declares the property with the same value, which wins over the
     *              attribute
     */
    private function styleDeclaresSameValue(\DOMElement $domElement, \DOMAttr $domAttr): bool
    {
        $style = $domElement->getAttribute(Attribute::Style->value);

        return $this->isSplittable($style) && \in_array(
            [$domAttr->nodeName, trim($domAttr->value)],
            array_map(
                fn (array $declaration): array => [$this->asciiLowercase($declaration[0]), $declaration[1]],
                $this->parseDeclarations($style),
            ),
            true,
        );
    }

    /**
     * @param \DOMElement $domElement the child element
     * @param string      $transform  the group transform, applied before the child's own transform
     */
    private function prependTransform(\DOMElement $domElement, string $transform): void
    {
        $newTransform = $this->combineTransforms($transform, $domElement->getAttribute(Attribute::Transform->value));

        if ('' !== $newTransform) {
            $domElement->setAttribute(Attribute::Transform->value, $newTransform);
        }
    }

    /**
     * @param \DOMElement $domElement the group element to flatten
     * @param \DOMElement $parentNode the parent element
     */
    private function flattenGroup(\DOMElement $domElement, \DOMElement $parentNode): void
    {
        $children = iterator_to_array($domElement->childNodes, false);
        foreach ($children as $child) {
            $parentNode->insertBefore($child, $domElement);
        }

        $parentNode->removeChild($domElement);
    }
}
