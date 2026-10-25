<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Svg;

/**
 * @internal
 */
enum Element: string
{
    case Animate = 'animate';

    case ClipPath = 'clipPath';

    case Embed = 'embed';

    case FeDropShadow = 'feDropShadow';

    case FeImage = 'feImage';

    case ForeignObject = 'foreignObject';

    case Iframe = 'iframe';

    case LinearGradient = 'linearGradient';

    case Link = 'link';

    case Object = 'object';

    case RadialGradient = 'radialGradient';

    case Script = 'script';

    case Set = 'set';

    case TextPath = 'textPath';

    case AltGlyph = 'altGlyph';

    case AltGlyphDef = 'altGlyphDef';

    case AltGlyphItem = 'altGlyphItem';

    case AnimateColor = 'animateColor';

    case AnimateMotion = 'animateMotion';

    case AnimateTransform = 'animateTransform';

    case Circle = 'circle';

    case Defs = 'defs';

    case Desc = 'desc';

    case Discard = 'discard';

    case Ellipse = 'ellipse';

    case FeBlend = 'feBlend';

    case FeColorMatrix = 'feColorMatrix';

    case FeComponentTransfer = 'feComponentTransfer';

    case FeComposite = 'feComposite';

    case FeConvolveMatrix = 'feConvolveMatrix';

    case FeDiffuseLighting = 'feDiffuseLighting';

    case FeDisplacementMap = 'feDisplacementMap';

    case FeDistantLight = 'feDistantLight';

    case FeFlood = 'feFlood';

    case FeFuncA = 'feFuncA';

    case FeFuncB = 'feFuncB';

    case FeFuncG = 'feFuncG';

    case FeFuncR = 'feFuncR';

    case FeGaussianBlur = 'feGaussianBlur';

    case FeMerge = 'feMerge';

    case FeMergeNode = 'feMergeNode';

    case FeMorphology = 'feMorphology';

    case FeOffset = 'feOffset';

    case FePointLight = 'fePointLight';

    case FeSpecularLighting = 'feSpecularLighting';

    case FeSpotLight = 'feSpotLight';

    case FeTile = 'feTile';

    case FeTurbulence = 'feTurbulence';

    case Filter = 'filter';

    case Font = 'font';

    case G = 'g';

    case Glyph = 'glyph';

    case GlyphRef = 'glyphRef';

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

    case Title = 'title';

    case Tref = 'tref';

    case Tspan = 'tspan';

    case Use = 'use';

    case View = 'view';

    case Vkern = 'vkern';

    case A = 'a';

    private const array LINKING_NAMES = [
        self::A->value,
        self::Use->value,
        self::Tref->value,
        self::Image->value,
        self::LinearGradient->value,
        self::RadialGradient->value,
        self::Pattern->value,
    ];

    private const array ANIMATION_NAMES = [
        self::Animate->value,
        self::Set->value,
        self::AnimateColor->value,
        self::AnimateMotion->value,
        self::AnimateTransform->value,
    ];

    private const array IMAGE_LOADING_NAMES = [
        self::Image->value,
        self::FeImage->value,
    ];

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        /** @var list<string>|null $cache */
        static $cache = null;

        return $cache ??= array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function linkingNames(): array
    {
        return self::LINKING_NAMES;
    }

    /**
     * @return list<string>
     */
    public static function animationNames(): array
    {
        return self::ANIMATION_NAMES;
    }

    /**
     * @return list<string>
     */
    public static function imageLoadingNames(): array
    {
        return self::IMAGE_LOADING_NAMES;
    }
}
