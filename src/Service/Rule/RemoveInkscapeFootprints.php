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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * Removes editor metadata written by Inkscape, Sodipodi, Adobe Illustrator, Sketch, Serif (Affinity) and Microsoft Visio.
 *
 * Elements and attributes are matched by namespace URI (also when the URI is declared through a DTD entity), so the
 * prefix used in the file does not matter. Illustrator's private data placeholders (a foreignObject that requires an
 * editor extension) are removed as well, since no browser supports those extensions and they are never rendered.
 *
 * @no-named-arguments
 */
final readonly class RemoveInkscapeFootprints implements SvgOptimizerRuleInterface
{
    private const array EDITOR_NAMESPACE_URIS = [
        SvgNamespace::Inkscape->value,
        SvgNamespace::Sodipodi->value,
        'http://ns.adobe.com/AdobeIllustrator/10.0/',
        'http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/',
        'http://ns.adobe.com/Extensibility/1.0/',
        'http://ns.adobe.com/Flows/1.0/',
        'http://ns.adobe.com/GenericCustomNamespace/1.0/',
        'http://ns.adobe.com/Graphs/1.0/',
        'http://ns.adobe.com/ImageReplacement/1.0/',
        'http://ns.adobe.com/SaveForWeb/1.0/',
        'http://ns.adobe.com/Variables/1.0/',
        'http://ns.adobe.com/XPath/1.0/',
        'http://www.bohemiancoding.com/sketch/ns',
        'http://www.serif.com/',
        'http://schemas.microsoft.com/visio/2003/SVGExtensions/',
    ];

    private const string XPATH_ALL_ELEMENTS = '//*';

    private const string XPATH_ALL_NAMESPACE_NODES = '//namespace::*';

    private const string XPATH_ELEMENT_NAMESPACE_NODES = 'namespace::*';

    private const string XPATH_DESCENDANT_ELEMENTS = './/*';

    private const string XPATH_EXTENSION_ELEMENTS = '//*[local-name()="%s"][@%s]';

    private const string XMLNS_PREFIX = 'xmlns:';

    private const string EXTENSION_SEPARATOR = ' ';

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
        $namespaceUris = $this->collectEditorNamespaceUris($domXPath);

        $this->removeEditorExtensionElements($domXPath);

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_ALL_ELEMENTS);

        foreach (iterator_to_array($domNodeList, false) as $domElement) {
            if ($this->isRemovableEditorElement($domXPath, $domElement, $namespaceUris)) {
                $domElement->parentNode?->removeChild($domElement);

                continue;
            }

            $this->removeEditorAttributes($domElement, $namespaceUris);
        }

        $this->removeNamespaceDeclarations($domXPath);
    }

    /**
     * Namespace URIs declared through a DTD entity (e.g. xmlns:i="&ns_ai;") are stored unexpanded on the nodes,
     * so the raw values of declarations resolving to an editor namespace are collected as well.
     *
     * @param \DOMXPath $domXPath the XPath object for querying the document
     *
     * @return list<string> the namespace URIs of the nodes to remove
     */
    private function collectEditorNamespaceUris(\DOMXPath $domXPath): array
    {
        $namespaceUris = self::EDITOR_NAMESPACE_URIS;

        /** @var \DOMNodeList<\DOMNameSpaceNode> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_ALL_NAMESPACE_NODES);

        foreach ($domNodeList as $domNameSpaceNode) {
            if (null !== $domNameSpaceNode->namespaceURI
                && $this->isEditorNamespace((string) $domNameSpaceNode->nodeValue)
            ) {
                $namespaceUris[] = $domNameSpaceNode->namespaceURI;
            }
        }

        return array_values(array_unique($namespaceUris));
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function removeEditorExtensionElements(\DOMXPath $domXPath): void
    {
        $query = \sprintf(
            self::XPATH_EXTENSION_ELEMENTS,
            SvgTag::ForeignObject->value,
            SvgAttribute::RequiredExtensions->value,
        );

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query($query);

        foreach (iterator_to_array($domNodeList, false) as $domElement) {
            if (\in_array($domElement->namespaceURI, [null, SvgNamespace::Svg->value], true)
                && $this->requiresEditorExtension($domElement)
                && $domElement->parentNode instanceof \DOMElement
            ) {
                $domElement->parentNode->removeChild($domElement);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to inspect
     *
     * @return bool true if one of the required extensions is an editor namespace
     */
    private function requiresEditorExtension(\DOMElement $domElement): bool
    {
        $extensions = explode(self::EXTENSION_SEPARATOR, $domElement->getAttribute(SvgAttribute::RequiredExtensions->value));

        foreach ($extensions as $extension) {
            if ($this->isEditorNamespace($extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Elements containing anything other than editor elements are kept, since their content may be referenced.
     *
     * @param \DOMXPath    $domXPath      the XPath object for querying the document
     * @param \DOMElement  $domElement    the element to inspect
     * @param list<string> $namespaceUris the namespace URIs of the nodes to remove
     *
     * @return bool true if the element can be removed
     */
    private function isRemovableEditorElement(\DOMXPath $domXPath, \DOMElement $domElement, array $namespaceUris): bool
    {
        if (!\in_array($domElement->namespaceURI, $namespaceUris, true)
            || !$domElement->parentNode instanceof \DOMElement
        ) {
            return false;
        }

        /** @var \DOMNodeList<\DOMElement> $descendants */
        $descendants = $domXPath->query(self::XPATH_DESCENDANT_ELEMENTS, $domElement);

        foreach ($descendants as $descendant) {
            if (!\in_array($descendant->namespaceURI, $namespaceUris, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DOMElement  $domElement    the element to clean
     * @param list<string> $namespaceUris the namespace URIs of the attributes to remove
     */
    private function removeEditorAttributes(\DOMElement $domElement, array $namespaceUris): void
    {
        $attributesToRemove = [];

        /** @var \DOMAttr $domAttr */
        foreach ($domElement->attributes as $domAttr) {
            if (\in_array($domAttr->namespaceURI, $namespaceUris, true)) {
                $attributesToRemove[] = $domAttr;
            }
        }

        foreach ($attributesToRemove as $attributeToRemove) {
            $domElement->removeAttributeNode($attributeToRemove);
        }
    }

    /**
     * Declarations still in use (e.g. by a kept editor element) are left untouched by the DOM.
     *
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function removeNamespaceDeclarations(\DOMXPath $domXPath): void
    {
        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_ALL_ELEMENTS);

        foreach ($domNodeList as $domElement) {
            /** @var \DOMNodeList<\DOMNameSpaceNode> $namespaceNodes */
            $namespaceNodes = $domXPath->query(self::XPATH_ELEMENT_NAMESPACE_NODES, $domElement);

            foreach (iterator_to_array($namespaceNodes, false) as $domNameSpaceNode) {
                $declaration = self::XMLNS_PREFIX . $domNameSpaceNode->prefix;

                if ('' !== $domNameSpaceNode->prefix
                    && $this->isEditorNamespace((string) $domNameSpaceNode->nodeValue)
                    && $domElement->hasAttribute($declaration)
                ) {
                    $domElement->removeAttribute($declaration);
                }
            }
        }
    }

    /**
     * @param string $namespaceUri the namespace URI to check
     *
     * @return bool true if the namespace URI belongs to an editor
     */
    private function isEditorNamespace(string $namespaceUri): bool
    {
        return \in_array($namespaceUri, self::EDITOR_NAMESPACE_URIS, true);
    }
}
