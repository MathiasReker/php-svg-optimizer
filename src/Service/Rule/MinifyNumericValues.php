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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;

/**
 * Shortens single-number attribute values, such as `stroke-width="01.50px"` to `stroke-width="1.5"`. Only the
 * notation changes: leading and trailing zeros and a `+` sign are dropped, and `px` is dropped from lengths, as a
 * pixel equals one user unit. Values are never rounded.
 *
 * @no-named-arguments
 */
final readonly class MinifyNumericValues implements SvgOptimizerRuleInterface
{
    use NumberFormatterTrait;

    /**
     * Attributes holding a length, where `px` equals a unitless value.
     */
    private const array LENGTH_ATTRIBUTES = [
        'cx', 'cy', 'dx', 'dy', 'font-size', 'fr', 'fx', 'fy', 'height', 'letter-spacing', 'markerHeight',
        'markerWidth', 'r', 'refX', 'refY', 'rx', 'ry', 'startOffset', 'stroke-dashoffset', 'stroke-width',
        'textLength', 'width', 'word-spacing', 'x', 'x1', 'x2', 'y', 'y1', 'y2',
    ];

    /**
     * Attributes holding a unitless number.
     */
    private const array NUMBER_ATTRIBUTES = [
        'amplitude', 'azimuth', 'diffuseConstant', 'elevation', 'exponent', 'fill-opacity', 'flood-opacity',
        'intercept', 'k', 'k1', 'k2', 'k3', 'k4', 'limitingConeAngle', 'numOctaves', 'offset', 'opacity',
        'pathLength', 'pointsAtX', 'pointsAtY', 'pointsAtZ', 'scale', 'seed', 'slope', 'specularConstant',
        'specularExponent', 'stop-opacity', 'stroke-miterlimit', 'stroke-opacity', 'surfaceScale', 'z',
    ];

    private const string LENGTH_REGEX = '/^\s*(' . self::NUMBER_PATTERN . ')(?:px)?\s*$/';

    private const string NUMBER_REGEX = '/^\s*(' . self::NUMBER_PATTERN . ')\s*$/';

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
            foreach (iterator_to_array($domNodeList->attributes, false) as $domAttr) {
                $this->minifyAttribute($domAttr);
            }
        }
    }

    /**
     * @param \DOMAttr $domAttr the attribute to minify
     */
    private function minifyAttribute(\DOMAttr $domAttr): void
    {
        if (null !== $domAttr->namespaceURI) {
            return;
        }

        $regex = match (true) {
            \in_array($domAttr->name, self::LENGTH_ATTRIBUTES, true) => self::LENGTH_REGEX,
            \in_array($domAttr->name, self::NUMBER_ATTRIBUTES, true) => self::NUMBER_REGEX,
            default => null,
        };

        if (null !== $regex && 1 === preg_match($regex, $domAttr->value, $matches)) {
            $domAttr->value = $this->minifyNumber($matches[1]);
        }
    }
}
