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
 * @internal
 *
 * @no-named-arguments
 */
trait IdReferenceTrait
{
    private const string FRAGMENT_REFERENCE_REGEX = '/#((?:\\\.|[^\s#,;\'"()\[\]{}<>\\\])+)/';

    private const string ID_PART_REGEX = '/[^\w-]?[\w-]*/';

    private const string ID_LIST_ITEM_REGEX = '/\S+/';

    private const string SMIL_ITEM_SEPARATOR = ';';

    private const array ID_LIST_ATTRIBUTES = [
        'aria-activedescendant', 'aria-controls', 'aria-describedby', 'aria-details', 'aria-errormessage',
        'aria-flowto', 'aria-labelledby', 'aria-owns', 'for', 'form', 'headers', 'list',
    ];

    /**
     * @param \DOMDocument $domDocument the DOM document to scan
     *
     * @return array<string, true> the referenced ids
     */
    private function collectReferencedIds(\DOMDocument $domDocument): array
    {
        $referencedIds = [];

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach ($domNodeList->attributes as $attribute) {
                $referencedIds += $this->attributeReferences($attribute);
            }

            if (SvgTag::Style->value === $domNodeList->localName) {
                $referencedIds += $this->fragmentReferences($domNodeList->textContent);
            }
        }

        return $referencedIds;
    }

    /**
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

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $elementsById[$domNodeList->getAttribute('id')] ??= $domNodeList;

            if (SvgTag::Style->value === $domNodeList->localName
                || !$this->isInsideDefinition($domNodeList, $definitions, $alwaysLive)) {
                $live += $this->elementReferences($domNodeList);
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
