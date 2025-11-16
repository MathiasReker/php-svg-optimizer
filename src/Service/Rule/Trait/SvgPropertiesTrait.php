<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Trait;

/**
 * @no-named-arguments
 */
trait SvgPropertiesTrait
{
    private const array SVG_PROPERTIES = [
        'fill',
        'fill-opacity',
        'fill-rule',
        'stroke',
        'stroke-opacity',
        'stroke-width',
        'stroke-linecap',
        'stroke-linejoin',
        'stroke-miterlimit',
        'stroke-dasharray',
        'stroke-dashoffset',
        'opacity',
        'visibility',
        'marker-start',
        'marker-mid',
        'marker-end',
        'mask',
        'clip-path',
        'clip-rule',
        'filter',
        'shape-rendering',
        'vector-effect',
        'color-interpolation',
        'color-interpolation-filters',
        'color-rendering',
        'image-rendering',
        'pointer-events',
        'text-rendering',
        'stop-color',
        'stop-opacity',
        'text-anchor',
        'alignment-baseline',
        'dominant-baseline',
        'letter-spacing',
        'word-spacing',
        'kerning',
        'cursor',
        'direction',
        'display',
        'overflow',
        'visibility',
        'unicode-bidi',
        'writing-mode',
    ];
}
