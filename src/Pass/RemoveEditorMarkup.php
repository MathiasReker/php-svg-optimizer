<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveEditorMarkup implements PassInterface
{
    use IdTrait;
    use SelectorTrait;
    use StyleSheetTrait;

    private const array EDITOR_NAMESPACE_URIS = [
        NamespaceUri::Inkscape->value,
        NamespaceUri::Sodipodi->value,
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

    private const string NAMESPACE_DECLARATION_REGEX = '/\sxmlns:([^\s=]+)="([^"]*)"/';

    private const string XPATH_DESCENDANT_ELEMENTS = './/*';

    private const string XPATH_EXTENSION_ELEMENTS = '//*[local-name()="%s"][@%s]';

    private const string XMLNS_PREFIX = 'xmlns:';

    private const string XPATH_NAMESPACE_NODES = 'boolean(//*[namespace-uri() = "%1$s"] | //@*[namespace-uri() = "%1$s"])';

    private const string EXTENSION_SEPARATOR = ' ';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasExternalStyleSheet($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);
        $namespaceUris = self::EDITOR_NAMESPACE_URIS;
        $css = $this->styleSheetText($domDocument);
        $removesElements = !$this->hasStructureSensitiveStyleSheet($domDocument);

        if ($removesElements) {
            $this->removeEditorExtensionElements($domXPath);
        }

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::XPATH_ALL_ELEMENTS, null, false);

        foreach ($domNodeList as $domElement) {
            if ($removesElements && $this->isRemovableEditorElement($domXPath, $domElement, $namespaceUris)) {
                $domElement->remove();

                continue;
            }

            $this->removeEditorAttributes($domElement, $namespaceUris, $css);
        }

        $this->removeNamespaceDeclarations($domXPath);
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function removeEditorExtensionElements(\DOMXPath $domXPath): void
    {
        $query = \sprintf(
            self::XPATH_EXTENSION_ELEMENTS,
            Element::ForeignObject->value,
            Attribute::RequiredExtensions->value,
        );

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query($query, null, false);

        foreach ($domNodeList as $domElement) {
            if (\in_array($domElement->namespaceURI, [null, NamespaceUri::Svg->value], true)
                && $this->requiresEditorExtension($domElement)
                && !$this->hasSvgElementWithId($domElement)
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
        $extensions = explode(self::EXTENSION_SEPARATOR, $domElement->getAttribute(Attribute::RequiredExtensions->value));

        foreach ($extensions as $extension) {
            if ($this->isEditorNamespace($extension)) {
                return true;
            }
        }

        return false;
    }

    /**
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
        $descendants = $domXPath->query(self::XPATH_DESCENDANT_ELEMENTS, $domElement, false);

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
     * @param string       $css           the style sheets of the document, whose attribute selectors keep an attribute
     */
    private function removeEditorAttributes(\DOMElement $domElement, array $namespaceUris, string $css): void
    {
        $attributesToRemove = [];

        /** @var \DOMAttr $domAttr */
        foreach ($domElement->attributes as $domAttr) {
            if (\in_array($domAttr->namespaceURI, $namespaceUris, true)
                && !$this->hasAttributeSelector($css, (string) $domAttr->localName)
            ) {
                $attributesToRemove[] = $domAttr;
            }
        }

        foreach ($attributesToRemove as $attributeToRemove) {
            $domElement->removeAttributeNode($attributeToRemove);
        }
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     */
    private function removeNamespaceDeclarations(\DOMXPath $domXPath): void
    {
        $declarations = $this->editorNamespaceDeclarations($domXPath);

        foreach ([] === $declarations ? [] : $domXPath->document->getElementsByTagName('*') as $domElement) {
            foreach ($declarations as $declaration) {
                if ($domElement->hasAttribute($declaration) && $this->isEditorNamespace($domElement->getAttribute($declaration))) {
                    $domElement->removeAttribute($declaration);
                }
            }
        }
    }

    /**
     * @param \DOMXPath $domXPath the XPath object for querying the document
     *
     * @return array<string, string> the namespace declaration attributes that bind a prefix to an editor namespace
     *                               somewhere, unless an element or attribute still uses the namespace, by themselves
     */
    private function editorNamespaceDeclarations(\DOMXPath $domXPath): array
    {
        preg_match_all(self::NAMESPACE_DECLARATION_REGEX, (string) $domXPath->document->saveXML(), $matches, \PREG_SET_ORDER);

        $declarations = [];
        $used = [];

        foreach ($matches as $match) {
            if ($this->isEditorNamespace($match[2])
                && !($used[$match[2]] ??= true === $domXPath->evaluate(\sprintf(self::XPATH_NAMESPACE_NODES, $match[2]), null, false))
            ) {
                $declaration = self::XMLNS_PREFIX . $match[1];
                $declarations[$declaration] = $declaration;
            }
        }

        return $declarations;
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
