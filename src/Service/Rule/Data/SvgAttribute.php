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

enum SvgAttribute: string implements SvgDataInterface
{
    use BaseEnum;

    case Accumulate = 'accumulate';
    case Additive = 'additive';
    case Amplitude = 'amplitude';
    case AttributeName = 'attributeName';
    case AttributeType = 'attributeType';
    case Autofocus = 'autofocus';
    case Azimuth = 'azimuth';
    case BackgroundColor = 'background-color';
    case BaseFrequency = 'baseFrequency';
    case BaselineShift = 'baseline-shift';
    case BaseProfile = 'baseProfile';
    case Begin = 'begin';
    case Bias = 'bias';
    case BorderColor = 'border-color';
    case By = 'by';
    case CalcMode = 'calcMode';
    case Clip = 'clip';
    case ClipPathUnits = 'clipPathUnits';
    case ContentScriptType = 'contentScriptType';
    case ContentStyleType = 'contentStyleType';
    case CurrentView = 'currentView';
    case Cx = 'cx';
    case Cy = 'cy';
    case D = 'd';
    case Data = 'data-*';
    case Decoding = 'decoding';
    case DiffuseConstant = 'diffuseConstant';
    case Dur = 'dur';
    case Dx = 'dx';
    case Dy = 'dy';
    case EdgeMode = 'edgeMode';
    case Elevation = 'elevation';
    case EnableBackground = 'enable-background';
    case End = 'end';
    case Exponent = 'exponent';
    case ExternalResourcesRequired = 'externalResourcesRequired';
    case FetchPriority = 'fetchpriority';
    case FloodColor = 'flood-color';
    case FontFamily = 'font-family';
    case FontSize = 'font-size';
    case FontSizeAdjust = 'font-size-adjust';
    case FontStretch = 'font-stretch';
    case FontStyle = 'font-style';
    case FontVariant = 'font-variant';
    case FontWeight = 'font-weight';
    case Fr = 'fr';
    case From = 'from';
    case Fx = 'fx';
    case Fy = 'fy';
    case GlyphOrientationHorizontal = 'glyph-orientation-horizontal';
    case GlyphOrientationVertical = 'glyph-orientation-vertical';
    case GradientTransform = 'gradientTransform';
    case GradientUnits = 'gradientUnits';
    case In = 'in';
    case In2 = 'in2';
    case Intercept = 'intercept';
    case K1 = 'k1';
    case K2 = 'k2';
    case K3 = 'k3';
    case K4 = 'k4';
    case KernelMatrix = 'kernelMatrix';
    case KernelUnitLength = 'kernelUnitLength';
    case KeyPoints = 'keyPoints';
    case KeySplines = 'keySplines';
    case KeyTimes = 'keyTimes';
    case LengthAdjust = 'lengthAdjust';
    case LightingColor = 'lighting-color';
    case LimitingConeAngle = 'limitingConeAngle';
    case MarkerHeight = 'markerHeight';
    case MarkerUnits = 'markerUnits';
    case MarkerWidth = 'markerWidth';
    case MaskContentUnits = 'maskContentUnits';
    case MaskUnits = 'maskUnits';
    case Mode = 'mode';
    case NumOctaves = 'numOctaves';
    case Offset = 'offset';
    case Operator = 'operator';
    case Order = 'order';
    case Orient = 'orient';
    case Origin = 'origin';
    case PaintOrder = 'paint-order';
    case PathLength = 'pathLength';
    case PatternContentUnits = 'patternContentUnits';
    case PatternTransform = 'patternTransform';
    case PatternUnits = 'patternUnits';
    case Ping = 'ping';
    case PointerEvents = 'pointer-events';
    case Points = 'points';
    case PointsAtX = 'pointsAtX';
    case PointsAtY = 'pointsAtY';
    case PointsAtZ = 'pointsAtZ';
    case PreserveAlpha = 'preserveAlpha';
    case PreserveAspectRatio = 'preserveAspectRatio';
    case PrimitiveUnits = 'primitiveUnits';
    case R = 'r';
    case Radius = 'radius';
    case ReferrerPolicy = 'referrerPolicy';
    case RefX = 'refX';
    case RefY = 'refY';
    case RepeatCount = 'repeatCount';
    case RepeatDur = 'repeatDur';
    case RequiredExtensions = 'requiredExtensions';
    case RequiredFeatures = 'requiredFeatures';
    case Restart = 'restart';
    case Result = 'result';
    case Rotate = 'rotate';
    case Rx = 'rx';
    case Ry = 'ry';
    case Scale = 'scale';
    case Seed = 'seed';
    case Side = 'side';
    case Slope = 'slope';
    case SolidColor = 'solid-color';
    case Spacing = 'spacing';
    case SpecularConstant = 'specularConstant';
    case SpecularExponent = 'specularExponent';
    case SpreadMethod = 'spreadMethod';
    case StartOffset = 'startOffset';
    case StdDeviation = 'stdDeviation';
    case StitchTiles = 'stitchTiles';
    case StopColor = 'stop-color';
    case StopOpacity = 'stop-opacity';
    case SurfaceScale = 'surfaceScale';
    case SuspendRedraw = 'suspendRedraw';
    case SystemLanguage = 'systemLanguage';
    case TableValues = 'tableValues';
    case Target = 'target';
    case TargetX = 'targetX';
    case TargetY = 'targetY';
    case TextDecoration = 'text-decoration';
    case TextLength = 'textLength';
    case TextOverflow = 'text-overflow';
    case To = 'to';
    case Transform = 'transform';
    case TransformOrigin = 'transform-origin';
    case UnsuspendRedraw = 'unsuspendRedraw';
    case UnsuspendRedrawAll = 'unsuspendRedrawAll';
    case UseCurrentView = 'useCurrentView';
    case Values = 'values';
    case ViewBox = 'viewBox';
    case Viewport = 'viewport';
    case ViewTarget = 'viewTarget';
    case WhiteSpace = 'white-space';
    case X = 'x';
    case X1 = 'x1';
    case X2 = 'x2';
    case XChannelSelector = 'xChannelSelector';
    case Xlink = 'xlink';
    case XlinkActuate = 'xlink:actuate';
    case XlinkArcrole = 'xlink:arcrole';
    case XlinkRole = 'xlink:role';
    case XlinkShow = 'xlink:show';
    case XlinkType = 'xlink:type';
    case XmlBase = 'xml:base';
    case XmlLang = 'xml:lang';
    case Y = 'y';
    case Y1 = 'y1';
    case Y2 = 'y2';
    case YChannelSelector = 'yChannelSelector';
    case Z = 'z';
    case ZoomAndPan = 'zoomAndPan';
    case Accept = 'accept';
    case Action = 'action';
    case Align = 'align';
    case AlignmentBaseline = 'alignment-baseline';
    case Alt = 'alt';
    case Autocomplete = 'autocomplete';
    case Background = 'background';
    case Bgcolor = 'bgcolor';
    case Border = 'border';
    case Cellpadding = 'cellpadding';
    case Cellspacing = 'cellspacing';
    case Checked = 'checked';
    case Cite = 'cite';
    case Class_ = 'class';
    case Clear = 'clear';
    case ClipPath = 'clip-path';
    case ClipRule = 'clip-rule';
    case Color = 'color';
    case ColorInterpolation = 'color-interpolation';
    case ColorInterpolationFilters = 'color-interpolation-filters';
    case ColorRendering = 'color-rendering';
    case Cols = 'cols';
    case Colspan = 'colspan';
    case Coords = 'coords';
    case Crossorigin = 'crossorigin';
    case Cursor = 'cursor';
    case Datetime = 'datetime';
    case Default = 'default';
    case Dir = 'dir';
    case Direction = 'direction';
    case Disabled = 'disabled';
    case Display = 'display';
    case DominantBaseline = 'dominant-baseline';
    case Download = 'download';
    case Encoding = 'encoding';
    case Enctype = 'enctype';
    case Face = 'face';
    case Fill = 'fill';
    case FillOpacity = 'fill-opacity';
    case FillRule = 'fill-rule';
    case Filter = 'filter';
    case For = 'for';
    case Headers = 'headers';
    case Height = 'height';
    case Hidden = 'hidden';
    case High = 'high';
    case Href = 'href';
    case Hreflang = 'hreflang';
    case Id = 'id';
    case ImageRendering = 'image-rendering';
    case Integrity = 'integrity';
    case Ismap = 'ismap';
    case Kerning = 'kerning';
    case Label = 'label';
    case Lang = 'lang';
    case LetterSpacing = 'letter-spacing';
    case List = 'list';
    case Loop = 'loop';
    case Low = 'low';
    case MarkerEnd = 'marker-end';
    case MarkerMid = 'marker-mid';
    case MarkerStart = 'marker-start';
    case Mask = 'mask';
    case Max = 'max';
    case Maxlength = 'maxlength';
    case Media = 'media';
    case Method = 'method';
    case Min = 'min';
    case Multiple = 'multiple';
    case Name = 'name';
    case Noshade = 'noshade';
    case Novalidate = 'novalidate';
    case Nowrap = 'nowrap';
    case Opacity = 'opacity';
    case Open = 'open';
    case Optimum = 'optimum';
    case Overflow = 'overflow';
    case Pattern = 'pattern';
    case Placeholder = 'placeholder';
    case Poster = 'poster';
    case Preload = 'preload';
    case Pubdate = 'pubdate';
    case Radiogroup = 'radiogroup';
    case Readonly = 'readonly';
    case Rel = 'rel';
    case Required = 'required';
    case Rev = 'rev';
    case Reversed = 'reversed';
    case Role = 'role';
    case Rows = 'rows';
    case Rowspan = 'rowspan';
    case Scope = 'scope';
    case Selected = 'selected';
    case Shape = 'shape';
    case ShapeRendering = 'shape-rendering';
    case Size = 'size';
    case Sizes = 'sizes';
    case Span = 'span';
    case Spellcheck = 'spellcheck';
    case Src = 'src';
    case Srclang = 'srclang';
    case Srcset = 'srcset';
    case Start = 'start';
    case Step = 'step';
    case Stroke = 'stroke';
    case StrokeDasharray = 'stroke-dasharray';
    case StrokeDashoffset = 'stroke-dashoffset';
    case StrokeLinecap = 'stroke-linecap';
    case StrokeLinejoin = 'stroke-linejoin';
    case StrokeMiterlimit = 'stroke-miterlimit';
    case StrokeOpacity = 'stroke-opacity';
    case StrokeWidth = 'stroke-width';
    case Style = 'style';
    case Summary = 'summary';
    case Tabindex = 'tabindex';
    case TextAnchor = 'text-anchor';
    case TextRendering = 'text-rendering';
    case Title = 'title';
    case Type = 'type';
    case UnicodeBidi = 'unicode-bidi';
    case Usemap = 'usemap';
    case Valign = 'valign';
    case Value = 'value';
    case VectorEffect = 'vector-effect';
    case Version = 'version';
    case Visibility = 'visibility';
    case Width = 'width';
    case WordSpacing = 'word-spacing';
    case WritingMode = 'writing-mode';
    case XlinkHref = 'xlink:href';
    case XlinkTitle = 'xlink:title';
    case XmlId = 'xml:id';
    case Xmlns = 'xmlns';
    case XmlnsXlink = 'xmlns:xlink';
    case XmlSpace = 'xml:space';
    case About = 'about';

