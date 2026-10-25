<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass\Trait\Id;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\ClassSelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
trait FragmentTargetTrait
{
    use ClassSelectorTrait;
    use DeclarationTrait;
    use ElementTrait;

    private const string FRAGMENT_TARGET_ALWAYS_QUERY = '//*[local-name()="symbol" or local-name()="view"]';

    private const string FRAGMENT_TARGET_ALL_IDS_QUERY = '//*[@id or @xml:id]';

    private const string FRAGMENT_TARGET_STYLESHEET_QUERY = '//processing-instruction("xml-stylesheet")';

    private const string FRAGMENT_TARGET_STYLE_QUERY = '//*[local-name()="style"]';

    private const string FRAGMENT_TARGET_PSEUDO_CLASS_REGEX = '/:target\b/i';

    private const string FRAGMENT_TARGET_HIDING_CSS_REGEX = '/display\s*:\s*none/i';

    private const string FRAGMENT_TARGET_HIDDEN_STYLE_REGEX = '/(?:^|;)\s*display\s*:\s*none\s*(?:!\s*important\s*)?(?:;|$)/i';

    private const string FRAGMENT_TARGET_ZERO_LENGTH_REGEX = '/^\s*[+-]?(?:0+(?:\.0*)?|\.0+)(?:e[+-]?\d+)?[a-z%]*\s*$/i';

    private const string FRAGMENT_TARGET_NONE = 'none';

    private const array FRAGMENT_TARGET_ADDRESSABLE_ELEMENTS = ['g', 'svg', 'symbol'];

    private const array FRAGMENT_TARGET_GROUPING_ELEMENTS = ['a', 'defs', 'g', 'switch'];

    private const array FRAGMENT_TARGET_RENDERED_CONTAINERS = ['a', 'g', 'svg', 'switch'];

    private const array FRAGMENT_TARGET_RENDERED_LEAVES = [
        'circle', 'ellipse', 'foreignObject', 'image', 'line', 'path', 'polygon', 'polyline', 'rect', 'text', 'use',
    ];

