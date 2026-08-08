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

/**
 * @no-named-arguments
 */
final readonly class RemoveDuplicateElements implements SvgOptimizerRuleInterface
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
     *
     * @throws \JsonException
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeDuplicateElements($domDocument);
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to process
     *
     * @throws \JsonException
     */
    private function removeDuplicateElements(\DOMDocument $domDocument): void
    {
        $elements = iterator_to_array($domDocument->getElementsByTagName('*'), true);

        $seen = [];

        foreach ($elements as $element) {
            $this->normalizeAttributes($element);

            $parent = $element->parentNode;
            if (!$parent instanceof \DOMElement) {
                continue;
            }

            $key = spl_object_hash($parent) . '::' . $this->buildSignature($element);

            if (\array_key_exists($key, $seen)) {
                $parent->removeChild($element);
            } else {
                $seen[$key] = true;
            }
        }
    }

    /**
     * @param \DOMElement $domElement the element whose attributes to normalize
     */
    private function normalizeAttributes(\DOMElement $domElement): void
    {
        foreach ($domElement->attributes as $attribute) {
            $trimmedValue = trim((string) $attribute->nodeValue);
            if ($trimmedValue !== $attribute->nodeValue) {
                $domElement->setAttribute($attribute->nodeName, $trimmedValue);
            }
        }
    }

    /**
     * @throws \JsonException
     */
    private function buildSignature(\DOMElement $domElement): string
    {
        $attrs = [];

        foreach ($domElement->attributes as $attribute) {
            $attrs[$attribute->nodeName] = trim((string) $attribute->nodeValue);
        }

        ksort($attrs);

        $children = [];

        foreach ($domElement->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $this->buildSignature($child);
            } elseif ($child instanceof \DOMText) {
                $children[] = trim((string) $child->nodeValue);
            }
        }

        return json_encode(
            [
                'tag' => $domElement->tagName,
                'attrs' => $attrs,
                'children' => $children,
            ],
            \JSON_THROW_ON_ERROR
        );
    }
}
