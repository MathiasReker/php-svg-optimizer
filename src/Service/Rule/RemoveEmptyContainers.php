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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\EmptyElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\TagMatcherTrait;

/**
 * Removes container elements that have no content and cannot be referenced, such as an empty `<defs>`, `<symbol>`
 * or `<mask>`. Containers emptied by the removal are removed as well.
 *
 * @no-named-arguments
 */
final readonly class RemoveEmptyContainers implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use EmptyElementRemovalTrait;
    use TagMatcherTrait;

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
        $this->removeElementsBottomUp($domDocument, $this->isRemovable(...));
    }

    /**
     * A filter on an empty group can still paint, for example a flood in user space, so such groups are kept.
     *
     * @param \DOMElement $domElement the element to check
     */
    private function isRemovable(\DOMElement $domElement): bool
    {
        return $this->isTagIn($domElement, $this->containerTags())
            && !$this->hasId($domElement)
            && $this->isEmptyElement($domElement)
            && !$domElement->hasAttribute(SvgAttribute::Filter->value)
            && !str_contains(mb_strtolower($domElement->getAttribute(SvgAttribute::Style->value)), SvgAttribute::Filter->value);
    }

    /**
     * @return list<string>
     */
    private function containerTags(): array
    {
        return array_map(static fn (SvgTag $svgTag): string => $svgTag->value, [
            SvgTag::ClipPath,
            SvgTag::Defs,
            SvgTag::G,
            SvgTag::Marker,
            SvgTag::Mask,
            SvgTag::Pattern,
            SvgTag::Switch,
            SvgTag::Symbol,
        ]);
    }
}
