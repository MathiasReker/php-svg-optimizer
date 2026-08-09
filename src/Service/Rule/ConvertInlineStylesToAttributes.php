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

final readonly class ConvertInlineStylesToAttributes implements SvgOptimizerRuleInterface
{
    /**
     * @see https://regex101.com/r/Wlb5xS/1
     */
    private const string PROPERTY_NAME_REGEX = '/^[a-z_-][a-z0-9_-]*$/i';

    /**
     * @see https://regex101.com/r/UUdVJ3/1
     */
    private const string STYLE_SPLIT_REGEX = '/\s*;\s*/';

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
        foreach ($this->getElementsWithStyle($domDocument) as $domElement) {
            $this->convertStyles($domElement);
        }
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to search
     *
     * @return list<\DOMElement> a list of elements with inline styles
     */
    private function getElementsWithStyle(\DOMDocument $domDocument): array
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domXPath->query('//*[@style]');

        return iterator_to_array($nodes, false);
    }

    /**
     * @param \DOMElement $domElement the element to process
     */
    private function convertStyles(\DOMElement $domElement): void
    {
        $style = trim($domElement->getAttribute(SvgAttribute::Style->value));

        if ('' === $style || !str_contains($style, ':')) {
            return;
        }

        $declarations = preg_split(self::STYLE_SPLIT_REGEX, $style, -1, \PREG_SPLIT_NO_EMPTY);
        if (false === $declarations) {
            return;
        }

        $remaining = $this->processDeclarations($domElement, $declarations);

        if ([] === $remaining) {
            $domElement->removeAttribute(SvgAttribute::Style->value);
        } else {
            $domElement->setAttribute(SvgAttribute::Style->value, implode('; ', $remaining));
        }
    }

    /**
     * @param list<string> $declarations
     *
     * @return list<string>
     */
    private function processDeclarations(\DOMElement $domElement, array $declarations): array
    {
        $remaining = [];

        foreach ($declarations as $declaration) {
            [$prop, $value] = $this->parseDeclaration($declaration);

            if ('' === $prop) {
                continue;
            }

            if ($this->isConvertibleProperty($prop)) {
                if (!$domElement->hasAttribute($prop)) {
                    $domElement->setAttribute($prop, $value);
                }

                continue;
            }

            $remaining[] = $prop . ':' . $value;
        }

        return $remaining;
    }

    /**
     * @return array{string, string}
     */
    private function parseDeclaration(string $declaration): array
    {
        $declaration = trim($declaration);

        if ('' === $declaration || !str_contains($declaration, ':')) {
            return ['', ''];
        }

        [$prop, $value] = explode(':', $declaration, 2);

        $prop = mb_strtolower(trim($prop));
        $value = trim($value);

        if ('' === $prop || '' === $value) {
            return ['', ''];
        }

        return [$prop, $value];
    }

    /**
     * @param string $prop the property name to check
     *
     * @return bool true if the property is convertible, false otherwise
     */
    private function isConvertibleProperty(string $prop): bool
    {
        return $this->isValidPropertyName($prop) && \array_key_exists($prop, $this->getConvertibleLookup());
    }

    /**
     * @return array<string, int> a map for quick property lookup
     */
    private function getConvertibleLookup(): array
    {
        /** @var array<string, int>|null $lookup */
        static $lookup = null;

        return $lookup ??= array_flip(SvgInlineStyleProperty::values());
    }

    /**
     * @param string $prop the property name to validate
     *
     * @return bool true if the property name is valid
     */
    private function isValidPropertyName(string $prop): bool
    {
        return 1 === preg_match(self::PROPERTY_NAME_REGEX, $prop);
    }
}
