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
final readonly class removeNonStandardSvgTags implements SvgOptimizerRuleInterface
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
     * @return array<string, true>
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

    private function normalizeName(string $name): string
    {
        return mb_strtolower($name);
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
