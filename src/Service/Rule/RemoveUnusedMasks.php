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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;

/**
 * @no-named-arguments
 */
final readonly class RemoveUnusedMasks implements SvgOptimizerRuleInterface
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
     * Removes unused `<mask>` elements and empty `<defs>` containers from the SVG.
     *
     * This method first identifies and removes any `<mask>` element that is not
     * referenced by a `mask` attribute elsewhere in the document. It then
     * cleans up any `<defs>` element that has become empty as a result.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        $this->removeUnusedMasks($domDocument, $domXPath);
        $this->removeEmptyDefs($domDocument);
    }

    /**
     * Finds and removes `<mask>` elements that are not referenced.
     *
     * It iterates through all `<mask>` elements. If a mask has no `id` or if its
     * `id` is not used in any `mask` attribute, the mask element is removed.
     *
     * @param \DOMDocument $domDocument the DOM document to modify
     * @param \DOMXPath    $domXPath    the XPath object for querying the document
     */
    private function removeUnusedMasks(\DOMDocument $domDocument, \DOMXPath $domXPath): void
    {
        $domNodeList = $domDocument->getElementsByTagName(SvgTag::Mask->value);

        for ($i = $domNodeList->length - 1; $i >= 0; --$i) {
            $mask = $domNodeList->item($i);
            if (!$mask instanceof \DOMElement) {
                continue;
            }

            $maskId = $mask->getAttribute(SvgAttribute::Id->value);

            if ('' === $maskId) {
                $mask->parentNode?->removeChild($mask);
                continue;
            }

            $query = \sprintf('//*[contains(@%s, "url(#%s)")]', SvgAttribute::Mask->value, $maskId);
            $references = $domXPath->query($query);

            if (false !== $references && 0 === $references->length) {
                $mask->parentNode?->removeChild($mask);
            }
        }
    }

    /**
     * Removes `<defs>` elements that do not contain any child elements.
     *
     * @param \DOMDocument $domDocument the DOM document to modify
     */
    private function removeEmptyDefs(\DOMDocument $domDocument): void
    {
        $domNodeList = $domDocument->getElementsByTagName(SvgTag::Defs->value);

        for ($i = $domNodeList->length - 1; $i >= 0; --$i) {
            $def = $domNodeList->item($i);
            if (!$def instanceof \DOMElement) {
                continue;
            }

            foreach ($def->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    continue 2;
                }
            }

            $def->parentNode?->removeChild($def);
        }
    }
}
