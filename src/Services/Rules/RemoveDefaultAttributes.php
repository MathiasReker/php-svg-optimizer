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

class RemoveDefaultAttributes implements SvgOptimizerRuleInterface
{
    /**
     * Remove default attributes from the SVG document.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        $defaultAttributes = [
            'fill' => 'none',
            'stroke' => 'none',
        ];

        foreach ($defaultAttributes as $attribute => $defaultValue) {
            /**
             * @var \DOMNodeList<\DOMAttr> $nodes
             */
            $nodes = $domXPath->query('//@' . $attribute);

            /**
             * @var \DOMAttr $node
             */
            foreach ($nodes as $node) {
                $parentNode = $node->ownerElement;
                if ($parentNode instanceof \DOMElement && $node->value === $defaultValue) {
                    $parentNode->removeAttribute($attribute);
                }
            }
        }
    }
}
