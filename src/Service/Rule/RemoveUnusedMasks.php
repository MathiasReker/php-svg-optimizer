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

    /**
     * Optimizes the SVG \DOMDocument by removing unused masks and empty <defs> elements.
     *
     * @param \DOMDocument $domDocument the SVG DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        $this->removeUnusedMasks($domDocument, $domXPath);
        $this->removeEmptyDefs($domDocument);
    }

    /**
     * Removes <mask> elements that are not referenced anywhere in the SVG.
     *
     * @param \DOMDocument $domDocument the SVG DOM document
     * @param \DOMXPath    $domXPath    XPath instance for querying the document
     */
    private function removeUnusedMasks(\DOMDocument $domDocument, \DOMXPath $domXPath): void
    {
        $masksToRemove = [];

        foreach ($domDocument->getElementsByTagName(SvgAttribute::Mask->value) as $domNodeList) {
            $maskId = $domNodeList->getAttribute(SvgAttribute::Id->value);

            if ('' === $maskId) {
                $masksToRemove[] = $domNodeList;
                continue;
            }

            $references = $domXPath->query(\sprintf("//*[contains(@mask, 'url(#%s)')]", $maskId));
            $isReferenced = false !== $references && $references->length > 0;

            if (!$isReferenced) {
                $masksToRemove[] = $domNodeList;
            }
        }

        foreach ($masksToRemove as $maskToRemove) {
            $maskToRemove->parentNode?->removeChild($maskToRemove);
        }
    }

    /**
     * Removes empty <defs> elements from the SVG document.
     *
     * @param \DOMDocument $domDocument the SVG DOM document
     */
    private function removeEmptyDefs(\DOMDocument $domDocument): void
    {
        foreach ($domDocument->getElementsByTagName(SvgTag::Defs->value) as $domNodeList) {
            $hasChildren = false;

            foreach ($domNodeList->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $hasChildren = true;
                    break;
                }
            }

            if (!$hasChildren) {
                $domNodeList->parentNode?->removeChild($domNodeList);
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
