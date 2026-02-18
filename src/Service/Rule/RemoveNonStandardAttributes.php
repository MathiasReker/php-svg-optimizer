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
final readonly class RemoveNonStandardAttributes implements SvgOptimizerRuleInterface
{
    /**
     * XPath query to select all elements that have any attribute.
     */
    private const string XPATH_ALL_WITH_ATTRIBUTES = '//*[@*]';

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
     * Removes attributes that are not part of the standard SVG specification.
     *
     * This method iterates through all attributes on all elements and removes
     * any that are not defined in the `SvgAttribute` enum. It explicitly
     * preserves `xml:*`, `xlink:*`, and `data-*` attributes.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $allowed = $this->getAllowedLookup();
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = $domXPath->query(self::XPATH_ALL_WITH_ATTRIBUTES);

        foreach ($elements as $element) {
            $this->removeNonStandardAttributesFromElement($element, $allowed);
        }
    }

    /**
     * Creates a lookup table of allowed SVG attribute names.
     *
     * This is used for efficient checking of whether an attribute is standard.
     *
     * @return array<string, true> a map where keys are the allowed attribute names
     */
    private function getAllowedLookup(): array
    {
        /** @var array<string, true>|null $lookup */
        static $lookup = null;

        if (null === $lookup) {
            $lookup = [];
            foreach (SvgAttribute::cases() as $attr) {
                $lookup[$attr->value] = true;
            }
        }

        return $lookup;
    }

    /**
     * Removes non-standard attributes from a single DOM element.
     *
     * @param \DOMElement         $domElement the element to process
     * @param array<string, true> $allowed    a lookup map of allowed attribute names
     */
    private function removeNonStandardAttributesFromElement(\DOMElement $domElement, array $allowed): void
    {
        for ($i = $domElement->attributes->length - 1; $i >= 0; --$i) {
            $attr = $domElement->attributes->item($i);
            if (!$attr instanceof \DOMAttr) {
                continue;
            }

            if ($this->isAttributeAllowed($attr->name, $allowed)) {
                continue;
            }

            $domElement->removeAttributeNode($attr);
        }
    }

    /**
     * Checks if an attribute is allowed based on its name.
     *
     * @param string              $name    the name of the attribute
     * @param array<string, true> $allowed a lookup map of allowed attribute names
     *
     * @return bool true if the attribute is allowed, false otherwise
     */
    private function isAttributeAllowed(string $name, array $allowed): bool
    {
        return str_starts_with($name, 'xml:')
            || str_starts_with($name, 'xlink:')
            || str_starts_with($name, 'data-')
            || \array_key_exists($name, $allowed);
    }
}
