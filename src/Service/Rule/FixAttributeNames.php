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

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class FixAttributeNames implements SvgOptimizerRuleInterface
{
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
        $domNodeList = $domDocument->getElementsByTagName('*');

        $lookup = $this->getLookupTable();

        foreach ($domNodeList as $domElement) {
            $this->normalizeAttributes($domElement, $lookup);
        }
    }

    /**
     * @return array<string, string> a map of normalized names to canonical names
     */
    private function getLookupTable(): array
    {
        /** @var array<string, string>|null $lookup */
        static $lookup = null;

        if (null !== $lookup) {
            return $lookup;
        }

        $lookup = [];
        foreach (SvgAttribute::cases() as $case) {
            $lookup[$this->normalizeName($case->value)] = $case->value;
        }

        return $lookup;
    }

    /**
     * @param string $name the attribute name to normalize
     *
     * @return string the normalized name
     */
    private function normalizeName(string $name): string
    {
        return mb_strtolower(str_replace('-', '', $name));
    }

    /**
     * @param \DOMElement           $domElement the element of the attribute
     * @param \DOMAttr              $domAttr    the attribute to rename
     * @param array<string, string> $lookup     the normalization lookup table
     *
     * @return string the canonical name to rename the attribute to, or an empty string to keep it
     */
    private function canonicalName(\DOMElement $domElement, \DOMAttr $domAttr, array $lookup): string
    {
        $canonicalName = $lookup[$this->normalizeName($domAttr->name)] ?? $domAttr->name;

        $exists = null === $domAttr->namespaceURI
            ? $domElement->hasAttribute($canonicalName)
            : $domElement->hasAttributeNS($domAttr->namespaceURI, $canonicalName);

        return $exists ? '' : $canonicalName;
    }

    /**
     * @param \DOMElement           $domElement the element to process
     * @param array<string, string> $lookup     the normalization lookup table
     */
    private function normalizeAttributes(\DOMElement $domElement, array $lookup): void
    {
        $attributes = iterator_to_array($domElement->attributes, false);

        foreach ($attributes as $attribute) {
            $canonicalName = $this->canonicalName($domElement, $attribute, $lookup);

            if ('' === $canonicalName) {
                continue;
            }

            $value = $attribute->value;
            $namespaceURI = $attribute->namespaceURI;
            $localName = $attribute->localName;

            if (null !== $namespaceURI && null !== $localName) {
                $domElement->removeAttributeNS($namespaceURI, $localName);
                $domElement->setAttributeNS($namespaceURI, $canonicalName, $value);
            } else {
                $domElement->removeAttribute($attribute->name);
                $domElement->setAttribute($canonicalName, $value);
            }
        }
    }
}
