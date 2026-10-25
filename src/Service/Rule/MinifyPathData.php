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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataParserTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataTokenTrait;

/**
 * @internal
 *
 * @no-named-arguments
 */
final readonly class MinifyPathData implements PassInterface
{
    use ElementTrait;
    use NumberFormatterTrait;
    use PathDataParserTrait;
    use PathDataSerializerTrait;
    use PathDataTokenTrait;

    /**
     * @param \DOMDocument $domDocument the DOM document to optimize
     */
    #[\Override]
    public function apply(\DOMDocument $domDocument): void
    {
        if ($this->mayMatchAttributeValues($domDocument)
            || \array_key_exists(SvgAttribute::D->value, $this->animatedAttributeNames($domDocument))
        ) {
            return;
        }

        foreach ($domDocument->getElementsByTagName('*') as $domNodeList) {
            if (SvgTag::Path->value === $domNodeList->localName && $domNodeList->hasAttribute(SvgAttribute::D->value)) {
                $this->minifyPathData($domNodeList);
            }
        }
    }

    /**
     * @param \DOMElement $domElement the path element
     */
    private function minifyPathData(\DOMElement $domElement): void
    {
        $data = $domElement->getAttribute(SvgAttribute::D->value);
        [$segments, $unparsedCommands] = $this->parsePathData($data);
        $minified = $this->serializePathData($segments) . $this->minifyPathDataNumbers($unparsedCommands);

        if (mb_strlen($minified) < mb_strlen($data)) {
            $domElement->setAttribute(SvgAttribute::D->value, $minified);
        }
    }
}
