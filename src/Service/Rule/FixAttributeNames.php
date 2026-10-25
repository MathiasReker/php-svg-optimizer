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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgElementAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class FixAttributeNames implements SvgOptimizerRuleInterface
{
    use ElementTrait;

    private const string EVENT_HANDLER_PREFIX = 'on';

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
            if (!$this->isForeignContent($domElement)) {
                $this->normalizeAttributes($domElement, $lookup);
            }
        }
    }

    /**
     * @return array<string, string> a map of normalized names to canonical names
     */
    private function getLookupTable(): array
    {
        $names = SvgAttribute::values();

        return array_combine(array_map($this->normalizeName(...), $names), $names);
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
     * @param \DOMElement $domElement the element of the attribute
     * @param \DOMAttr    $domAttr    the attribute to check
     *
     * @return bool true if the attribute is in no namespace or the XLink namespace, and is not already a name that the
     *              element defines, such as `playbackorder` beside the SVG Tiny `playbackOrder`
     */
    private function mayRename(\DOMElement $domElement, \DOMAttr $domAttr): bool
    {
        return \in_array($domAttr->namespaceURI, [null, SvgNamespace::Xlink->value], true)
            && !\array_key_exists($domAttr->name, SvgElementAttributes::forElement((string) $domElement->localName));
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

        if ($canonicalName === $domAttr->name
            || str_starts_with($canonicalName, self::EVENT_HANDLER_PREFIX)
            || !$this->mayRename($domElement, $domAttr)
        ) {
            return '';
        }

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
