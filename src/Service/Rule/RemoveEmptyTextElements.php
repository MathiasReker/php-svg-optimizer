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
final readonly class RemoveEmptyTextElements implements SvgOptimizerRuleInterface
{
    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    /**
     * Optimize the SVG by removing empty text-related elements recursively.
     *
     * @param \DOMDocument $domDocument the SVG DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        if ($domDocument->documentElement instanceof \DOMElement) {
            $this->removeEmptyTextRecursive($domDocument->documentElement);
        }
    }

    /**
     * Recursively traverse the DOM tree and remove empty text elements.
     *
     * Processes child nodes in reverse order to allow safe removal during iteration.
     *
     * @param \DOMNode $domNode the DOM node to process
     */
    private function removeEmptyTextRecursive(\DOMNode $domNode): void
    {
        if (!$domNode->hasChildNodes()) {
            return;
        }

        for ($i = $domNode->childNodes->length - 1; $i >= 0; --$i) {
            $child = $domNode->childNodes->item($i);
            if (null === $child) {
                continue;
            }

            if (!$child instanceof \DOMElement) {
                $this->removeEmptyTextRecursive($child);
                continue;
            }

            $this->removeEmptyTextRecursive($child);

            $this->removeIfEmpty($child);
        }
    }

    /**
     * Remove a text-related element if it meets the empty criteria.
     *
     * - 'text' or 'tspan' elements with no child nodes are removed.
     * - 'tref' elements with an empty 'xlink:href' attribute are removed.
     *
     * @param \DOMElement $domElement the DOM element to check and remove if empty
     */
    private function removeIfEmpty(\DOMElement $domElement): void
    {
        $shouldRemove = match ($domElement->tagName) {
            'text', 'tspan' => 0 === $domElement->childNodes->length,
            'tref' => '' === $domElement->getAttribute('xlink:href'),
            default => false,
        };

        if ($shouldRemove) {
            $domElement->parentNode?->removeChild($domElement);
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return true;
    }
}
