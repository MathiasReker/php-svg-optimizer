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

/**
 * Enum representing all allowed HTML, SVG, MathML, and XML attributes.
 *
 * Using a backed enum for type safety, iteration, and IDE autocomplete.
 */
enum SvgAttribute: string implements SvgDataInterface
{
    use BaseEnum;

    case About = 'about';
    case Accept = 'accept';
    case Action = 'action';
    case Align = 'align';
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
    case Color = 'color';
    case Cols = 'cols';
    case Colspan = 'colspan';
    case Coords = 'coords';
    case Crossorigin = 'crossorigin';
    case Datetime = 'datetime';
    case Default = 'default';
    case Dir = 'dir';
    case Disabled = 'disabled';
    case Download = 'download';
    case Enctype = 'enctype';
    case Encoding = 'encoding';
    case Face = 'face';
    case For = 'for';
    case Headers = 'headers';
    case Height = 'height';
    case Hidden = 'hidden';
    case High = 'high';
    case Href = 'href';
    case Hreflang = 'hreflang';
    case Id = 'id';
    case Integrity = 'integrity';
    case Ismap = 'ismap';
    case Label = 'label';
    case Lang = 'lang';
    case List = 'list';
    case Loop = 'loop';
    case Low = 'low';
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
    case Open = 'open';
    case Optimum = 'optimum';
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
    case Spellcheck = 'spellcheck';
    case Scope = 'scope';
    case Selected = 'selected';
    case Shape = 'shape';
    case Size = 'size';
    case Sizes = 'sizes';
    case Span = 'span';
    case Srclang = 'srclang';
    case Start = 'start';
    case Src = 'src';
    case Srcset = 'srcset';
    case Step = 'step';
    case Style = 'style';
    case Summary = 'summary';
    case Tabindex = 'tabindex';
    case Title = 'title';
    case Type = 'type';
    case Usemap = 'usemap';
    case Valign = 'valign';
    case Value = 'value';
    case Version = 'version';
    case Width = 'width';
    case Xmlns = 'xmlns';
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
    case TextRendering = 'text-rendering';
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
    case XlinkHref = 'xlink:href';
    case XmlId = 'xml:id';
    case XlinkTitle = 'xlink:title';
    case XmlSpace = 'xml:space';
    case XmlnsXlink = 'xmlns:xlink';

    case Transform = 'transform';
    case From = 'from';
    case Begin = 'begin';
    case End = 'end';
    case To = 'to';
    case Values = 'values';
    case FloodColor = 'flood-color';
    case StopColor = 'stop-color';

    case LightingColor = 'lighting-color';
    case SolidColor = 'solid-color';
    case BackgroundColor = 'background-color';
    case BorderColor = 'border-color';
    case X = 'x';
    case X1 = 'x1';
    case X2 = 'x2';
    case Y = 'y';
    case Y1 = 'y1';
    case Y2 = 'y2';
    case Cx = 'cx';
    case Cy = 'cy';
    case Rx = 'rx';
    case Ry = 'ry';
    case R = 'r';
    case Points = 'points';
    case D = 'd';
    case ViewBox = 'viewBox';
    case EnableBackground = 'enable-background';
    case BaseProfile = 'baseProfile';

    case Xlink = 'xlink';
    case ContentScriptType = 'contentScriptType';
    case ContentStyleType = 'contentStyleType';
    case CurrentView = 'currentView';
    case ExternalResourcesRequired = 'externalResourcesRequired';
    case RequiredFeatures = 'requiredFeatures';
    case UseCurrentView = 'useCurrentView';
    case ViewTarget = 'viewTarget';
    case Viewport = 'viewport';
    case XlinkArcrole = 'xlink:arcrole';
    case XlinkShow = 'xlink:show';
    case XlinkType = 'xlink:type';
    case XmlBase = 'xml:base';
    case ZoomAndPan = 'zoomAndPan';
    case SuspendRedraw = 'suspendRedraw';
    case UnsuspendRedraw = 'unsuspendRedraw';
    case UnsuspendRedrawAll = 'unsuspendRedrawAll';
    case XmlLang = 'xml:lang';
    case Accumulate = 'accumulate';
    case Additive = 'additive';
    case Amplitude = 'amplitude';
    case AttributeName = 'attributeName';
    case AttributeType = 'attributeType';
    case Autofocus = 'autofocus';
    case Azimuth = 'azimuth';
    case BaseFrequency = 'baseFrequency';
    case BaselineShift = 'baseline-shift';
    case Bias = 'bias';
    case By = 'by';
    case CalcMode = 'calcMode';
    case Clip = 'clip';
    case ClipPathUnits = 'clipPathUnits';
    case Data = 'data-*';
    case Decoding = 'decoding';
    case DiffuseConstant = 'diffuseConstant';
    case Dur = 'dur';
    case Dx = 'dx';
    case Dy = 'dy';
    case EdgeMode = 'edgeMode';
    case Elevation = 'elevation';
    case Exponent = 'exponent';
    case FetchPriority = 'fetchpriority';
    case FontFamily = 'font-family';
    case FontSize = 'font-size';
    case FontSizeAdjust = 'font-size-adjust';
    case FontStretch = 'font-stretch';
    case FontStyle = 'font-style';
    case FontVariant = 'font-variant';
    case FontWeight = 'font-weight';
    case Fr = 'fr';
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
    case PointsAtX = 'pointsAtX';
    case PointsAtY = 'pointsAtY';
    case PointsAtZ = 'pointsAtZ';
    case PreserveAlpha = 'preserveAlpha';
    case PreserveAspectRatio = 'preserveAspectRatio';
    case PrimitiveUnits = 'primitiveUnits';
    case Radius = 'radius';
    case ReferrerPolicy = 'referrerPolicy';
    case RefX = 'refX';
    case RefY = 'refY';
    case RepeatCount = 'repeatCount';
    case RepeatDur = 'repeatDur';
    case RequiredExtensions = 'requiredExtensions';
    case Restart = 'restart';
    case Result = 'result';
    case Rotate = 'rotate';
    case Scale = 'scale';
    case Seed = 'seed';
    case Side = 'side';
    case Slope = 'slope';
    case Spacing = 'spacing';
    case SpecularConstant = 'specularConstant';
    case SpecularExponent = 'specularExponent';
    case SpreadMethod = 'spreadMethod';
    case StartOffset = 'startOffset';
    case StdDeviation = 'stdDeviation';
    case StitchTiles = 'stitchTiles';
    case StopOpacity = 'stop-opacity';
    case SurfaceScale = 'surfaceScale';
    case SystemLanguage = 'systemLanguage';
    case TableValues = 'tableValues';
    case Target = 'target';
    case TargetX = 'targetX';
    case TargetY = 'targetY';
    case TextDecoration = 'text-decoration';
    case TextOverflow = 'text-overflow';
    case TextLength = 'textLength';
    case TransformOrigin = 'transform-origin';
    case XChannelSelector = 'xChannelSelector';
    case XlinkActuate = 'xlink:actuate';
    case XlinkRole = 'xlink:role';
    case YChannelSelector = 'yChannelSelector';
    case Z = 'z';
    case WhiteSpace = 'white-space';
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
