<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Services\Rules;

use DOMDocument;
use MathiasReker\PhpSvgOptimizer\Contracts\Services\Rules\SvgOptimizerRuleInterface;

final class RemoveEmptyAttributes implements SvgOptimizerRuleInterface
{
    /**
     * Optimizes the provided DOMDocument by removing empty or whitespace-only attributes.
     *
     * This method iterates through all elements in the DOMDocument and removes any attributes
     * that are empty or contain only whitespace. The modified content is then saved back into the
     * provided DOMDocument object.
     *
     * @param \DOMDocument $domDocument The DOMDocument object to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $this->removeEmptyAttributes($domDocument);
    }

    /**
     * Removes attributes from the given DOMDocument that are empty or contain only whitespace.
     *
     * This private method directly modifies the DOMDocument by iterating through all elements
     * and removing attributes that either have an empty value or contain only whitespace characters.
     *
     * @param \DOMDocument $domDocument The DOMDocument object containing the SVG elements
     */
    private function removeEmptyAttributes(\DOMDocument $domDocument): void
    {
        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            foreach (iterator_to_array($domNodeList->attributes, true) as $attrName => $attrNode) {
                if ('' === preg_replace('/\s+/', '', $attrNode->value)) {
                    $domNodeList->removeAttribute($attrName);
                }
            }
        }
    }
}
