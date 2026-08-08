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
final readonly class RemoveNonStandardTags implements SvgOptimizerRuleInterface
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

        $allowed = $this->getAllowedTagLookup();

        $elements = iterator_to_array($domNodeList, false);

        foreach ($elements as $element) {
            $normalized = $this->normalizeName($element->tagName);

            if (\array_key_exists($normalized, $allowed)) {
                continue;
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
     * @return array<string, true> a map where keys are normalized tag names
     */
    private function getAllowedTagLookup(): array
    {
        $lookup = [];

        foreach (SvgTag::cases() as $tag) {
            if (str_starts_with($tag->value, '#')) {
                continue;
            }

            $lookup[$this->normalizeName($tag->value)] = true;
        }

        return $lookup;
    }

    /**
     * @param string $name the tag name to normalize
     *
     * @return string the normalized tag name
     */
    private function normalizeName(string $name): string
    {
        $pos = mb_strrpos($name, ':');
        if (false !== $pos) {
            $name = mb_substr($name, $pos + 1);
        }

        return mb_strtolower($name);
    }
}
