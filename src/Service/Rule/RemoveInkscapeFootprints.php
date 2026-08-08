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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;

/**
 * @no-named-arguments
 */
final readonly class RemoveInkscapeFootprints implements SvgOptimizerRuleInterface
{
    private const int EXPLODE_LIMIT = 2;

    private const int OPTIMIZATION_LOOP_COUNT = 2;

    private const array XMLNS_ATTRIBUTES = [
        'xmlns:sodipodi',
        'xmlns:inkscape',
    ];

    private const array ATTRIBUTES_TO_REMOVE = [
        'sodipodi:*',
        'inkscape:*',
    ];

    private const array NAMESPACE_URIS = [
        'sodipodi' => SvgNamespace::Sodipodi->value,
        'inkscape' => SvgNamespace::Inkscape->value,
    ];

    private const string XPATH_ALL_ELEMENTS = '//*';

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
        $domXPath = new \DOMXPath($domDocument);

        foreach (self::NAMESPACE_URIS as $prefix => $uri) {
            $domXPath->registerNamespace($prefix, $uri);
        }

        for ($i = 0; $i < self::OPTIMIZATION_LOOP_COUNT; ++$i) {
            $this->removeNamespaceDeclarations($domDocument);
            $this->removeTags($domXPath, self::ATTRIBUTES_TO_REMOVE);
            $this->removeNamespacedAttributes($domXPath);
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function removeNamespaceDeclarations(\DOMDocument $domDocument): void
    {
        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $domElement) {
            foreach (self::XMLNS_ATTRIBUTES as $xmlnsAttribute) {
                if ($domElement->hasAttribute($xmlnsAttribute)) {
                    $domElement->removeAttribute($xmlnsAttribute);
                }
            }
        }
    }

    /**
     * @param \DOMXPath    $domXPath     the XPath object for querying the document
     * @param list<string> $tagsToRemove A list of tag patterns to remove (e.g., "sodipodi:*").
     */
    private function removeTags(\DOMXPath $domXPath, array $tagsToRemove): void
    {
        foreach ($tagsToRemove as $tagToRemove) {
            [$prefix] = explode(':', $tagToRemove, self::EXPLODE_LIMIT);

            $query = \sprintf('//%s:*', $prefix);

            /** @var \DOMNodeList<\DOMElement> $domNodeList */
            $domNodeList = $domXPath->query($query);

            foreach (iterator_to_array($domNodeList, true) as $domElement) {
                if (!$domElement->parentNode instanceof \DOMNode) {
                    continue;
                }

                $domElement->parentNode->removeChild($domElement);
            }
        }
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function removeNamespacedAttributes(\DOMXPath $domXPath): void
    {
        foreach (self::ATTRIBUTES_TO_REMOVE as $pattern) {
            if (!str_contains($pattern, ':')) {
                continue;
            }

            $prefix = mb_strstr($pattern, ':', true);

            if (false === $prefix) {
                continue;
            }

            if (!\array_key_exists($prefix, self::NAMESPACE_URIS)) {
                continue;
            }

            $this->processNodes($domXPath, self::NAMESPACE_URIS[$prefix]);
        }
    }

    /**
     * @param \DOMXPath $domXPath     the XPath object for querying the document
     * @param string    $namespaceUri the namespace URI of the attributes to remove
     */
    private function processNodes(\DOMXPath $domXPath, string $namespaceUri): void
    {
        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domXPath->query(self::XPATH_ALL_ELEMENTS);

        foreach (iterator_to_array($nodes, true) as $domElement) {
            $this->removeNodeAttributes($domElement, $namespaceUri);
        }
    }

    /**
     * @param \DOMElement $domElement   the element to clean
     * @param string      $namespaceUri the namespace URI of the attributes to remove
     */
    private function removeNodeAttributes(\DOMElement $domElement, string $namespaceUri): void
    {
        $attributesToRemove = $this->getAttributesToRemove($domElement, $namespaceUri);

        foreach ($attributesToRemove as $attributeToRemove) {
            $domElement->removeAttributeNS($namespaceUri, $attributeToRemove);
        }
    }

    /**
     * @param \DOMElement $domElement   the element to inspect
     * @param string      $namespaceUri the namespace URI to match
     *
     * @return list<string> a list of attribute local names to be removed
     */
    private function getAttributesToRemove(\DOMElement $domElement, string $namespaceUri): array
    {
        $attributesToRemove = [];

        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if ($attribute->namespaceURI === $namespaceUri && null !== $attribute->localName) {
                $attributesToRemove[] = $attribute->localName;
            }
        }

        return $attributesToRemove;
    }
}
