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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PresentationAttributeCleanupTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveNonInheritableGroupAttributes implements SvgOptimizerRuleInterface
{
    use PresentationAttributeCleanupTrait;

    private const array GROUP_IGNORED_ATTRIBUTES = [
        SvgAttribute::AlignmentBaseline->value,
        SvgAttribute::BaselineShift->value,
        SvgAttribute::Cx->value,
        SvgAttribute::Cy->value,
        SvgAttribute::D->value,
        SvgAttribute::FloodColor->value,
        SvgAttribute::FloodOpacity->value,
        SvgAttribute::Height->value,
        SvgAttribute::LightingColor->value,
        SvgAttribute::MaskType->value,
        SvgAttribute::R->value,
        SvgAttribute::Rx->value,
        SvgAttribute::Ry->value,
        SvgAttribute::StopColor->value,
        SvgAttribute::StopOpacity->value,
        SvgAttribute::VectorEffect->value,
        SvgAttribute::Width->value,
        SvgAttribute::X->value,
        SvgAttribute::Y->value,
    ];

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
        if ($this->hasStyleElement($domDocument)) {
            return;
        }

        $animated = $this->animatedAttributeNames($domDocument);

        foreach ($this->svgElements($domDocument) as $domElement) {
            if (SvgTag::G->value === $domElement->localName && !$this->hasInheritingDescendant($domElement)) {
                $this->removeUnanimatedAttributes($domElement, self::GROUP_IGNORED_ATTRIBUTES, $animated);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the group to check
     *
     * @return bool true if a descendant uses `inherit`, which also takes non-inherited values from the group
     */
    private function hasInheritingDescendant(\DOMElement $domElement): bool
    {
        foreach ($domElement->getElementsByTagName('*') as $domNodeList) {
            if ($this->hasInheritValue($domNodeList)) {
                return true;
            }
        }

        return false;
    }
}
