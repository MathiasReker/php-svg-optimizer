<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\DangerousUrlTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Security\UnsafeAttributeTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveForeignContent implements PassInterface
{
    use CanonicalNameTrait;
    use DangerousUrlTrait;
    use ElementNameTrait;
    use ElementRemovalTrait;
    use ElementTrait;
    use UnsafeAttributeTrait;

    private const array EMBEDDING_ELEMENTS = [
        Element::ForeignObject->value,
        Element::Iframe->value,
        Element::Object->value,
        Element::Embed->value,
        Element::Link->value,
    ];

    private const array HTML_BREAKOUT_ELEMENTS = [
        'b', 'big', 'blockquote', 'body', 'br', 'center', 'code', 'dd', 'div', 'dl', 'dt', 'em', 'embed',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'hr', 'i', 'img', 'li', 'listing', 'menu', 'meta', 'nobr',
        'ol', 'p', 'pre', 'ruby', 's', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tt', 'u',
        'ul', 'var',
    ];

    private const array FONT_BREAKOUT_ATTRIBUTES = ['color', 'face', 'size'];

    private const string FONT_ELEMENT = 'font';

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->removeElementsWhere($domDocument, $this->isUnsafeElement(...));
        $this->unwrapElementsWithUnsafeLinks($domDocument);
        $this->removeUnsafeAttributes($domDocument);
    }

    /**
     * @param \DOMAttr $domAttr the attribute to check
     *
     * @return bool true if the attribute is an XML Events attribute, or an HTML parser would read it as a known
     *              attribute that it is not
     */
    private function isUnsafeAttribute(\DOMAttr $domAttr): bool
    {
        return NamespaceUri::XmlEvents->value === $domAttr->namespaceURI || $this->isNonCanonicalAttribute($domAttr);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is not SVG, could embed another document, breaks out of foreign content in an
     *              HTML parser, or is read as another element by an HTML parser
     */
    private function isUnsafeElement(\DOMElement $domElement): bool
    {
        return !\in_array($domElement->namespaceURI, [null, '', NamespaceUri::Svg->value], true)
            || $this->isNonCanonicalElement($domElement)
            || $this->hasLocalNameIgnoringCase($domElement, self::EMBEDDING_ELEMENTS)
            || $this->isHtmlBreakout($domElement);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isHtmlBreakout(\DOMElement $domElement): bool
    {
        $fontBreakout = $this->hasLocalNameIgnoringCase($domElement, [self::FONT_ELEMENT])
            && [] !== array_filter(self::FONT_BREAKOUT_ATTRIBUTES, $domElement->hasAttribute(...));

        return $fontBreakout || $this->hasLocalNameIgnoringCase($domElement, self::HTML_BREAKOUT_ELEMENTS);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function unwrapElementsWithUnsafeLinks(\DOMDocument $domDocument): void
    {
        foreach (Element::linkingNames() as $tag) {
            foreach ($this->elementsByQualifiedName($domDocument, $tag) as $domElement) {
                if ($this->hasUnsafeLink($domElement)) {
                    $this->unwrap($domElement);
                }
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function hasUnsafeLink(\DOMElement $domElement): bool
    {
        return [] !== array_filter(
            Attribute::linkNames(),
            fn (string $attrName): bool => $domElement->hasAttribute($attrName) && $this->isDangerousUrl($domElement->getAttribute($attrName)),
        );
    }

    /**
     * @param \DOMElement $domElement the DOM element to replace by its children
     */
    private function unwrap(\DOMElement $domElement): void
    {
        $parent = $domElement->parentNode;

        if (!$parent instanceof \DOMNode) {
            return;
        }

        foreach ([...$domElement->childNodes] as $child) {
            $parent->insertBefore($child, $domElement);
        }

        $parent->removeChild($domElement);
    }
}
