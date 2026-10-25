<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Pass;

use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRenameTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Number\NumberLiteralTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class ConvertEllipsesToCircles implements PassInterface
{
    use AnimationTrait;
    use ElementRenameTrait;
    use ElementTrait;
    use NumberLiteralTrait;
    use ScriptingTrait;
    use StyleSheetTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->hasStyleSheet($domDocument) || $this->hasScriptOrEventHandler($domDocument)) {
            return;
        }

        $animationTargets = $this->animationTargets($domDocument);

        foreach ($this->elementsByLocalName($domDocument, Element::Ellipse->value) as $domElement) {
            if ($this->hasEqualRadii($domElement) && !\in_array($domElement, $animationTargets, true)) {
                $this->convert($domDocument, $domElement);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the ellipse element
     */
    private function hasEqualRadii(\DOMElement $domElement): bool
    {
        $rx = $domElement->getAttribute(Attribute::Rx->value);
        $ry = $domElement->getAttribute(Attribute::Ry->value);

        return $this->isSvgElement($domElement)
            && !$this->hasElementChildren($domElement)
            && !$domElement->hasAttribute(Attribute::Style->value)
            && $this->isPlainNumber($rx)
            && $this->isPlainNumber($ry)
            && $this->minifyNumber(trim($rx)) === $this->minifyNumber(trim($ry));
    }

    /**
     * @param \DOMElement $domElement the ellipse element
     */
    private function convert(\DOMDocument $domDocument, \DOMElement $domElement): void
    {
        $radius = $domElement->getAttribute(Attribute::Rx->value);

        $domElement->removeAttribute(Attribute::Rx->value);
        $domElement->removeAttribute(Attribute::Ry->value);
        $domElement->setAttribute(Attribute::R->value, $radius);

        $this->renameElement($domDocument, $domElement, Element::Circle->value);
    }
}
