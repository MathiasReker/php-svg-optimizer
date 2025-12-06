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
final readonly class RemoveAriaAndRole implements SvgOptimizerRuleInterface
{
    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    /**
     * Remove all 'aria' attributes and the 'role' attribute from the SVG elements.
     *
     * @param \DOMDocument $domDocument The SVG \DOMDocument to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domxPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $nodes */
        $nodes = $domxPath->query('//*');

        foreach ($nodes as $node) {
            /** @var list<\DOMAttr> $attrs */
            $attrs = iterator_to_array($node->attributes, false);
            foreach ($attrs as $attr) {
                if (0 === strcasecmp($attr->name, 'role')
                    || 0 === strcasecmp(mb_substr($attr->name, 0, 5), 'aria-')) {
                    $node->removeAttribute($attr->name);
                }
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
