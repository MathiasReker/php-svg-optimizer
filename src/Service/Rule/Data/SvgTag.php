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

enum SvgTag: string implements SvgDataInterface
{
    case A = 'a';
    case Font = 'font';
    case Image = 'image';
    case Style = 'style';
    case Svg = 'svg';
    case ALTGlyph = 'altglyph';
    case ALTGlyphDef = 'altglyphdef';
    case ALTGlyphItem = 'altglyphitem';
    case AnimateColor = 'animatecolor';
    case AnimateMotion = 'animatemotion';
    case AnimateTransform = 'animatetransform';
    case Circle = 'circle';
    case Clippath = 'clippath';
    case Defs = 'defs';
    case Desc = 'desc';
    case Ellipse = 'ellipse';
    case Filter = 'filter';
    case G = 'g';
    case Glyph = 'glyph';
    case Glyphref = 'glyphref';
    case Hkern = 'hkern';
    case Line = 'line';
    case Lineargradient = 'lineargradient';
    case Marker = 'marker';
    case Mask = 'mask';
    case Metadata = 'metadata';
    case Mpath = 'mpath';
    case Path = 'path';
    case Pattern = 'pattern';
    case Polygon = 'polygon';
    case Polyline = 'polyline';
    case Radialgradient = 'radialgradient';
    case Rect = 'rect';
    case Stop = 'stop';
    case Switch = 'switch';
    case Symbol = 'symbol';
    case Text = 'text';
    case Textpath = 'textpath';
    case Title = 'title';
    case Tref = 'tref';
    case Tspan = 'tspan';
    case Use = 'use';
    case View = 'view';
    case Vkern = 'vkern';
    case FEBlend = 'feBlend';
    case FEColorMatrix = 'feColorMatrix';
    case FEComponentTransfer = 'feComponentTransfer';
    case FEComposite = 'feComposite';
    case FEConvolveMatrix = 'feConvolveMatrix';
    case FEDiffuseLighting = 'feDiffuseLighting';
    case FEDisplacementMap = 'feDisplacementMap';
    case FEDistantLight = 'feDistantLight';
    case FEFlood = 'feFlood';
    case FEFuncA = 'feFuncA';
    case FEFuncB = 'feFuncB';
    case FEFuncG = 'feFuncG';
    case FEFuncR = 'feFuncR';
    case FEGaussianBlur = 'feGaussianBlur';
    case FEMerge = 'feMerge';
    case FEMergeNode = 'feMergeNode';
    case FEMorphology = 'feMorphology';
    case FEOffset = 'feOffset';
    case FEPointLight = 'fePointLight';
    case FESpecularLighting = 'feSpecularLighting';
    case FESpotLight = 'feSpotLight';
    case FETile = 'feTile';
    case FETurbulence = 'feTurbulence';

    case TextNode = '#text';

    /**
     * Returns all tag values as an array of strings.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $tag) => $tag->value, self::cases());
    }
}
