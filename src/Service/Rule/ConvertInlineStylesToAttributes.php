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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 */
final readonly class ConvertInlineStylesToAttributes implements SvgOptimizerRuleInterface
{
    use CssDeclarationTrait;
    use ElementTrait;

    private const string STYLED_ELEMENTS_QUERY = '//*[@style]';

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
        if ($this->hasStyleElement($domDocument)) {
            return;
        }

        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query(self::STYLED_ELEMENTS_QUERY);

        foreach ($domNodeList as $domElement) {
            if ($this->isSvgElement($domElement)) {
                $this->convertStyles($domElement);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element to process
     */
    private function convertStyles(\DOMElement $domElement): void
    {
        $style = $domElement->getAttribute(SvgAttribute::Style->value);

        if (!str_contains($style, ':') || str_contains($style, self::CSS_COMMENT_START)) {
            return;
        }

        if (!$this->isSplittableCssDeclarationBlock($style)) {
            return;
        }

        $remaining = $this->processDeclarations($domElement, $style);

        if ([] === $remaining) {
            $domElement->removeAttribute(SvgAttribute::Style->value);
        } else {
            $domElement->setAttribute(SvgAttribute::Style->value, implode('; ', $remaining));
        }
    }

    /**
     * @param string $style a style attribute value that can be split safely
     *
     * @return array<int, array{string, string}> the property name and value of each declaration with both, as written
     */
    private function parseDeclarations(string $style): array
    {
        return array_filter(
            $this->parseCssDeclarations($style),
            static fn (array $declaration): bool => '' !== $declaration[0] && '' !== $declaration[1],
        );
    }

    /**
     * @param string $style a style attribute value that can be split safely
     *
     * @return list<string> the declarations that stay in the style attribute
     */
    private function processDeclarations(\DOMElement $domElement, string $style): array
    {
        $pinned = $this->pinnedCssProperties($style, '', ';' . implode(':;', $this->cssPropertyNames($style)) . ':')
            + $this->differentlySetProperties($style, [$domElement]);
        $remaining = [];

        foreach ($this->parseDeclarations($style) as [$prop, $value]) {
            $propLower = $this->asciiLowercase($prop);

            if (!\array_key_exists($propLower, $pinned) && $this->isConvertibleProperty($propLower)) {
                $domElement->setAttribute($propLower, $value);

                continue;
            }

            $remaining[] = $prop . ':' . $value;
        }

        return $remaining;
    }

    /**
     * @param string $prop the property name to check
     *
     * @return bool true if the property is convertible, false otherwise
     */
    private function isConvertibleProperty(string $prop): bool
    {
        return null !== SvgInlineStyleProperty::tryFrom($prop);
    }
}
