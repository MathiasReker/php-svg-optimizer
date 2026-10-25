<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Pass\PassInterface;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberListTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class RemoveWidthHeightAttributes implements PassInterface
{
    use AsciiCaseTrait;
    use NumberFormatterTrait;
    use NumberListTrait;

    private const int VIEW_BOX_LENGTH = 4;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        $svg = $domDocument->documentElement;

        if ($svg instanceof \DOMElement
            && str_contains($this->asciiLowercase($svg->nodeName), SvgTag::Svg->value)
            && $this->hasValidViewBox($svg)
        ) {
            $remove = [];
            foreach ($svg->attributes as $attr) {
                if (\in_array($this->asciiLowercase($attr->nodeName), [
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

    /**
     * @param \DOMElement $domElement the root element
     *
     * @return bool true if the element has a `viewBox` with a positive width and height, which then sizes the image
     */
    private function hasValidViewBox(\DOMElement $domElement): bool
    {
        $values = $this->parseNumberList($domElement->getAttribute(SvgAttribute::ViewBox->value), false);

        return self::VIEW_BOX_LENGTH === \count($values) && $this->isPositiveNumber($values[2]) && $this->isPositiveNumber($values[3]);
    }
}
