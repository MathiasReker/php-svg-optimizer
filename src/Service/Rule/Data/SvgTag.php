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

enum SvgTag: string implements SvgDataInterface
{
    use BaseEnumTrait;

    case Animate = 'animate';
    case ClipPath = 'clipPath';
    case Embed = 'embed';
    case FEDropShadow = 'feDropShadow';
    case FEImage = 'feImage';
    case ForeignObject = 'foreignObject';
    case Iframe = 'iframe';
    case LinearGradient = 'linearGradient';
    case Link = 'link';
    case Object = 'object';
    case RadialGradient = 'radialGradient';
    case Script = 'script';
    case Set = 'set';
    case TextNode = '#text';
    case TextPath = 'textPath';
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
    case Filter = 'filter';
    case Font = 'font';
    case G = 'g';
    case Glyph = 'glyph';
    case Glyphref = 'glyphref';
    case Hkern = 'hkern';
    case Image = 'image';
    case Line = 'line';
    case Marker = 'marker';
    case Mask = 'mask';
    case Metadata = 'metadata';
    case Mpath = 'mpath';
    case Path = 'path';
    case Pattern = 'pattern';
    case Polygon = 'polygon';
    case Polyline = 'polyline';
    case Rect = 'rect';
    case Stop = 'stop';
    case Style = 'style';
    case Svg = 'svg';
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
    case A = 'a';

    /**
     * Returns all tag values as an array of strings.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $tag) => $tag->value, self::cases());
    }

    /**
     * @return list<string>
     */
    public static function dangerous(): array
    {
        return self::valuesFromCases(self::dangerousCases());
    }

    /**
     * @return list<SvgTag>
     */
    public static function dangerousCases(): array
    {
        return [self::Script, self::ForeignObject, self::Iframe, self::Object, self::Embed, self::Link];
    }

    /**
     * @return list<string>
     */
    public static function conditionalDangerous(): array
    {
        return self::valuesFromCases(self::conditionalDangerousCases());
    }

    /**
     * @return list<SvgTag>
     */
    public static function conditionalDangerousCases(): array
    {
        return [self::A, self::Use, self::Tref, self::Image, self::LinearGradient, self::RadialGradient, self::Pattern];
    }
}
