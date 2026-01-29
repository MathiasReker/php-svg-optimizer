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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\Trait\BaseEnumTrait;

enum SvgInlineStyleProperty: string implements SvgDataInterface
{
    use BaseEnumTrait;

    case AlignmentBaseline = 'alignment-baseline';
    case ClipPath = 'clip-path';
    case ClipRule = 'clip-rule';
    case ColorInterpolation = 'color-interpolation';
    case ColorInterpolationFilters = 'color-interpolation-filters';
    case ColorRendering = 'color-rendering';
    case Cursor = 'cursor';
    case Direction = 'direction';
    case Display = 'display';
    case DominantBaseline = 'dominant-baseline';
    case FillOpacity = 'fill-opacity';
    case FillRule = 'fill-rule';
    case Filter = 'filter';
    case ImageRendering = 'image-rendering';
    case Kerning = 'kerning';
    case LetterSpacing = 'letter-spacing';
    case MarkerEnd = 'marker-end';
    case MarkerMid = 'marker-mid';
    case MarkerStart = 'marker-start';
    case Mask = 'mask';
    case Opacity = 'opacity';
    case Overflow = 'overflow';
    case PointerEvents = 'pointer-events';
    case ShapeRendering = 'shape-rendering';
    case StopColor = 'stop-color';
    case StopOpacity = 'stop-opacity';
    case Stroke = 'stroke';
    case StrokeDasharray = 'stroke-dasharray';
    case StrokeDashoffset = 'stroke-dashoffset';
    case StrokeLinecap = 'stroke-linecap';
    case StrokeLinejoin = 'stroke-linejoin';
    case StrokeMiterlimit = 'stroke-miterlimit';
    case StrokeOpacity = 'stroke-opacity';
    case StrokeWidth = 'stroke-width';
    case TextAnchor = 'text-anchor';
    case TextRendering = 'text-rendering';
    case UnicodeBidi = 'unicode-bidi';
    case VectorEffect = 'vector-effect';
    case Visibility = 'visibility';
    case WordSpacing = 'word-spacing';
    case WritingMode = 'writing-mode';
    case Fill = 'fill';

    /**
     * Returns all property values as a string array.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return self::valuesFromCases(self::cases());
    }
}
