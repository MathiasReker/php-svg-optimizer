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
        $namespaceUris = $this->getConfiguredNamespaceUris();

        if ([] === $namespaceUris) {
            return;
        }

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domXPath->query(self::XPATH_ALL_ELEMENTS);

        foreach (iterator_to_array($nodes, true) as $domElement) {
            $this->removeNodeAttributes($domElement, $namespaceUris);
        }
    }

    /**
     * @return list<string> the namespace URIs configured for removal
     */
    private function getConfiguredNamespaceUris(): array
    {
        $namespaceUris = [];

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

            $namespaceUris[] = self::NAMESPACE_URIS[$prefix];
        }

        return $namespaceUris;
    }

    /**
     * @param \DOMElement  $domElement    the element to clean
     * @param list<string> $namespaceUris the namespace URIs of the attributes to remove
     */
    private function removeNodeAttributes(\DOMElement $domElement, array $namespaceUris): void
    {
        foreach ($this->getAttributesToRemove($domElement, $namespaceUris) as [$namespaceUri, $localName]) {
            $domElement->removeAttributeNS($namespaceUri, $localName);
        }
    }

    /**
     * @param \DOMElement  $domElement    the element to inspect
     * @param list<string> $namespaceUris the namespace URIs to match
     *
     * @return list<array{string, string}> a list of [namespaceUri, localName] pairs to be removed
     */
    private function getAttributesToRemove(\DOMElement $domElement, array $namespaceUris): array
    {
        $attributesToRemove = [];

        /** @var \DOMAttr $attribute */
        foreach ($domElement->attributes as $attribute) {
            if (null !== $attribute->namespaceURI
                && \in_array($attribute->namespaceURI, $namespaceUris, true)
                && null !== $attribute->localName
            ) {
                $attributesToRemove[] = [$attribute->namespaceURI, $attribute->localName];
            }
        }

        return $attributesToRemove;
    }
}
