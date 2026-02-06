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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @no-named-arguments
 */
final readonly class removeNonStandardTags implements SvgOptimizerRuleInterface
{
    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);
        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query('//*');

        $allowed = $this->getAllowedTagLookup();

        $elements = iterator_to_array($domNodeList, false);

        foreach ($elements as $element) {
            $normalized = $this->normalizeName($element->tagName);

            if (\array_key_exists($normalized, $allowed)) {
                continue; // standard tag, skip
            }

            $parent = $element->parentNode;
            if (null === $parent) {
                continue;
            }

            while (null !== $element->firstChild) {
                $parent->insertBefore($element->firstChild, $element);
            }

            $parent->removeChild($element);
        }
    }

    /**
     * Build a lookup table of allowed SVG tag names.
     *
     * Pseudo-tags (values starting with `#`) are ignored.
     *
     * @return array<string, true> A lookup map of normalized SVG tag names
     */
    private function getAllowedTagLookup(): array
    {
        $lookup = [];

        foreach (SvgTag::cases() as $tag) {
            if (str_starts_with($tag->value, '#')) {
                continue; // skip pseudo-tags
            }

            $lookup[$this->normalizeName($tag->value)] = true;
        }

        return $lookup;
    }

    /**
     * Normalize an SVG tag name for comparison.
     *
     * - Namespace prefixes are stripped (e.g. `svg:rect` → `rect`)
     * - The resulting name is lowercased for case-insensitive matching
     *
     * Note: This method does NOT validate namespace URIs.
     *
     * @param string $name The raw tag name from the DOM
     *
     * @return string The normalized tag name
     */
    private function normalizeName(string $name): string
    {
        $pos = mb_strrpos($name, ':');
        if (false !== $pos) {
            $name = mb_substr($name, $pos + 1);
        }

        return mb_strtolower($name);
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
