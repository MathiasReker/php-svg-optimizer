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
        // Painting & coloring
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

        // Opacity & visibility
        'opacity',
        'visibility',

        // Markers
        'marker-start',
        'marker-mid',
        'marker-end',

        // Filters / masking / clipping
        'mask',
        'clip-path',
        'clip-rule',
        'filter',

        // Rendering hints & effects
        'shape-rendering',
        'vector-effect',
        'color-interpolation',
        'color-interpolation-filters',
        'color-rendering',
        'image-rendering',
        'pointer-events',
        'text-rendering',

        // Gradients / stops
        'stop-color',
        'stop-opacity',

        // Text properties supported as SVG presentation attributes
        'text-anchor',
        'alignment-baseline',
        'dominant-baseline',
        'letter-spacing',
        'word-spacing',
        'kerning',

        // Additional presentation / alignment properties
        'cursor',
        'direction',
        'display',
        'overflow',
        'visibility',
        'unicode-bidi',
        'writing-mode',
    ];
}
