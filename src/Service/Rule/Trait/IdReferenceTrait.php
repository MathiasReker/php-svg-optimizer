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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * Collects the ids an SVG document references internally. The collection over-counts on purpose (a hex color such as
 * `#fff` also counts as a reference to the id `fff`), so callers only ever keep too much, never too little.
 *
 * @no-named-arguments
 */
trait IdReferenceTrait
{
    /**
     * A `#` followed by everything up to a delimiter, which covers `url(#id)`, `href="#id"` and CSS id selectors.
     */
    private const string FRAGMENT_REFERENCE_REGEX = '/#((?:\\\.|[^\s#,;\'"()\[\]{}<>\\\])+)/';

    /**
     * Splits before every character other than a word character or `-`, so `a.b:hover` yields the candidate ids `a`,
     * `a.b` and `a.b:hover`.
     */
    private const string ID_PART_REGEX = '/[^\w-]?[\w-]*/';

    private const string ID_LIST_ITEM_REGEX = '/\S+/';

    private const string SMIL_ITEM_SEPARATOR = ';';

    /**
     * Attributes holding a whitespace-separated list of ids.
     */
    private const array ID_LIST_ATTRIBUTES = [
        'aria-activedescendant', 'aria-controls', 'aria-describedby', 'aria-details', 'aria-errormessage',
        'aria-flowto', 'aria-labelledby', 'aria-owns', 'for', 'form', 'headers', 'list',
    ];

    /**
     * Collects every id referenced by an attribute or a `<style>` element.
     *
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return array<string, true> the referenced ids
     */
    private function collectReferencedIds(\DOMDocument $domDocument): array
    {
        $referencedIds = [];

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            foreach ($domElement->attributes as $attribute) {
                $referencedIds += $this->attributeReferences($attribute);
            }

            if (SvgTag::Style->value === $domElement->localName) {
                $referencedIds += $this->fragmentReferences($domElement->textContent);
            }
        }

        return $referencedIds;
    }

    /**
     * Collects the ids that are referenced from rendered content, directly or through definitions that are themselves
     * referenced. A reference made only from inside an unused definition, such as the last gradient of an unused
     * chain of linked gradients, does not count.
     *
     * @param \DOMDocument $domDocument the DOM document to scan
     * @param list<string> $definitions the elements that only render when referenced
     * @param list<string> $alwaysLive  the elements whose content counts as referenced, such as sprite symbols
     *
     * @return array<string, true> the referenced ids
     */
    private function collectLiveReferencedIds(\DOMDocument $domDocument, array $definitions, array $alwaysLive): array
    {
        $elementsById = [];
        $live = [];

        foreach ($domDocument->getElementsByTagName('*') as $domElement) {
            $elementsById[$domElement->getAttribute('id')] ??= $domElement;

            if (SvgTag::Style->value === $domElement->localName
                || !$this->isInsideDefinition($domElement, $definitions, $alwaysLive)) {
                $live += $this->elementReferences($domElement);
            }
        }

        unset($elementsById['']);

        return $this->followReferences($live, $elementsById);
    }

    /**
     * @param array<string, true>        $live         the ids referenced so far
     * @param array<string, \DOMElement> $elementsById the elements by id
     *
     * @return array<string, true> the ids referenced so far and from within the elements they identify
     */
    private function followReferences(array $live, array $elementsById): array
    {
        $pending = array_map(strval(...), array_keys($live));

        while ([] !== $pending) {
            $id = array_pop($pending);

            if (!\array_key_exists($id, $elementsById)) {
                continue;
            }

            foreach ($elementsById[$id]->getElementsByTagName('*') as $descendant) {
                $references = array_diff_key($this->elementReferences($descendant), $live);
                $live += $references;
                array_push($pending, ...array_map(strval(...), array_keys($references)));
            }

            $references = array_diff_key($this->elementReferences($elementsById[$id]), $live);
            $live += $references;
            array_push($pending, ...array_map(strval(...), array_keys($references)));
        }

        return $live;
    }

    /**
     * @param \DOMElement $domElement the element to scan
     *
     * @return array<string, true> the ids referenced by its attributes, and by its text if it is a `<style>`
     */
    private function elementReferences(\DOMElement $domElement): array
    {
        $referencedIds = SvgTag::Style->value === $domElement->localName
            ? $this->fragmentReferences($domElement->textContent)
            : [];

        foreach ($domElement->attributes as $attribute) {
            $referencedIds += $this->attributeReferences($attribute);
        }

        return $referencedIds;
    }

    /**
     * @param \DOMElement  $domElement  the element to check
     * @param list<string> $definitions the elements that only render when referenced
     * @param list<string> $alwaysLive  the elements whose content counts as referenced
     *
     * @return bool true if the element is, or is inside, a `<defs>` or a definition element, and the nearest such
     *              ancestor is not one whose content always counts as referenced
     */
    private function isInsideDefinition(\DOMElement $domElement, array $definitions, array $alwaysLive): bool
    {
        for ($node = $domElement; $node instanceof \DOMElement; $node = $node->parentNode) {
            if (\in_array($node->localName, $alwaysLive, true)) {
                return false;
            }

            if (SvgTag::Defs->value === $node->localName || \in_array($node->localName, $definitions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \DOMAttr $domAttr the attribute to scan
     *
     * @return array<string, true> the ids referenced through a fragment, an id list or a SMIL timing value
     */
    private function attributeReferences(\DOMAttr $domAttr): array
    {
        $referencedIds = $this->fragmentReferences($domAttr->value);
        $name = $domAttr->nodeName;

        if (\in_array($name, self::ID_LIST_ATTRIBUTES, true)) {
            preg_match_all(self::ID_LIST_ITEM_REGEX, $domAttr->value, $items);
            $referencedIds += array_fill_keys($items[0], true);
        }

        if (SvgAttribute::Begin->value === $name || SvgAttribute::End->value === $name) {
            foreach (explode(self::SMIL_ITEM_SEPARATOR, $domAttr->value) as $item) {
                $referencedIds += $this->idCandidates(trim($item));
            }
        }

        return $referencedIds;
    }

    /**
     * @param string $text the attribute value or style sheet to scan
     *
     * @return array<string, true> the ids referenced through `#id`
     */
    private function fragmentReferences(string $text): array
    {
        if (false === preg_match_all(self::FRAGMENT_REFERENCE_REGEX, $text, $matches)) {
            return [];
        }

        $referencedIds = [];

        foreach ($matches[1] as $token) {
            // A URL fragment may percent-encode the id, such as `#%E4%BA%BA` for the id `人`.
            $referencedIds += $this->idCandidates($token) + $this->idCandidates(rawurldecode($token));
        }

        return $referencedIds;
    }

    /**
     * @param string $token a reference that may continue past the id, such as `a.click` or `a:hover`
     *
     * @return array<string, true> every prefix that may be the id, both escaped and unescaped
     */
    private function idCandidates(string $token): array
    {
        $candidates = [];
        $prefix = '';

        preg_match_all(self::ID_PART_REGEX, $token, $parts);

        foreach ($parts[0] as $part) {
            $prefix .= $part;
            $candidates[$prefix] = true;
            $candidates[stripslashes($prefix)] = true;
        }

        return $candidates;
    }
}
