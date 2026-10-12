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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RenameElementTrait;

/**
 * Converts an `<ellipse>` whose radii are equal to a `<circle>`.
 *
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConvertEllipseToCircle implements SvgOptimizerRuleInterface
{
    use ElementTrait;
    use NumberFormatterTrait;
    use RenameElementTrait;

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

        $animationTargetIds = $this->animationTargetIds($domDocument);

        foreach ($this->elementsNamed($domDocument, SvgTag::Ellipse->value) as $domElement) {
            if ($this->hasEqualRadii($domElement)
                && !\array_key_exists($domElement->getAttribute(SvgAttribute::Id->value), $animationTargetIds)
            ) {
                $this->convert($domElement);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the ellipse element
     */
    private function hasEqualRadii(\DOMElement $domElement): bool
    {
        $rx = $domElement->getAttribute(SvgAttribute::Rx->value);
        $ry = $domElement->getAttribute(SvgAttribute::Ry->value);

        return !$this->hasElementChildren($domElement)
            && !$domElement->hasAttribute(SvgAttribute::Style->value)
            && $this->isPlainNumber($rx)
            && $this->isPlainNumber($ry)
            && $this->minifyNumber(trim($rx)) === $this->minifyNumber(trim($ry));
    }

    /**
     * @param \DOMElement $domElement the ellipse element
     */
    private function convert(\DOMElement $domElement): void
    {
        $radius = $domElement->getAttribute(SvgAttribute::Rx->value);

        $domElement->removeAttribute(SvgAttribute::Rx->value);
        $domElement->removeAttribute(SvgAttribute::Ry->value);
        $domElement->setAttribute(SvgAttribute::R->value, $radius);

        $this->renameElement($domElement, SvgTag::Circle->value);
    }
}