    private const array FRAGMENT_TARGET_USABLE_ELEMENTS = [
        'a', 'circle', 'ellipse', 'foreignObject', 'g', 'image', 'line', 'path', 'polygon', 'polyline', 'rect', 'svg',
        'switch', 'symbol', 'text', 'use',
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return array<string, true> the ids that another document may address through `file.svg#id`
     */
    private function fragmentTargetIds(\DOMDocument $domDocument): array
    {
        $domXPath = new \DOMXPath($domDocument);

        if ($this->mayTargetEveryId($domXPath)) {
            return $this->idsOfNodes($this->queryNodes($domXPath, self::FRAGMENT_TARGET_ALL_IDS_QUERY));
        }

        $hidingClasses = $this->hidingClasses($domXPath);
        $targets = $this->idsOfNodes($this->queryNodes($domXPath, self::FRAGMENT_TARGET_ALWAYS_QUERY));

        foreach ($this->rootElements($domDocument) as $root) {
            $resource = $this->isResourceDocument($root, $hidingClasses);
            $targets += $this->fragmentTargetsWithin($root, $resource, $resource, $hidingClasses);
        }

        return $targets;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to read
     *
     * @return list<\DOMElement> the root element, if any
     */
    private function rootElements(\DOMDocument $domDocument): array
    {
        return array_filter([$domDocument->documentElement]);
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     *
     * @return bool true if a style sheet may show any element by its fragment, or cannot be seen
     */
    private function mayTargetEveryId(\DOMXPath $domXPath): bool
    {
        if ([] !== $this->queryNodes($domXPath, self::FRAGMENT_TARGET_STYLESHEET_QUERY)) {
            return true;
        }

        return $this->anyStyleMatches($domXPath, self::FRAGMENT_TARGET_PSEUDO_CLASS_REGEX);
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     *
     * @return array<string, true>|true the classes that style sheets hide elements through, or true if they may hide
     *                                  an element through any class
     */
    private function hidingClasses(\DOMXPath $domXPath): array|true
    {
        $classes = [];

        foreach ($this->queryNodes($domXPath, self::FRAGMENT_TARGET_STYLE_QUERY) as $style) {
            $styleClasses = $this->hidingClassesOf($style->textContent);

            if (true === $styleClasses) {
                return true;
            }

            $classes += $styleClasses;
        }

        return $classes;
    }

    /**
     * @param string $css the CSS content of a `<style>` element
     *
     * @return array<string, true>|true the classes that the style sheet hides elements through, or true if it may hide
     *                                  an element through any class
     */
    private function hidingClassesOf(string $css): array|true
    {
        if (1 !== preg_match(self::FRAGMENT_TARGET_HIDING_CSS_REGEX, $css)) {
            return [];
        }

        if (!$this->isPlainRuleList($css)) {
            return true;
        }

        preg_match_all(self::CSS_PLAIN_RULE_REGEX, $css, $matches, \PREG_SET_ORDER);
        $classes = [];

        foreach ($matches as $match) {
            if (1 !== preg_match(self::FRAGMENT_TARGET_HIDING_CSS_REGEX, $match[2])) {
                continue;
            }

            $ruleClasses = $this->classSelectorList($match[1]);

            if ([] === $ruleClasses) {
                return true;
            }

            $classes += array_fill_keys($ruleClasses, true);
        }

        return $classes;
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     * @param string    $regex    the pattern to look for
     *
     * @return bool true if the text of a `<style>` matches the pattern
     */
    private function anyStyleMatches(\DOMXPath $domXPath, string $regex): bool
    {
        foreach ($this->queryNodes($domXPath, self::FRAGMENT_TARGET_STYLE_QUERY) as $style) {
            if (1 === preg_match($regex, $style->textContent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     * @param string    $query    the expression to evaluate
     *
     * @return list<\DOMNode> the matching nodes
     */
    private function queryNodes(\DOMXPath $domXPath, string $query): array
    {
        $result = [];
        $nodes = $domXPath->query($query, null, false);

        foreach (false === $nodes ? [] : $nodes as $node) {
            if ($node instanceof \DOMNode) {
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * @param list<\DOMNode> $nodes the elements to collect the ids of
     *
     * @return array<string, true> the `id` and `xml:id` values of the elements
     */
    private function idsOfNodes(array $nodes): array
    {
        $ids = [];

        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $ids += $this->fragmentIdsOf($node);
            }
        }

        return $ids;
    }

    /**
     * @param \DOMElement $domElement the element to read
     *
     * @return array<string, true> the non-empty `id` and `xml:id` values of the element
     */
    private function fragmentIdsOf(\DOMElement $domElement): array
    {
        return array_fill_keys(array_diff([
            $domElement->getAttribute(Attribute::Id->value),
            $domElement->getAttributeNS(NamespaceUri::Xml->value, Attribute::Id->value),
        ], ['']), true);
    }

    /**
     * @param \DOMElement              $domElement    the parent of the elements to scan
     * @param bool                     $inArea        true if the children are not rendered where they are, such as inside `<defs>`
     * @param bool                     $resource      true if the document renders nothing itself
     * @param array<string, true>|true $hidingClasses the classes that hide an element, or true if any class may
     *
     * @return array<string, true> the ids of the children, and of their descendants, that may be addressed
     */
    private function fragmentTargetsWithin(\DOMElement $domElement, bool $inArea, bool $resource, array|true $hidingClasses): array
    {
        $targets = [];

        foreach ($this->svgChildElements($domElement) as $child) {
            $localName = $child->localName ?? '';

            if ($this->isFragmentTargetCandidate($localName, $inArea, $resource)) {
                $targets += $this->fragmentIdsOf($child);
            }

            $opensArea = Element::Defs->value === $localName || $this->mayHaveDisplayNone($child, $hidingClasses);

            if ($opensArea || ($inArea && \in_array($localName, self::FRAGMENT_TARGET_GROUPING_ELEMENTS, true))) {
                $targets += $this->fragmentTargetsWithin($child, true, $resource, $hidingClasses);
            }
        }

        return $targets;
    }

    /**
     * @param string $localName the local name of the element
     * @param bool   $inArea    true if the element is not rendered where it is
     * @param bool   $resource  true if the document renders nothing itself
     *
     * @return bool true if the element is a `<g>`, `<svg>` or `<symbol>`, or is not rendered where it is and can be used
     */
    private function isFragmentTargetCandidate(string $localName, bool $inArea, bool $resource): bool
    {
        if (\in_array($localName, self::FRAGMENT_TARGET_ADDRESSABLE_ELEMENTS, true)) {
            return true;
        }

        return $inArea && ($resource || \in_array($localName, self::FRAGMENT_TARGET_USABLE_ELEMENTS, true));
    }

    /**
     * @param \DOMElement              $domElement    the element to check
     * @param array<string, true>|true $hidingClasses the classes that hide an element, or true if any class may
     *
     * @return bool true if the element may have `display: none`
     */
    private function mayHaveDisplayNone(\DOMElement $domElement, array|true $hidingClasses): bool
    {
        if (self::FRAGMENT_TARGET_NONE === mb_strtolower(trim($domElement->getAttribute(Attribute::Display->value)))) {
            return true;
        }

        if (1 === preg_match(self::FRAGMENT_TARGET_HIDDEN_STYLE_REGEX, $domElement->getAttribute(Attribute::Style->value))) {
            return true;
        }

        if (true === $hidingClasses) {
            return $domElement->hasAttribute(Attribute::Class_->value);
        }

        return [] !== array_intersect_key(array_flip($this->classList($domElement)), $hidingClasses);
    }

    /**
     * @param \DOMElement              $domElement    the root element
     * @param array<string, true>|true $hidingClasses the classes that hide an element, or true if any class may
     *
     * @return bool true if the document renders nothing itself, such as an inline sprite or a library of paint servers
     */
    private function isResourceDocument(\DOMElement $domElement, array|true $hidingClasses): bool
    {
        return $this->mayHaveDisplayNone($domElement, $hidingClasses)
            || 1 === preg_match(self::FRAGMENT_TARGET_ZERO_LENGTH_REGEX, $domElement->getAttribute(Attribute::Width->value))
            || 1 === preg_match(self::FRAGMENT_TARGET_ZERO_LENGTH_REGEX, $domElement->getAttribute(Attribute::Height->value))
            || !$this->rendersContent($domElement, $hidingClasses);
    }

    /**
     * @param \DOMElement              $domElement    the container to check
     * @param array<string, true>|true $hidingClasses the classes that hide an element, or true if any class may
     *
     * @return bool true if a child of the container, or of a rendered container inside it, renders
     */
    private function rendersContent(\DOMElement $domElement, array|true $hidingClasses): bool
    {
        foreach ($this->svgChildElements($domElement) as $child) {
            if ($this->mayHaveDisplayNone($child, $hidingClasses)) {
                continue;
            }

            $localName = $child->localName ?? '';

            if (\in_array($localName, self::FRAGMENT_TARGET_RENDERED_LEAVES, true)
                || (\in_array($localName, self::FRAGMENT_TARGET_RENDERED_CONTAINERS, true) && $this->rendersContent($child, $hidingClasses))) {
                return true;
            }
        }

        return false;
    }
}