    /**
     * Returns all enum values as a string array.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return self::valuesFromCases(self::cases());
    }

    /**
     * @return list<string>
     */
    public static function colors(): array
    {
        return self::valuesFromCases(self::colorCases());
    }

    /**
     * @return list<SvgAttribute>
     */
    public static function colorCases(): array
    {
        return [
            self::Fill,
            self::Stroke,
            self::Color,
            self::StopColor,
            self::FloodColor,
            self::LightingColor,
            self::SolidColor,
            self::BackgroundColor,
            self::BorderColor,
        ];
    }

    /**
     * Returns exact dangerous SVG attribute values.
     *
     * @return list<string>
     */
    public static function dangerousExact(): array
    {
        return self::valuesFromCases(self::dangerousExactCases());
    }

    /**
     * @return list<SvgAttribute>
     */
    public static function dangerousExactCases(): array
    {
        return [
            self::XlinkHref,
            self::Href,
        ];
    }

    /**
     * Returns dangerous SVG attribute values.
     *
     * @return list<string>
     */
    public static function dangerous(): array
    {
        return self::valuesFromCases(self::dangerousCases());
    }

    /**
     * Returns dangerous SVG attribute enum cases.
     *
     * @return list<self>
     */
    public static function dangerousCases(): array
    {
        return [
            self::Fill,
            self::Stroke,
            self::Filter,
            self::ClipPath,
            self::Mask,
            self::MarkerStart,
            self::MarkerMid,
            self::MarkerEnd,
            self::Begin,
            self::Pattern,
            self::End,
            self::From,
            self::To,
            self::Values,
            self::Style,
        ];
    }
}
