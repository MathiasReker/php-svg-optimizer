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
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMElement> $domNodeList */
        $domNodeList = $domXPath->query('//*');

        foreach ($domNodeList as $domElement) {
            /** @var list<\DOMAttr> $attributes */
            $attributes = iterator_to_array($domElement->attributes, false);
            foreach ($attributes as $attribute) {
                if (0 === strcasecmp($attribute->name, SvgAttribute::Role->value)
                    || 0 === strcasecmp(mb_substr($attribute->name, 0, 5), 'aria-')) {
                    $domElement->removeAttribute($attribute->name);
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
