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
    /**
     * Default attributes to be removed from the SVG document.
     *
     * This array contains attributes and their default values. If an attribute
     * is present in an SVG element with its default value, it will be removed.
     */
    private const array DEFAULT_SVG_ATTRIBUTES = [
        SvgAttribute::Stroke->value => 'none',
        SvgAttribute::StrokeWidth->value => '1',
        SvgAttribute::StrokeLinecap->value => 'butt',
        SvgAttribute::StrokeLinejoin->value => 'miter',
        SvgAttribute::StrokeMiterlimit->value => '4',
    ];

    #[\Override]
    public static function isRisky(): bool
    {
        return false;
    }

    /**
     * Remove default attributes from the SVG document.
     *
     * This method iterates through the predefined default attributes and removes them
     * from the SVG document if their values match the default values specified.
     *
     * @param \DOMDocument $domDocument The \DOMDocument instance representing the SVG file to be optimized
     */
    #[\Override]
    public function optimize(\DOMDocument $domDocument): void
    {
        $domXPath = new \DOMXPath($domDocument);

        foreach (self::DEFAULT_SVG_ATTRIBUTES as $attribute => $defaultValue) {
            /** @var \DOMNodeList<\DOMAttr> $domNodeList */
            $domNodeList = $domXPath->query('//@' . $attribute);

            foreach ($domNodeList as $domAttr) {
                $parentNode = $domAttr->ownerElement;
                if ($parentNode instanceof \DOMElement && $domAttr->value === $defaultValue) {
                    $parentNode->removeAttribute($attribute);
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
