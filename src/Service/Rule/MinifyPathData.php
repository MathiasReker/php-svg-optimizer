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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataParserTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataSerializerTrait;

/**
 * Rewrites path data in its shortest form: drops repeated command letters and unneeded separators, shortens number
 * literals, and writes relative linetos along one axis as `h`/`v`. Coordinates are never recalculated or rounded.
 *
 * @no-named-arguments
 */
final readonly class MinifyPathData implements SvgOptimizerRuleInterface
{
    use NumberFormatterTrait;
    use PathDataParserTrait;
    use PathDataSerializerTrait;

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
        $segments = $this->parsePathData($data);

        if ([] === $segments) {
            return;
        }

        $minified = $this->serializePathData($segments);

        if (mb_strlen($minified) < mb_strlen($data)) {
            $domElement->setAttribute(SvgAttribute::D->value, $minified);
        }
    }
}
