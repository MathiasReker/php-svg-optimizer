<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Data;

use MathiasReker\PhpSvgOptimizer\Contract\Service\Rule\SvgDataInterface;

enum SvgInlineStyleProperty: string implements SvgDataInterface
{
    case Fill = 'fill';
    case FillOpacity = 'fill-opacity';
    case FillRule = 'fill-rule';
    case Stroke = 'stroke';
    case StrokeOpacity = 'stroke-opacity';
    case StrokeWidth = 'stroke-width';
    case StrokeLinecap = 'stroke-linecap';
    case StrokeLinejoin = 'stroke-linejoin';
    case StrokeMiterlimit = 'stroke-miterlimit';
    case StrokeDasharray = 'stroke-dasharray';
    case StrokeDashoffset = 'stroke-dashoffset';
    case Opacity = 'opacity';
    case Visibility = 'visibility';
    case MarkerStart = 'marker-start';
    case MarkerMid = 'marker-mid';
    case MarkerEnd = 'marker-end';
    case Mask = 'mask';
    case ClipPath = 'clip-path';
    case ClipRule = 'clip-rule';
    case Filter = 'filter';
    case ShapeRendering = 'shape-rendering';
    case VectorEffect = 'vector-effect';
    case ColorInterpolation = 'color-interpolation';
    case ColorInterpolationFilters = 'color-interpolation-filters';
    case ColorRendering = 'color-rendering';
    case ImageRendering = 'image-rendering';
    case PointerEvents = 'pointer-events';
    case TextRendering = 'text-rendering';
    case StopColor = 'stop-color';
    case StopOpacity = 'stop-opacity';
    case TextAnchor = 'text-anchor';
    case AlignmentBaseline = 'alignment-baseline';
    case DominantBaseline = 'dominant-baseline';
    case LetterSpacing = 'letter-spacing';
    case WordSpacing = 'word-spacing';
    case Kerning = 'kerning';
    case Cursor = 'cursor';
    case Direction = 'direction';
    case Display = 'display';
    case Overflow = 'overflow';
    case UnicodeBidi = 'unicode-bidi';
    case WritingMode = 'writing-mode';

    /**
     * Returns all property values as a string array.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $property) => $property->value, self::cases());
    }
}
