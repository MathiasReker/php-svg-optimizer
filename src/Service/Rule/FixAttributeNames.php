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
     * Normalizes the case of SVG attribute names throughout the document.
     *
     * This method iterates over all elements and their attributes, correcting
     * the casing of attribute names to match the standard defined in the
     * `SvgAttribute` enum. For example, `viewbox` would be corrected to `viewBox`.
     *
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
     * Creates a lookup table for efficient attribute name normalization.
     *
     * The keys of the returned array are normalized attribute names (lowercase,
     * with dashes removed), and the values are the canonical, case-sensitive
     * names from the `SvgAttribute` enum.
     *
     * @return array<string, string> a map of normalized names to canonical names
     */
    private function getLookupTable(): array
    {
        /** @var array<string, string>|null $lookup */
        $lookup = [];
        foreach (SvgAttribute::cases() as $case) {
            $lookup[$this->normalizeName($case->value)] = $case->value;
        }

        return $lookup;
    }

    /**
     * Normalizes an attribute name for lookup.
     *
     * This helper function converts a name to a consistent format (lowercase,
     * no dashes) to be used as a key in the lookup table.
     *
     * @param string $name the attribute name to normalize
     *
     * @return string the normalized name
     */
    private function normalizeName(string $name): string
    {
        return mb_strtolower(str_replace('-', '', $name));
    }

    /**
     * Corrects the attribute names for a single DOM element.
     *
     * It iterates over the element's attributes, and for any attribute whose
     * name does not match the canonical form, it replaces it while preserving
     * its value and namespace.
     *
     * @param \DOMElement           $domElement the element to process
     * @param array<string, string> $lookup     the normalization lookup table
     */
    private function normalizeAttributes(\DOMElement $domElement, array $lookup): void
    {
        $attributes = iterator_to_array($domElement->attributes, false);

        foreach ($attributes as $attribute) {
            $normalized = $this->normalizeName($attribute->name);

            if (!\array_key_exists($normalized, $lookup)) {
                continue;
            }

            if ($attribute->name === $lookup[$normalized]) {
                continue;
            }

            $canonicalName = $lookup[$normalized];
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
