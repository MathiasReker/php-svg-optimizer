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
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataParserTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Path\PathDataTokenTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use MathiasReker\PhpSvgOptimizer\Svg\Element;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifyPathData implements PassInterface
{
    use AnimationTrait;
    use PathDataParserTrait;
    use PathDataSerializerTrait;
    use PathDataTokenTrait;
    use StyleSheetTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->maySelectByAttributeValue($domDocument)
            || \array_key_exists(Attribute::D->value, $this->animatedAttributeNames($domDocument))
        ) {
            return;
        }

        foreach ($domDocument->getElementsByTagName('*') as $element) {
            if (Element::Path->value === $element->localName && $element->hasAttribute(Attribute::D->value)) {
                $this->minifyPathData($element);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the path element
     */
    private function minifyPathData(\DOMElement $domElement): void
    {
        $data = $domElement->getAttribute(Attribute::D->value);
        [$segments, $unparsedCommands] = $this->parsePathData($data);
        $minified = $this->serializePathData($segments) . $this->minifyPathDataNumbers($unparsedCommands);

        if (mb_strlen($minified) < mb_strlen($data)) {
            $domElement->setAttribute(Attribute::D->value, $minified);
        }
    }
}
