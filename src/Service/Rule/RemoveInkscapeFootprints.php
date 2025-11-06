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

/**
 * @no-named-arguments
 */
final readonly class RemoveInkscapeFootprints implements SvgOptimizerRuleInterface
{
    /**
     * The limit for the number of times to explode the SVG document.
     */
    private const int EXPLODE_LIMIT = 2;

    /**
     * The number of optimization loops to perform.
     */
    private const int OPTIMIZATION_LOOP_COUNT = 2;

    /**
     * The XPath query to select all nodes in the SVG document.
     *
     * This query is used to select all elements in the SVG document for
     * processing, such as removing unwanted elements and attributes.
     */
    private const string ALL_NODES_XPATH_QUERY = '//*';

    /**
     * The XML namespace attributes to remove from the SVG document.
     */
    private const array XMLNS_ATTRIBUTES = [
        'xmlns:sodipodi',
        'xmlns:inkscape',
    ];

    /**
     * The tags to remove from the SVG document.
     *
     * These tags are typically used for metadata and are not essential for
     * rendering the SVG image. They are removed to reduce file size and
     * improve performance.
     */
    private const array TAGS_TO_REMOVE = [
        'sodipodi:*',
        'inkscape:*',
    ];

    /**
     * The attributes to remove from the SVG document.
     *
     * These attributes are typically used for metadata and are not essential
     * for rendering the SVG image. They are removed to reduce file size and
     * improve performance.
     */
    private const array ATTRIBUTES_TO_REMOVE = [
        'sodipodi:*',
        'inkscape:*',
    ];

    /**
     * The XML namespace URIs for the Sodipodi and Inkscape namespaces.
     *
     * These URIs are used to identify the namespaces in the SVG document and
     * are used to remove elements and attributes related to these namespaces.
     */
    private const array NAMESPACE_URIS = [
        'sodipodi' => 'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd',
        'inkscape' => 'http://www.inkscape.org/namespaces/inkscape',
    ];

    /**
     * Remove the Inkspace footprints from the SVG document.
     *
     * This method will find and remove all elements and attributes related to
     * Inkspace, including any XML namespace attributes. These elements and
     * attributes are typically used for metadata and are not essential for
     * rendering the SVG image.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
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
            $this->removeTags($domXPath, self::TAGS_TO_REMOVE);
            $this->removeNamespacedAttributes($domXPath);
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Remove the XML namespace attributes from all SVG tags.
     *
     * This method iterates through all elements in the \DOMDocument and removes
     * any XML namespace attributes that are defined in the XMLNS_ATTRIBUTES
     * constant.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    private function removeNamespaceDeclarations(\DOMDocument $domDocument): void
    {
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $element) {
            foreach (self::XMLNS_ATTRIBUTES as $xmlnsAttribute) {
                if ($element->hasAttribute($xmlnsAttribute)) {
                    $element->removeAttribute($xmlnsAttribute);
                }
            }
        }
    }

    /**
     * Remove elements from the SVG document based on the specified tags.
     *
     * This method iterates through all elements in the \DOMXPath and removes
     * any elements that match the specified tags in the TAGS_TO_REMOVE
     * constant.
     *
     * @param \DOMXPath    $domXPath     The \DOMXPath instance representing the SVG file to be optimized
     * @param list<string> $tagsToRemove
     */
    private function removeTags(\DOMXPath $domXPath, array $tagsToRemove): void
    {
        foreach ($tagsToRemove as $tagToRemove) {
            [$prefix] = explode(':', $tagToRemove, self::EXPLODE_LIMIT);

            $query = \sprintf('//%s:*', $prefix);
            $nodes = $domXPath->query($query);

            if (!($nodes instanceof \DOMNodeList)) {
                continue;
            }

            foreach (iterator_to_array($nodes, true) as $node) {
                if (!($node instanceof \DOMElement)) {
                    continue;
                }

                if (!($node->parentNode instanceof \DOMNode)) {
                    continue;
                }

                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Remove attributes from the SVG document based on the specified attributes.
     *
     * This method iterates through all elements in the \DOMXPath and removes
     * any attributes that match the specified attributes in the ATTRIBUTES_TO_REMOVE
     * constant.
     *
     * @param \DOMXPath $domXPath The \DOMXPath instance representing the SVG file to be optimized
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
     * Process all nodes in the \DOMXPath and remove attributes that match the given namespace URI.
     */
    private function processNodes(\DOMXPath $domXPath, string $namespaceUri): void
    {
        $nodes = $domXPath->query(self::ALL_NODES_XPATH_QUERY);
        if (!($nodes instanceof \DOMNodeList)) {
            return;
        }

        foreach (iterator_to_array($nodes, true) as $domNode) {
            if ($domNode instanceof \DOMElement) {
                $this->removeNodeAttributes($domNode, $namespaceUri);
            }
        }
    }

    /**
     * Remove attributes from the node that match the given namespace URI.
     */
    private function removeNodeAttributes(\DOMElement $domElement, string $namespaceUri): void
    {
        $attributesToRemove = $this->getAttributesToRemove($domElement, $namespaceUri);

        foreach ($attributesToRemove as $attributeToRemove) {
            $domElement->removeAttributeNS($namespaceUri, $attributeToRemove);
        }
    }

    /**
     * Get the attributes to remove from the node based on the given namespace URI.
     *
     * @return list<string>
     */
    private function getAttributesToRemove(\DOMElement $domElement, string $namespaceUri): array
    {
        $attributesToRemove = [];

        /** @var \DOMAttr $attr */
        foreach ($domElement->attributes ?? [] as $attr) {
            if ($attr->namespaceURI === $namespaceUri && null !== $attr->localName) {
                $attributesToRemove[] = $attr->localName;
            }
        }

        return $attributesToRemove;
    }
}
