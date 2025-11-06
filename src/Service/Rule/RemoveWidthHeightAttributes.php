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
final readonly class RemoveWidthHeightAttributes implements SvgOptimizerRuleInterface
{
    /**
     * Optimizes the SVG \DOMDocument by removing `width` and `height` attributes
     * from the root <svg> element if they exist.
     *
     * @param \DOMDocument $domDocument the SVG DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $svg = $domDocument->documentElement;

        if ($svg instanceof \DOMElement && str_contains(mb_strtolower($svg->nodeName), 'svg')) {
            $remove = [];
            foreach ($svg->attributes as $attr) {
                if (\in_array(mb_strtolower($attr->nodeName), ['width', 'height'], true)) {
                    $remove[] = $attr->nodeName;
                }
            }

            foreach ($remove as $attrName) {
                $svg->removeAttribute($attrName);
            }
        }
    }

    #[\Override]
    public function shouldCheckSize(): bool
    {
        return false;
    }
}
