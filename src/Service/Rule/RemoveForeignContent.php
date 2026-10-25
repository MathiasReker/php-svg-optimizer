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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CanonicalNameTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DangerousAttributeValueTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EntityReferenceTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TagMatcherTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\UnsafeAttributeTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveForeignContent implements PassInterface
{
    use AsciiCaseTrait;
    use CanonicalNameTrait;
    use DangerousAttributeValueTrait;
    use ElementRemovalTrait;
    use EntityReferenceTrait;
    use TagMatcherTrait;
    use UnsafeAttributeTrait;

    private const array EMBEDDING_TAGS = [
        SvgTag::ForeignObject->value,
        SvgTag::Iframe->value,
        SvgTag::Object->value,
        SvgTag::Embed->value,
        SvgTag::Link->value,
    ];

    private const array HTML_BREAKOUT_TAGS = [
        'b', 'big', 'blockquote', 'body', 'br', 'center', 'code', 'dd', 'div', 'dl', 'dt', 'em', 'embed',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'hr', 'i', 'img', 'li', 'listing', 'menu', 'meta', 'nobr',
        'ol', 'p', 'pre', 'ruby', 's', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tt', 'u',
        'ul', 'var',
    ];

    private const array FONT_BREAKOUT_ATTRIBUTES = ['color', 'face', 'size'];

    private const string FONT_TAG = 'font';

    /**
     * @param \DOMDocument $domDocument the DOM document to sanitize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->expandEntityReferences($domDocument);
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
        return SvgNamespace::XmlEvents->value === $domAttr->namespaceURI || $this->isNonCanonicalAttribute($domAttr);
    }

    /**
     * @param \DOMElement $domElement the element to check
     *
     * @return bool true if the element is not SVG, could embed another document, breaks out of foreign content in an
     *              HTML parser, or is read as another element by an HTML parser
     */
    private function isUnsafeElement(\DOMElement $domElement): bool
    {
        return !\in_array($domElement->namespaceURI, [null, '', SvgNamespace::Svg->value], true)
            || $this->isNonCanonicalElement($domElement)
            || $this->isTagIn($domElement, self::EMBEDDING_TAGS)
            || $this->isHtmlBreakout($domElement);
    }

    /**
     * @param \DOMElement $domElement the element to check
     */
    private function isHtmlBreakout(\DOMElement $domElement): bool
    {
        $fontBreakout = $this->isTagIn($domElement, [self::FONT_TAG])
            && [] !== array_filter(self::FONT_BREAKOUT_ATTRIBUTES, $domElement->hasAttribute(...));

        return $fontBreakout || $this->isTagIn($domElement, self::HTML_BREAKOUT_TAGS);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to clean
     */
    private function unwrapElementsWithUnsafeLinks(\DOMDocument $domDocument): void
    {
        foreach (SvgTag::conditionalDangerous() as $tag) {
            foreach ($this->elementList($domDocument, $tag) as $domElement) {
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
            SvgAttribute::dangerousExact(),
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
