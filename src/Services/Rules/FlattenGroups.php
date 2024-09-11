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

class FlattenGroups implements SvgOptimizerRuleInterface
{
    /**
     * Flatten groups in the SVG document, applying attributes to child elements if necessary.
     *
     * @param \DOMDocument $domDocument the DOMDocument instance representing the SVG file to be optimized
     */
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        $domXPath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        /**
         * @var \DOMNodeList<\DOMElement> $groups
         */
        $groups = $domXPath->query('//svg:g');

        foreach ($groups as $group) {
            if ($group->hasAttributes()) {
                $this->applyGroupAttributesToChildren($group);
            }

            $this->flattenGroup($group);
        }
    }

    /**
     * Apply the group's attributes to its child elements.
     *
     * @param \DOMElement $domElement the group element whose attributes will be applied to its children
     */
    private function applyGroupAttributesToChildren(\DOMElement $domElement): void
    {
        /**
         * @var \DOMElement $child
         */
        foreach ($domElement->childNodes as $child) {
            foreach ($domElement->attributes as $attr) {
                /**
                 * @var \DOMAttr $attr
                 */
                if ($child->hasAttribute($attr->nodeName)) {
                    continue;
                }

                if (!\is_string($attr->nodeValue)) {
                    continue;
                }

                $child->setAttribute($attr->nodeName, $attr->nodeValue);
            }
        }
    }

    /**
     * Flatten the group by moving its children up and removing the group.
     *
     * @param \DOMElement $domElement the group element to flatten
     */
    private function flattenGroup(\DOMElement $domElement): void
    {
        $children = [];

        foreach ($domElement->childNodes as $child) {
            $children[] = $child;
        }

        /**
         * @var \DOMNode $parentNode
         */
        $parentNode = $domElement->parentNode;
        foreach ($children as $child) {
            $parentNode->insertBefore($child, $domElement);
        }

        $parentNode->removeChild($domElement);
    }
}
