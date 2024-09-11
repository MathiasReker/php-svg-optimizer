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

class RemoveDefaultAttributes implements SvgOptimizerRuleInterface
{
    /**
     * Default attributes to be removed from the SVG document.
     *
     * @var array<string, string>
     */
    private const DEFAULT_SVG_ATTRIBUTES = [
        'fill' => 'none',
        'stroke' => 'none',
    ];

    /**
     * Remove default attributes from the SVG document.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        foreach (self::DEFAULT_SVG_ATTRIBUTES as $attribute => $defaultValue) {
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
