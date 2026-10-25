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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\ElementAttributeMap;
use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveNonStandardAttributes implements PassInterface
{
    use ElementTrait;
    use ScriptingTrait;
    use SelectorTrait;
    use StyleSheetTrait;

    private const string XPATH_ALL_WITH_ATTRIBUTES = '/descendant::*[@*]';

    private const string DATA_ATTRIBUTE_REGEX = '/^data-[\-\w.\x{00B7}-\x{FFFF}]+$/u';

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
        $css = $this->styleSheetText($domDocument);

        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query(self::XPATH_ALL_WITH_ATTRIBUTES, null, false);

        foreach ($elements as $element) {
            if ($this->isForeignContent($element)) {
                continue;
            }

            $this->removeNonStandardAttributesFromElement($element, $this->allowedAttributesFor($element), $css);
        }
    }

    /**
     * @param \DOMElement $domElement the element whose attributes are checked
     *
     * @return array<string, true> a map where keys are the allowed attribute names
     */
    private function allowedAttributesFor(\DOMElement $domElement): array
    {
        /** @var array<string, array<string, true>> $cache */
        static $cache = [];

        $element = (string) $domElement->localName;

        if (!ElementAttributeMap::isKnownElement($element)) {
            return $this->allowedAttributes();
        }

        return $cache[$element] ??= array_diff_key(
            ElementAttributeMap::allowedAttributes($element),
            [Attribute::XmlBase->value => true],
        );
    }

    /**
     * @return array<string, true> a map where keys are the allowed attribute names
     */
    private function allowedAttributes(): array
    {
        /** @var array<string, true>|null $lookup */
        static $lookup = null;

        return $lookup ??= array_diff_key(
            array_fill_keys(Attribute::values(), true),
            array_flip(Attribute::htmlOnlyNames()),
        );
    }

    /**
     * @param \DOMElement         $domElement the element to process
     * @param array<string, true> $allowed    a lookup map of allowed attribute names
     * @param string              $css        the style sheets of the document
     */
    private function removeNonStandardAttributesFromElement(\DOMElement $domElement, array $allowed, string $css): void
    {
        foreach (iterator_to_array($domElement->attributes, false) as $domAttr) {
            if (!$this->isAttributeAllowed($domAttr, $allowed)
                && !$this->hasAttributeSelector($css, (string) $domAttr->localName)
            ) {
                $domElement->removeAttributeNode($domAttr);
            }
        }
    }

    /**
     * @param \DOMAttr            $domAttr the attribute to check
     * @param array<string, true> $allowed a lookup map of allowed attribute names
     *
     * @return bool true if the attribute is allowed, or an event handler, which removeScripts removes, false otherwise
     */
    private function isAttributeAllowed(\DOMAttr $domAttr, array $allowed): bool
    {
        $name = $this->qualifiedName($domAttr);

        return \array_key_exists($name, $allowed)
            || 1 === preg_match(self::DATA_ATTRIBUTE_REGEX, $name)
            || (null === $domAttr->namespaceURI && $this->isEventHandlerName($name));
    }

    /**
     * @param \DOMAttr $domAttr the attribute to resolve
     *
     * @return string the canonical name, or an empty string if the attribute belongs to a foreign namespace
     */
    private function qualifiedName(\DOMAttr $domAttr): string
    {
        return match ($domAttr->namespaceURI) {
            null => $domAttr->nodeName,
            NamespaceUri::Xlink->value => 'xlink:' . $domAttr->name,
            NamespaceUri::Xml->value => 'xml:' . $domAttr->name,
            NamespaceUri::XmlEvents->value => NamespaceUri::XmlEvents->prefix() . ':' . $domAttr->name,
            default => '',
        };
    }
}
