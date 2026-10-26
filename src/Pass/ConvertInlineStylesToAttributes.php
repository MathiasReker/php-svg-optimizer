<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\ConvertibleProperty;

/**
 * @internal
 */
final readonly class ConvertInlineStylesToAttributes implements SizeGuardedPassInterface
{
    use AsciiCaseTrait;
    use DeclarationTrait;
    use ElementTrait;
    use StyleSheetTrait;

    private const string STYLED_ELEMENTS_QUERY = '//*[@style]';

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $this->convert($domDocument, false);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function applyWithoutSizeGuard(\DOMDocument $domDocument): void
    {
        $this->convert($domDocument, true);
    }

    /**
     * @param \DOMDocument $domDocument   the DOM document to optimize
     * @param bool         $growthAllowed whether an element is also converted when its markup grows
     */
    private function convert(\DOMDocument $domDocument, bool $growthAllowed): void
    {
        if ($this->hasStyleSheet($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::STYLED_ELEMENTS_QUERY);

        foreach ($domNodeList as $domElement) {
            if ($this->isSvgElement($domElement)) {
                $this->convertStyles($domElement, $growthAllowed);
            }
        }
    }

    /**
     * @param \DOMElement $domElement    the element to process
     * @param bool        $growthAllowed whether the element is also converted when its markup grows
     */
    private function convertStyles(\DOMElement $domElement, bool $growthAllowed): void
    {
        $style = $domElement->getAttribute(Attribute::Style->value);

        if (!str_contains($style, ':') || str_contains($style, self::CSS_COMMENT_START)) {
            return;
        }

        if (!$this->isSplittable($style)) {
            return;
        }

        [$attributes, $remaining] = $this->processDeclarations($domElement, $style);

        if ($growthAllowed || $this->convertedLength($domElement, $attributes, $remaining) <= $this->attributeLength(Attribute::Style->value, $style)) {
            $this->convertDeclarations($domElement, $attributes, $remaining);
        }
    }

    /**
     * @param \DOMElement           $domElement the element to change
     * @param array<string, string> $attributes the presentation attributes to set, by name
     * @param list<string>          $remaining  the declarations that stay in the style attribute
     *
     * @return int the number of bytes that the converted attributes, the remaining style and any attribute they replace
     *             add to the markup of the element
     */
    private function convertedLength(\DOMElement $domElement, array $attributes, array $remaining): int
    {
        $length = [] === $remaining ? 0 : $this->attributeLength(Attribute::Style->value, implode(';', $remaining));

        foreach ($attributes as $name => $value) {
            $length += $this->attributeLength($name, $value)
                - ($domElement->hasAttribute($name) ? $this->attributeLength($name, $domElement->getAttribute($name)) : 0);
        }

        return $length;
    }

    /**
     * @param string $name  the attribute name
     * @param string $value the attribute value
     *
     * @return int the number of bytes of the attribute in the markup
     */
    private function attributeLength(string $name, string $value): int
    {
        return mb_strlen(' ' . $name . '="' . htmlspecialchars($value, \ENT_XML1 | \ENT_COMPAT) . '"', '8bit');
    }

    /**
     * @param \DOMElement           $domElement the element to change
     * @param array<string, string> $attributes the presentation attributes to set, by name
     * @param list<string>          $remaining  the declarations that stay in the style attribute
     */
    private function convertDeclarations(\DOMElement $domElement, array $attributes, array $remaining): void
    {
        foreach ($attributes as $name => $value) {
            $domElement->setAttribute($name, $value);
        }

        if ([] === $remaining) {
            $domElement->removeAttribute(Attribute::Style->value);
        } else {
            $domElement->setAttribute(Attribute::Style->value, implode(';', $remaining));
        }
    }

    /**
     * @param string $style a style attribute value that can be split safely
     *
     * @return array<int, array{string, string}> the property name and value of each declaration with both, as written
     */
    private function parseFilledDeclarations(string $style): array
    {
        return array_filter(
            $this->parseDeclarations($style),
            static fn (array $declaration): bool => '' !== $declaration[0] && '' !== $declaration[1],
        );
    }

    /**
     * @param string $style a style attribute value that can be split safely
     *
     * @return array{array<string, string>, list<string>} the presentation attributes the declarations become, by name,
     *                                                    and the declarations that stay in the style attribute
     */
    private function processDeclarations(\DOMElement $domElement, string $style): array
    {
        $pinned = $this->propertiesThatStayCss($style, '', ';' . implode(':;', $this->propertyNames($style)) . ':')
            + $this->differentlySetProperties($style, [$domElement]);
        $attributes = [];
        $remaining = [];

        foreach ($this->parseFilledDeclarations($style) as [$prop, $value]) {
            $propLower = $this->asciiLowercase($prop);

            if (!\array_key_exists($propLower, $pinned) && $this->isConvertibleProperty($propLower)) {
                $attributes[$propLower] = $value;

                continue;
            }

            $remaining[] = $prop . ':' . $value;
        }

        return [$attributes, $remaining];
    }

    /**
     * @param string $prop the property name to check
     *
     * @return bool true if the property is convertible, false otherwise
     */
    private function isConvertibleProperty(string $prop): bool
    {
        return null !== ConvertibleProperty::tryFrom($prop);
    }
}
