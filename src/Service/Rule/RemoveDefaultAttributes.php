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
final readonly class RemoveDefaultAttributes implements SvgOptimizerRuleInterface
{
    private const array DEFAULT_SVG_ATTRIBUTES = [
        SvgAttribute::Stroke->value => 'none',
        SvgAttribute::StrokeWidth->value => '1',
        SvgAttribute::StrokeLinecap->value => 'butt',
        SvgAttribute::StrokeLinejoin->value => 'miter',
        SvgAttribute::StrokeMiterlimit->value => '4',
    ];

    private const string XPATH_ALL_ATTRIBUTES = '//@*';

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    #[\Override]
    public static function shouldCheckSize(): bool
    {
        return false;
    }

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        /** @var \DOMNodeList<\DOMAttr> $attributes */
        $attributes = $domXPath->query(self::XPATH_ALL_ATTRIBUTES);

        /** @var \DOMAttr $attr */
        foreach ($attributes as $attr) {
            if (!\array_key_exists($attr->name, self::DEFAULT_SVG_ATTRIBUTES)) {
                continue;
            }

            $element = $attr->ownerElement;
            if (null === $element) {
                continue;
            }

            if ($attr->value === self::DEFAULT_SVG_ATTRIBUTES[$attr->name]) {
                $element->removeAttribute($attr->name);
            }
        }
    }
}
