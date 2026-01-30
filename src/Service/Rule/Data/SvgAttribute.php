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

enum SvgAttribute: string implements SvgDataInterface
{
    use BaseEnumTrait;

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
    case Divisor = 'divisor';
    case FilterUnits = 'filterUnits';
    case FloodOpacity = 'flood-opacity';
    case AriaActivedescendant = 'aria-activedescendant';
    case AriaAtomic = 'aria-atomic';
    case AriaAutocomplete = 'aria-autocomplete';
    case AriaBusy = 'aria-busy';
    case AriaChecked = 'aria-checked';
    case AriaColcount = 'aria-colcount';
    case AriaColindex = 'aria-colindex';
    case AriaColspan = 'aria-colspan';
    case AriaControls = 'aria-controls';
    case AriaCurrent = 'aria-current';
    case AriaDescribedby = 'aria-describedby';
    case AriaDetails = 'aria-details';
    case AriaDisabled = 'aria-disabled';
    case AriaDropeffect = 'aria-dropeffect';
    case AriaErrormessage = 'aria-errormessage';
    case AriaExpanded = 'aria-expanded';
    case AriaFlowto = 'aria-flowto';
    case AriaGrabbed = 'aria-grabbed';
    case AriaHaspopup = 'aria-haspopup';
    case AriaHidden = 'aria-hidden';
    case AriaInvalid = 'aria-invalid';
    case AriaKeyshortcuts = 'aria-keyshortcuts';
    case AriaLabel = 'aria-label';
    case AriaLabelledby = 'aria-labelledby';
    case AriaLevel = 'aria-level';
    case AriaLive = 'aria-live';
    case AriaModal = 'aria-modal';
    case AriaMultiline = 'aria-multiline';
    case AriaMultiselectable = 'aria-multiselectable';
    case AriaOrientation = 'aria-orientation';
    case AriaOwns = 'aria-owns';
    case AriaPlaceholder = 'aria-placeholder';
    case AriaPosinset = 'aria-posinset';
    case AriaPressed = 'aria-pressed';
    case AriaReadonly = 'aria-readonly';
    case AriaRelevant = 'aria-relevant';
    case AriaRequired = 'aria-required';
    case AriaRoledescription = 'aria-roledescription';
    case AriaRowcount = 'aria-rowcount';
    case AriaRowindex = 'aria-rowindex';
    case AriaRowspan = 'aria-rowspan';
    case AriaSelected = 'aria-selected';
    case AriaSetsize = 'aria-setsize';
    case AriaSort = 'aria-sort';
    case AriaValuemax = 'aria-valuemax';
    case AriaValuemin = 'aria-valuemin';
    case AriaValuenow = 'aria-valuenow';
    case AriaValuetext = 'aria-valuetext';
    case OnAbort = 'onabort';
    case OnAfterPrint = 'onafterprint';
    case OnBeforePrint = 'onbeforeprint';
    case OnBegin = 'onbegin';
    case OnCancel = 'oncancel';
    case OnCanplay = 'oncanplay';
    case OnCanplaythrough = 'oncanplaythrough';
    case OnChange = 'onchange';
    case OnClick = 'onclick';
    case OnClose = 'onclose';
    case OnCopy = 'oncopy';
    case OnCuechange = 'oncuechange';
    case OnCut = 'oncut';
    case OnDblclick = 'ondblclick';
    case OnDrag = 'ondrag';
    case OnDragEnd = 'ondragend';
    case OnDragEnter = 'ondragenter';
    case OnDragExit = 'ondragexit';
    case OnDragLeave = 'ondragleave';
    case OnDragOver = 'ondragover';
    case OnDragStart = 'ondragstart';
    case OnDrop = 'ondrop';
    case OnDurationChange = 'ondurationchange';
    case OnEmptied = 'onemptied';
    case OnEnd = 'onend';
    case OnEnded = 'onended';
    case OnError = 'onerror';
    case OnFocus = 'onfocus';
    case OnFocusin = 'onfocusin';
    case OnFocusout = 'onfocusout';
    case OnHashChange = 'onhashchange';
    case OnInput = 'oninput';
    case OnInvalid = 'oninvalid';
    case OnKeyDown = 'onkeydown';
    case OnKeyPress = 'onkeypress';
    case OnKeyUp = 'onkeyup';
    case OnLoad = 'onload';
    case OnLoadedData = 'onloadeddata';
    case OnLoadedMetadata = 'onloadedmetadata';
    case OnLoadStart = 'onloadstart';
    case OnMessage = 'onmessage';
    case OnMouseDown = 'onmousedown';
    case OnMouseEnter = 'onmouseenter';
    case OnMouseLeave = 'onmouseleave';
    case OnMouseMove = 'onmousemove';
    case OnMouseOut = 'onmouseout';
    case OnMouseOver = 'onmouseover';
    case OnMouseUp = 'onmouseup';
    case OnMouseWheel = 'onmousewheel';
    case OnOffline = 'onoffline';
    case OnOnline = 'ononline';
    case OnPageHide = 'onpagehide';
    case OnPageShow = 'onpageshow';
    case OnPaste = 'onpaste';
    case OnPause = 'onpause';
    case OnPlay = 'onplay';
    case OnPlaying = 'onplaying';
    case OnPopState = 'onpopstate';
    case OnProgress = 'onprogress';
    case OnRateChange = 'onratechange';
    case OnRepeat = 'onrepeat';
    case OnReset = 'onreset';
    case OnResize = 'onresize';
    case OnScroll = 'onscroll';
    case OnSeeked = 'onseeked';
    case OnSeeking = 'onseeking';
    case OnSelect = 'onselect';
    case OnShow = 'onshow';
    case OnStalled = 'onstalled';
    case OnStorage = 'onstorage';
    case OnSubmit = 'onsubmit';
    case OnSuspend = 'onsuspend';
    case OnTimeUpdate = 'ontimeupdate';
    case OnToggle = 'ontoggle';
    case OnUnload = 'onunload';
    case OnVolumeChange = 'onvolumechange';
    case OnWaiting = 'onwaiting';
    case ColorProfile = 'color-profile';
    case Marker = 'marker';
    case AudioLevel = 'audio-level';
    case BufferedRendering = 'buffered-rendering';
    case ViewportFill = 'viewport-fill';
    case ViewportFillOpacity = 'viewport-fill-opacity';
    case AccentHeight = 'accent-height';
    case Alphabetic = 'alphabetic';
    case ArabicForm = 'arabic-form';
    case Ascent = 'ascent';
    case Bandwidth = 'bandwidth';
    case Bbox = 'bbox';
    case CapHeight = 'cap-height';
    case Content = 'content';
    case Datatype = 'datatype';
    case DefaultAction = 'defaultAction';
    case Descent = 'descent';
    case Editable = 'editable';
    case EvEvent = 'ev:event';
    case Event = 'event';
    case FocusHighlight = 'focusHighlight';
    case Focusable = 'focusable';
    case G1 = 'g1';
    case G2 = 'g2';
    case GlyphName = 'glyph-name';
    case Handler = 'handler';
    case Hanging = 'hanging';
    case HorizAdvX = 'horiz-adv-x';
    case HorizOriginX = 'horiz-origin-x';
    case Ideographic = 'ideographic';
    case InitialVisibility = 'initialVisibility';
    case K = 'k';
    case Mathematical = 'mathematical';
    case MediaCharacterEncoding = 'mediaCharacterEncoding';
    case MediaContentEncodings = 'mediaContentEncodings';
    case MediaSize = 'mediaSize';
    case MediaTime = 'mediaTime';
    case NavDown = 'nav-down';
    case NavDownLeft = 'nav-down-left';
    case NavDownRight = 'nav-down-right';
    case NavLeft = 'nav-left';
    case NavNext = 'nav-next';
    case NavPrev = 'nav-prev';
    case NavRight = 'nav-right';
    case NavUp = 'nav-up';
    case NavUpLeft = 'nav-up-left';
    case NavUpRight = 'nav-up-right';
    case Observer = 'observer';
    case Overlay = 'overlay';
    case OverlinePosition = 'overline-position';
    case OverlineThickness = 'overline-thickness';
    case Panose1 = 'panose-1';
    case Path = 'path';
    case Phase = 'phase';
    case PlaybackOrder = 'playbackOrder';
    case Propagate = 'propagate';
    case Property = 'property';
    case RequiredFonts = 'requiredFonts';
    case RequiredFormats = 'requiredFormats';
    case Resource = 'resource';
    case SnapshotTime = 'snapshotTime';
    case StemH = 'stemh';
    case StemV = 'stemv';
    case StrikethroughPosition = 'strikethrough-position';
    case StrikethroughThickness = 'strikethrough-thickness';
    case SyncBehavior = 'syncBehavior';
    case SyncBehaviorDefault = 'syncBehaviorDefault';
    case SyncMaster = 'syncMaster';
    case SyncTolerance = 'syncTolerance';
    case SyncToleranceDefault = 'syncToleranceDefault';
    case TimelineBegin = 'timelineBegin';
    case TransformBehavior = 'transformBehavior';
    case Typeof = 'typeof';
    case U1 = 'u1';
    case U2 = 'u2';
    case UnderlinePosition = 'underline-position';
    case UnderlineThickness = 'underline-thickness';
    case Unicode = 'unicode';
    case UnicodeRange = 'unicode-range';
    case UnitsPerEm = 'units-per-em';
    case Widths = 'widths';
    case XHeight = 'x-height';

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
            self::Cursor,
            self::Background,
            self::Border,
            self::ColorProfile,
            self::Marker,
            self::Overlay,
        ];
    }
}
