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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Presentation\InheritanceTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MoveCommonAttributesToGroups implements PassInterface
{
    use AnimationTrait;
    use AsciiCaseTrait;
    use ElementTrait;
    use IdReferenceTrait;
    use InheritanceTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    private const string XPATH_GROUP_ELEMENTS = '//svg:g';

    private const string FONT_PREFIX = 'font';

    private const string ALL_PROPERTY = 'all';

    private const string PROPERTY_SEPARATOR = '-';

    private const string ALTERNATIVE_SEPARATOR = '|';

    private const int MINIMUM_CHILDREN = 2;

    private const array DESCRIPTIVE_ELEMENTS = [
        Element::Desc->value,
        Element::Metadata->value,
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
        $groups = $domXPath->query(self::XPATH_GROUP_ELEMENTS);
        $attributeNames = array_diff(Attribute::inheritedNames(), [Attribute::Color->value], array_keys($this->animatedAttributeNames($domDocument)));
        $referencedIds = $this->referencedIds($domDocument);

        foreach (array_reverse(iterator_to_array($groups, false)) as $domElement) {
            $children = $this->movableChildren($domElement, $referencedIds);

            if (\count($children) < self::MINIMUM_CHILDREN || $this->isForeignContent($domElement)) {
                continue;
            }

            foreach ($attributeNames as $attributeName) {
                $this->moveAttribute($domElement, $children, $attributeName);
            }
        }
    }

    /**
     * @param \DOMElement         $domElement    the group element
     * @param array<string, true> $referencedIds the ids referenced in the document
     *
     * @return list<\DOMElement> the element children that inherit from the group, or none if one of them cannot lose
     *                           its own attributes
     */
    private function movableChildren(\DOMElement $domElement, array $referencedIds): array
    {
        $children = [];

        foreach ($domElement->childNodes as $child) {
            if (!$child instanceof \DOMElement || \in_array($child->localName, self::DESCRIPTIVE_ELEMENTS, true)) {
                continue;
            }

            if (!$this->isSvgElement($child)
                || $this->isAnimationElement($child)
                || $this->isReferencedElement($child, $referencedIds)
            ) {
                return [];
            }

            $children[] = $child;
        }

        return $children;
    }

    /**
     * @param \DOMElement                 $domElement    the group element
     * @param non-empty-list<\DOMElement> $children      the element children of the group
     * @param string                      $attributeName the attribute to move
     */
    private function moveAttribute(\DOMElement $domElement, array $children, string $attributeName): void
    {
        [$firstChild] = $children;
        $value = $firstChild->getAttribute($attributeName);

        if (!$this->isMovableValue($value)
            || !$this->groupAccepts($domElement, $attributeName, $value)
            || !$this->childrenShare($children, $attributeName, $value)
        ) {
            return;
        }

        $domElement->setAttribute($attributeName, $value);

        foreach ($children as $child) {
            $child->removeAttribute($attributeName);
        }
    }

    /**
     * @param string $value the attribute value
     */
    private function isMovableValue(string $value): bool
    {
        return '' !== trim($value)
            && !$this->isInheritKeyword($value)
            && 1 !== preg_match(self::FONT_RELATIVE_UNIT_REGEX, $value);
    }

    /**
     * @param \DOMElement $domElement    the group element
     * @param string      $attributeName the attribute to move
     * @param string      $value         the attribute value
     */
    private function groupAccepts(\DOMElement $domElement, string $attributeName, string $value): bool
    {
        if ($this->inlineStyleDeclares($domElement, $this->propertyPattern($attributeName))) {
            return false;
        }

        if ($domElement->hasAttribute($attributeName)) {
            return $value === $domElement->getAttribute($attributeName)
                && !\in_array($attributeName, self::PARENT_RELATIVE_ATTRIBUTES, true);
        }

        return !str_starts_with($attributeName, self::FONT_PREFIX) || !$this->hasFontRelativeValue($domElement);
    }

    /**
     * @param list<\DOMElement> $children      the element children of the group
     * @param string            $attributeName the attribute to move
     * @param string            $value         the attribute value of the first child
     */
    private function childrenShare(array $children, string $attributeName, string $value): bool
    {
        $usesCurrentColor = str_contains($this->asciiLowercase($value), self::CURRENT_COLOR);

        foreach ($children as $child) {
            if ($value !== $child->getAttribute($attributeName)
                || $this->inlineStyleDeclares($child, $this->propertyPattern($attributeName))
                || ($usesCurrentColor && $this->setsColor($child))
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function setsColor(\DOMElement $domElement): bool
    {
        return $domElement->hasAttribute(Attribute::Color->value)
            || $this->inlineStyleDeclares($domElement, $this->propertyPattern(Attribute::Color->value));
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasFontRelativeValue(\DOMElement $domElement): bool
    {
        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if (1 === preg_match(self::FONT_RELATIVE_UNIT_REGEX, $attribute->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $attributeName the attribute name
     *
     * @return string the property, its shorthand and `all` as regular expression alternatives
     */
    private function propertyPattern(string $attributeName): string
    {
        return implode(self::ALTERNATIVE_SEPARATOR, [
            $attributeName,
            explode(self::PROPERTY_SEPARATOR, $attributeName)[0],
            self::ALL_PROPERTY,
        ]);
    }
}
