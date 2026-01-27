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
    case Class = 'class';
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

    /**
     * Returns all enum values as a string array.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $attr) => $attr->value, self::cases());
    }
}
