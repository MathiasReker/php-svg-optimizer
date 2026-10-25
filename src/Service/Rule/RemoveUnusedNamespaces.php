<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveUnusedNamespaces implements PassInterface
{
    private const string NAMESPACE_QUERY = '/*/namespace::*[name() != "" and name() != "xml"]';

    private const string TEXT_QUERY = '//text() | //@*';

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
                $root->removeAttribute(SvgAttribute::Xmlns->value . ':' . $prefix);
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

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            $prefixes[$domNodeList->prefix] = true;

            /** @var \DOMAttr $attribute */
            foreach ($domNodeList->attributes as $attribute) {
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

        /** @var \DOMNodeList<\DOMNode> $domNodeList */
        $domNodeList = $domXPath->query(self::TEXT_QUERY);

        foreach ($domNodeList as $domNode) {
            $values .= ($domNode->nodeValue ?? '') . "\n";
        }

        return $values;
    }
}
