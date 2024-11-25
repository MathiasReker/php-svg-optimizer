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

final class RemoveDefaultAttributes implements SvgOptimizerRuleInterface
{
    /**
     * Default attributes to be removed from the SVG document.
     *
     * This array contains attributes and their default values. If an attribute
     * is present in an SVG element with its default value, it will be removed.
     *
     * @var array<string, string>
     */
    private const DEFAULT_SVG_ATTRIBUTES = [
        'stroke' => 'none',
    ];

    /**
     * Remove default attributes from the SVG document.
     *
     * This method iterates through the predefined default attributes and removes them
     * from the SVG document if their values match the default values specified.
     *
     * @param \DOMDocument $domDocument The DOMDocument instance representing the SVG file to be optimized
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
