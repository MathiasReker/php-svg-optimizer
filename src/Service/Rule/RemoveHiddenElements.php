<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgOptimizerRuleInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\IdReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ParentValueTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveHiddenElements implements SvgOptimizerRuleInterface
{
    use AsciiCaseTrait;
    use ElementTrait;
    use IdReferenceTrait;
    use NumberFormatterTrait;
    use ParentValueTrait;

    private const array RENDERED_TAGS = [
        'a', 'circle', 'ellipse', 'g', 'image', 'line', 'path', 'polygon', 'polyline', 'rect', 'svg', 'text', 'use',
    ];

    private const array SHAPE_TAGS = ['circle', 'ellipse', 'line', 'path', 'polygon', 'polyline', 'rect'];

    private const string NONE = 'none';

    private const string STYLE_PROPERTIES = 'display|opacity|fill|stroke|visibility|marker(?:-[a-z]+)?|pointer-events|cursor|filter|mask(?:-[a-z]+)?|clip(?:-path)?|all';

    private const string ANIMATION_TEST = 'contains("|animate|set|animatecolor|animatemotion|animatetransform|discard|",'
        . ' concat("|", translate(local-name(), "ACDEIMNORST", "acdeimnorst"), "|"))';

    private const string EVENT_ATTRIBUTE_TEST = '@*[starts-with(translate(local-name(), "ON", "on"), "on")]';

    private const string XPATH_BLOCKED = 'boolean(ancestor::*[contains("|defs|clipPath|mask|pattern|marker|symbol|switch'
        . '|text|tspan|textPath|linearGradient|radialGradient|filter|font|glyph|missing-glyph|",'
        . ' concat("|", local-name(), "|"))]'
        . ' or descendant-or-self::*[@id or @xml:id or local-name() = "script" or local-name() = "foreignObject"'
        . ' or (namespace-uri() != "" and namespace-uri() != "http://www.w3.org/2000/svg")'
        . ' or ' . self::EVENT_ATTRIBUTE_TEST . ' or ' . self::ANIMATION_TEST . '])';

    private const string XPATH_INVISIBLE_BLOCKED = 'boolean(ancestor-or-self::*[local-name() = "a" or @cursor'
        . ' or @pointer-events or @filter or @mask or @clip-path]'
        . ' or descendant-or-self::*[@role or @tabindex or @*[starts-with(local-name(), "aria-")]])';

    private const string XPATH_TRANSPARENT_BLOCKED = 'boolean(descendant-or-self::*'
        . '[contains("|a|desc|text|tspan|textPath|", concat("|", local-name(), "|"))])';

    private const string XPATH_ANIMATION_PARENTS = '//*[*[' . self::ANIMATION_TEST . ']]';

    private const string XPATH_TITLE_PARENTS = '//*[*[local-name() = "title"]]';

    private const string XPATH_SCRIPTED = 'boolean(//*[local-name() = "script"] or //' . self::EVENT_ATTRIBUTE_TEST . ')';

    private const string XPATH_UNPAINTED_BLOCKED = 'boolean(ancestor-or-self::*[@id or @xml:id or @marker'
        . ' or @marker-start or @marker-mid or @marker-end])';

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleElement($domDocument) || $this->hasSiblingSensitiveStyle($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);
        $referencedIds = $this->collectReferencedIds($domDocument);
        $animationParents = $this->elementIds($domXPath, self::XPATH_ANIMATION_PARENTS);
        $transparencyKeepers = $this->elementIds($domXPath, self::XPATH_TITLE_PARENTS);

        if (true === $domXPath->evaluate(self::XPATH_SCRIPTED) && $domDocument->documentElement instanceof \DOMElement) {
            $transparencyKeepers[spl_object_id($domDocument->documentElement)] = $domDocument->documentElement;
        }

        foreach (iterator_to_array($domDocument->getElementsByTagName('*'), false) as $domElement) {
            $parent = $domElement->parentNode;

            if ($parent instanceof \DOMElement
                && $this->isCandidate($domElement, $domXPath, $referencedIds, $animationParents)
                && $this->isHidden($domElement, $domXPath, $transparencyKeepers)
            ) {
                $parent->removeChild($domElement);
            }
        }
    }

    /**
     * @param \DOMXPath $domXPath   the XPath object for querying the document
     * @param string    $expression the query for the elements
     *
     * @return array<int, \DOMElement> the matching elements, by object id
     */
    private function elementIds(\DOMXPath $domXPath, string $expression): array
    {
        $ids = [];

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query($expression);

        foreach ($domNodeList as $domElement) {
            $ids[spl_object_id($domElement)] = $domElement;
        }

        return $ids;
    }

    /**
     * @param \DOMElement             $domElement the element to start from
     * @param array<int, \DOMElement> $elementIds the elements to look for, by object id
     *
     * @return bool true if the element or an ancestor is one of the elements
     */
    private function hasAncestorOrSelfIn(\DOMElement $domElement, array $elementIds): bool
    {
        for ($node = $domElement; $node instanceof \DOMElement; $node = $node->parentNode) {
            if (\array_key_exists(spl_object_id($node), $elementIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMElement             $domElement       the element to check
     * @param \DOMXPath               $domXPath         the XPath object for querying the document
     * @param array<string, true>     $referencedIds    the ids referenced anywhere in the document
     * @param array<int, \DOMElement> $animationParents the elements with an animation child, which may target any descendant,
     *                                                  by object id
     */
    private function isCandidate(\DOMElement $domElement, \DOMXPath $domXPath, array $referencedIds, array $animationParents): bool
    {
        if (!\in_array($domElement->localName, self::RENDERED_TAGS, true)
            || $this->isForeignContent($domElement)
            || true === $domXPath->evaluate(self::XPATH_BLOCKED, $domElement, false)
            || $this->hasAncestorOrSelfIn($domElement, $animationParents)
        ) {
            return false;
        }

        for ($node = $domElement; $node instanceof \DOMElement; $node = $node->parentNode) {
            if ($this->isReferencedElement($node, $referencedIds) || $this->styleDeclares($node, self::STYLE_PROPERTIES)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement             $domElement          the element to check
     * @param \DOMXPath               $domXPath            the XPath object for querying the document
     * @param array<int, \DOMElement> $transparencyKeepers the elements whose transparent descendants are kept, because a title
     *                                                     shows a tooltip or a script may hit-test them, by object id
     */
    private function isHidden(\DOMElement $domElement, \DOMXPath $domXPath, array $transparencyKeepers): bool
    {
        if (self::NONE === $this->keyword($domElement->getAttribute(SvgAttribute::Display->value))) {
            return true;
        }

        if (true === $domXPath->evaluate(self::XPATH_INVISIBLE_BLOCKED, $domElement, false)) {
            return false;
        }

        return $this->isTransparent($domElement, $domXPath, $transparencyKeepers) || $this->isUnpainted($domElement, $domXPath);
    }

    /**
     * @param \DOMElement             $domElement          the element to check
     * @param \DOMXPath               $domXPath            the XPath object for querying the document
     * @param array<int, \DOMElement> $transparencyKeepers the elements whose transparent descendants are kept, by object id
     */
    private function isTransparent(\DOMElement $domElement, \DOMXPath $domXPath, array $transparencyKeepers): bool
    {
        $opacity = $domElement->getAttribute(SvgAttribute::Opacity->value);

        return $this->isZeroNumber(trim($opacity))
            && !$this->hasAncestorOrSelfIn($domElement, $transparencyKeepers)
            && true !== $domXPath->evaluate(self::XPATH_TRANSPARENT_BLOCKED, $domElement, false);
    }

    /**
     * @param \DOMElement $domElement the element to check
     * @param \DOMXPath   $domXPath   the XPath object for querying the document
     */
    private function isUnpainted(\DOMElement $domElement, \DOMXPath $domXPath): bool
    {
        return \in_array($domElement->localName, self::SHAPE_TAGS, true)
            && self::NONE === $this->inheritedKeyword($domElement, SvgAttribute::Fill->value, '')
            && self::NONE === $this->inheritedKeyword($domElement, SvgAttribute::Stroke->value, self::NONE)
            && true !== $domXPath->evaluate(self::XPATH_UNPAINTED_BLOCKED, $domElement, false);
    }

    /**
     * @param \DOMElement $domElement the element to start from
     * @param string      $attribute  the inherited presentation attribute
     * @param string      $initial    the keyword used when no element sets the attribute
     *
     * @return string the keyword set on the nearest element, or the initial keyword
     */
    private function inheritedKeyword(\DOMElement $domElement, string $attribute, string $initial): string
    {
        for ($node = $domElement; $node instanceof \DOMElement; $node = $node->parentNode) {
            $keyword = $this->keyword($node->getAttribute($attribute));

            if ($node->hasAttribute($attribute) && !$this->isInheritKeyword($keyword)) {
                return $keyword;
            }
        }

        return $initial;
    }

    /**
     * @param string $value the attribute value
     *
     * @return string the value without surrounding whitespace, in lowercase
     */
    private function keyword(string $value): string
    {
        return $this->asciiLowercase(trim($value));
    }
}
