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
final readonly class RemoveWidthHeightAttributes implements SvgOptimizerRuleInterface
{
    #[\Override]
    public static function isRisky(): bool
    {
        return true;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * Removes the `width` and `height` attributes from the root `<svg>` element.
     *
     * Removing these attributes allows the SVG to scale fluidly within its
     * container, which is often desirable for responsive design. However, this
     * is considered a risky operation as it can alter the intended display
     * size of the SVG if a `viewBox` is not properly set.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $svg = $domDocument->documentElement;

        if ($svg instanceof \DOMElement && str_contains(mb_strtolower($svg->nodeName), SvgTag::Svg->value)) {
            $remove = [];
            foreach ($svg->attributes as $attr) {
                if (\in_array(mb_strtolower($attr->nodeName), [
                    SvgAttribute::Width->value,
                    SvgAttribute::Height->value,
                ], true)) {
                    $remove[] = $attr->nodeName;
                }
            }

            foreach ($remove as $attrName) {
                $svg->removeAttribute($attrName);
            }
        }
    }
}
