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
final readonly class RemoveDataAttributes implements SvgOptimizerRuleInterface
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
     * Removes all `data-*` attributes from elements in the SVG document.
     *
     * These attributes are often used for custom data by scripts and are not
     * typically required for rendering the SVG. Removing them can reduce file
     * size, but it is considered a risky operation as it may break
     * functionality that relies on this data.
     *
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domNodeList = $domDocument->getElementsByTagName('*');

        foreach ($domNodeList as $domElement) {
            /** @var list<\DOMAttr> $attributes */
            $attributes = iterator_to_array($domElement->attributes, false);
            foreach ($attributes as $attribute) {
                if (0 === strcasecmp(mb_substr($attribute->name, 0, 5), 'data-')) {
                    $domElement->removeAttribute($attribute->name);
                }
            }
        }
    }
}
