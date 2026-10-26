<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Svg\Attribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedNamespaces implements PassInterface
{
    private const string NAMESPACE_QUERY = '/*/namespace::*[name() != "" and name() != "xml"]';

    private const array VALUE_QUERIES = ['//text()', '//@*'];

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        $usedPrefixes = $this->usedPrefixes($domDocument);
        $values = $this->values($domXPath);

        /** @var \DOMNodeList<\DOMNameSpaceNode> $declarations */
        $declarations = $domXPath->query(self::NAMESPACE_QUERY);

        foreach (iterator_to_array($declarations, false) as $domNameSpaceNode) {
            $prefix = $domNameSpaceNode->prefix;
            $root = $domNameSpaceNode->parentNode;

            if ($root instanceof \DOMElement && !\array_key_exists($prefix, $usedPrefixes) && !str_contains($values, $prefix . ':')) {
                $root->removeAttribute(Attribute::Xmlns->value . ':' . $prefix);
            }
        }
    }

    /**
     * @param \DOMDocument $domDocument the document to scan
     *
     * @return array<string, true> the prefixes of the element and attribute names
     */
    private function usedPrefixes(\DOMDocument $domDocument): array
    {
        $prefixes = [];

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            $prefixes[$element->prefix] = true;

            /** @var \DOMAttr $attribute */
            foreach ($element->attributes as $attribute) {
                $prefixes[$attribute->prefix] = true;
            }
        }

        return $prefixes;
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     *
     * @return string the attribute values and text of the document, which may name a prefix, as in
     *                `attributeName="xlink:href"`
     */
    private function values(\DOMXPath $domXPath): string
    {
        $values = '';

        foreach (self::VALUE_QUERIES as $query) {
            /** @var \DOMNodeList<\DOMNode> $domNodeList */
            $domNodeList = $domXPath->query($query);

            foreach ($domNodeList as $domNode) {
                $values .= ($domNode->nodeValue ?? '') . "\n";
            }
        }

        return $values;
    }
}
