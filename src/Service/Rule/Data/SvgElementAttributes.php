<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Service\Rule\Data;

/**
 * Per-element attribute data: for each known element, the attributes the specifications allow on it.
 *
 * Sources: SVG 2, SVG 1.1 (removed elements and legacy attributes), SVG Tiny 1.2, Filter Effects 1,
 * CSS Masking 1 and SVG Animations. The data is deliberately permissive: attributes that browsers
 * honour in real-world files are kept even when a newer specification dropped them.
 *
 * @internal
 *
 * @no-named-arguments
 */
final class SvgElementAttributes
{
    /**
     * SVG 2 core attributes.
     */
    private const array CORE = ['id', 'tabindex', 'autofocus', 'lang', 'xml:space', 'class', 'style'];

    /**
     * SVG 1.1 core attributes and attributes removed in SVG 2 that are still common in real files.
     */
    private const array LEGACY = ['xml:base', 'xml:lang', 'xml:id', 'externalResourcesRequired'];

    /**
     * SVG Tiny 1.2 metadata (RDFa) attributes, part of the Tiny 1.2 core attribute set.
     */
    private const array RDFA = ['about', 'content', 'datatype', 'property', 'rel', 'resource', 'rev', 'typeof'];

    /**
     * Conditional processing attributes (SVG 2, plus SVG 1.1 and SVG Tiny 1.2 additions).
     */
    private const array CONDITIONAL_PROCESSING = [
        'requiredExtensions',
        'systemLanguage',
        'requiredFeatures',
        'requiredFonts',
        'requiredFormats',
    ];

    /**
     * SVG 2 presentation attributes that may be specified on any SVG element.
     * The geometry properties (x, y, cx, d, ...) are element-specific and listed per element,
     * and transform is a separate group because it does not apply to gradients and patterns.
     */
    private const array PRESENTATION = [
        'alignment-baseline',
        'baseline-shift',
        'clip-path',
        'clip-rule',
        'color',
        'color-interpolation',
        'color-interpolation-filters',
        'cursor',
        'direction',
        'display',
        'dominant-baseline',
        'fill',
        'fill-opacity',
        'fill-rule',
        'filter',
        'flood-color',
        'flood-opacity',
        'font-family',
        'font-size',
        'font-size-adjust',
        'font-stretch',
        'font-style',
        'font-variant',
        'font-weight',
        'glyph-orientation-vertical',
        'image-rendering',
        'letter-spacing',
        'lighting-color',
        'marker-end',
        'marker-mid',
        'marker-start',
        'mask',
        'mask-type',
        'opacity',
        'overflow',
        'paint-order',
        'pointer-events',
        'shape-rendering',
        'stop-color',
        'stop-opacity',
        'stroke',
        'stroke-dasharray',
        'stroke-dashoffset',
        'stroke-linecap',
        'stroke-linejoin',
        'stroke-miterlimit',
        'stroke-opacity',
        'stroke-width',
        'text-anchor',
        'text-decoration',
        'text-overflow',
        'text-rendering',
        'transform-origin',
        'unicode-bidi',
        'vector-effect',
        'visibility',
        'white-space',
        'word-spacing',
        'writing-mode',
    ];

    /**
     * Presentation attributes from SVG 1.1 and SVG Tiny 1.2 that SVG 2 dropped.
     */
    private const array LEGACY_PRESENTATION = [
        'clip',
        'color-profile',
        'enable-background',
        'font',
        'glyph-orientation-horizontal',
        'kerning',
        'marker',
        'solid-color',
        'buffered-rendering',
        'viewport-fill',
        'viewport-fill-opacity',
        'audio-level',
    ];

    /**
     * The transform presentation attribute.
     */
    private const array TRANSFORM = ['transform'];

    /**
     * WAI-ARIA attributes.
     */
    private const array ARIA = [
        'role',
        'aria-activedescendant',
        'aria-atomic',
        'aria-autocomplete',
        'aria-busy',
        'aria-checked',
        'aria-colcount',
        'aria-colindex',
        'aria-colspan',
        'aria-controls',
        'aria-current',
        'aria-describedby',
        'aria-details',
        'aria-disabled',
        'aria-dropeffect',
        'aria-errormessage',
        'aria-expanded',
        'aria-flowto',
        'aria-grabbed',
        'aria-haspopup',
        'aria-hidden',
        'aria-invalid',
        'aria-keyshortcuts',
        'aria-label',
        'aria-labelledby',
        'aria-level',
        'aria-live',
        'aria-modal',
        'aria-multiline',
        'aria-multiselectable',
        'aria-orientation',
        'aria-owns',
        'aria-placeholder',
        'aria-posinset',
        'aria-pressed',
        'aria-readonly',
        'aria-relevant',
        'aria-required',
        'aria-roledescription',
        'aria-rowcount',
        'aria-rowindex',
        'aria-rowspan',
        'aria-selected',
        'aria-setsize',
        'aria-sort',
        'aria-valuemax',
        'aria-valuemin',
        'aria-valuenow',
        'aria-valuetext',
    ];

    /**
     * Global event attributes.
     */
    private const array GLOBAL_EVENTS = [
        'oncancel',
        'oncanplay',
        'oncanplaythrough',
        'onchange',
        'onclick',
        'onclose',
        'oncuechange',
        'ondblclick',
        'ondrag',
        'ondragend',
        'ondragenter',
        'ondragexit',
        'ondragleave',
        'ondragover',
        'ondragstart',
        'ondrop',
        'ondurationchange',
        'onemptied',
        'onended',
        'onerror',
        'onfocus',
        'oninput',
        'oninvalid',
        'onkeydown',
        'onkeypress',
        'onkeyup',
        'onload',
        'onloadeddata',
        'onloadedmetadata',
        'onloadstart',
        'onmousedown',
        'onmouseenter',
        'onmouseleave',
        'onmousemove',
        'onmouseout',
        'onmouseover',
        'onmouseup',
        'onmousewheel',
        'onwheel',
        'onpause',
        'onplay',
        'onplaying',
        'onprogress',
        'onratechange',
        'onreset',
        'onresize',
        'onscroll',
        'onseeked',
        'onseeking',
        'onselect',
        'onshow',
        'onstalled',
        'onsubmit',
        'onsuspend',
        'ontimeupdate',
        'ontoggle',
        'onvolumechange',
        'onwaiting',
    ];

    /**
     * Document element event attributes.
     */
    private const array DOCUMENT_ELEMENT_EVENTS = ['oncopy', 'oncut', 'onpaste'];

    /**
     * Graphical event attributes from SVG 1.1 not covered by the global event attributes.
     */
    private const array GRAPHICAL_EVENTS = ['onfocusin', 'onfocusout'];

    /**
     * Document event attributes (outermost svg element).
     */
    private const array DOCUMENT_EVENTS = [
        'onabort',
        'onafterprint',
        'onbeforeprint',
        'onerror',
        'onhashchange',
        'onload',
        'onmessage',
        'onoffline',
        'ononline',
        'onpagehide',
        'onpageshow',
        'onpopstate',
        'onresize',
        'onscroll',
        'onstorage',
        'onunload',
    ];

    /**
     * Animation event attributes.
     */
    private const array ANIMATION_EVENTS = ['onbegin', 'onend', 'onrepeat'];

    /**
     * Animation timing attributes (plus SVG Tiny 1.2 synchronization attributes).
     */
    private const array ANIMATION_TIMING = [
        'begin',
        'dur',
        'end',
        'min',
        'max',
        'restart',
        'repeatCount',
        'repeatDur',
        'fill',
        'syncBehavior',
        'syncTolerance',
        'syncMaster',
    ];

    /**
     * Animation value attributes.
     */
    private const array ANIMATION_VALUE = ['calcMode', 'values', 'keyTimes', 'keySplines', 'from', 'to', 'by'];

    /**
     * Animation addition attributes.
     */
    private const array ANIMATION_ADDITION = ['additive', 'accumulate'];

    /**
     * Animation attribute target attributes.
     */
    private const array ANIMATION_TARGET = ['attributeName', 'attributeType'];

    /**
     * Reference attributes: href and the XLink attributes.
     */
    private const array XLINK = [
        'href',
        'xlink:href',
        'xlink:type',
        'xlink:role',
        'xlink:arcrole',
        'xlink:title',
        'xlink:show',
        'xlink:actuate',
    ];

    /**
     * SVG Tiny 1.2 focus and navigation attributes.
     */
    private const array NAVIGATION = [
        'focusable',
        'focusHighlight',
        'nav-next',
        'nav-prev',
        'nav-up',
        'nav-up-right',
        'nav-right',
        'nav-down-right',
        'nav-down',
        'nav-down-left',
        'nav-left',
        'nav-up-left',
    ];

    /**
     * Filter primitive attributes.
     */
    private const array FILTER_PRIMITIVE = ['x', 'y', 'width', 'height', 'result'];

    /**
     * Transfer function element attributes (feFuncR, feFuncG, feFuncB, feFuncA).
     */
    private const array TRANSFER_FUNCTION = [
        'type',
        'tableValues',
        'slope',
        'intercept',
        'amplitude',
        'exponent',
        'offset',
    ];

    /**
     * Text positioning attributes shared by text, tspan, tref, altGlyph and glyphRef.
     */
    private const array TEXT_POSITIONING = ['x', 'y', 'dx', 'dy', 'rotate'];

    /**
     * Attributes of HTML elements that may appear inside an SVG document.
     */
    private const array HTML_GLOBAL = ['title'];

    private const array GROUPS = [
        'core' => self::CORE,
        'legacy' => self::LEGACY,
        'rdfa' => self::RDFA,
        'conditionalProcessing' => self::CONDITIONAL_PROCESSING,
        'presentation' => self::PRESENTATION,
        'legacyPresentation' => self::LEGACY_PRESENTATION,
        'transform' => self::TRANSFORM,
        'aria' => self::ARIA,
        'globalEvents' => self::GLOBAL_EVENTS,
        'documentElementEvents' => self::DOCUMENT_ELEMENT_EVENTS,
        'graphicalEvents' => self::GRAPHICAL_EVENTS,
        'documentEvents' => self::DOCUMENT_EVENTS,
        'animationEvents' => self::ANIMATION_EVENTS,
        'animationTiming' => self::ANIMATION_TIMING,
        'animationValue' => self::ANIMATION_VALUE,
        'animationAddition' => self::ANIMATION_ADDITION,
        'animationTarget' => self::ANIMATION_TARGET,
        'xlink' => self::XLINK,
        'navigation' => self::NAVIGATION,
        'filterPrimitive' => self::FILTER_PRIMITIVE,
        'transferFunction' => self::TRANSFER_FUNCTION,
        'textPositioning' => self::TEXT_POSITIONING,
        'htmlGlobal' => self::HTML_GLOBAL,
    ];

    /**
     * Groups shared by every SVG element.
     */
    private const array BASE = ['core', 'legacy', 'rdfa', 'presentation', 'legacyPresentation'];

    /**
     * Event groups of elements that receive global events.
     */
    private const array EVENTS = ['globalEvents', 'documentElementEvents', 'graphicalEvents'];

    /**
     * Groups of graphics elements, containers and text content elements.
     */
    private const array GRAPHICS = [
        ...self::BASE,
        ...self::EVENTS,
        'transform',
        'conditionalProcessing',
        'aria',
        'navigation',
    ];

    /**
     * Groups shared by every animation element.
     */
    private const array ANIMATION = [
        ...self::BASE,
        ...self::EVENTS,
        'conditionalProcessing',
        'animationEvents',
        'animationTiming',
        'xlink',
    ];

    /**
     * Groups of filter primitive elements.
     */
    private const array FILTER_PRIMITIVE_GROUPS = [...self::BASE, 'filterPrimitive'];

    /**
     * Groups of HTML elements that may appear inside an SVG document.
     */
    private const array HTML = ['core', 'legacy', 'aria', 'globalEvents', 'documentElementEvents', 'htmlGlobal'];

    /**
     * Element name => groups and element-specific attributes.
     * Keys are the SvgTag values; camelCase spellings are added where SvgTag uses a lowercase value.
     */
    private const array ELEMENTS = [
        '#text' => ['groups' => [], 'attributes' => []],
        'a' => [
            'groups' => [...self::GRAPHICS, 'xlink'],
            'attributes' => ['target', 'download', 'hreflang', 'ping', 'referrerpolicy', 'rel', 'type'],
        ],
        'altglyph' => [
            'groups' => [...self::GRAPHICS, 'xlink', 'textPositioning'],
            'attributes' => ['glyphRef', 'format'],
        ],
        'altGlyph' => [
            'groups' => [...self::GRAPHICS, 'xlink', 'textPositioning'],
            'attributes' => ['glyphRef', 'format'],
        ],
        'altglyphdef' => ['groups' => ['core', 'legacy', 'rdfa'], 'attributes' => []],
        'altGlyphDef' => ['groups' => ['core', 'legacy', 'rdfa'], 'attributes' => []],
        'altglyphitem' => ['groups' => ['core', 'legacy', 'rdfa'], 'attributes' => []],
        'altGlyphItem' => ['groups' => ['core', 'legacy', 'rdfa'], 'attributes' => []],
        'animate' => [
            'groups' => [...self::ANIMATION, 'animationTarget', 'animationValue', 'animationAddition'],
            'attributes' => [],
        ],
        'animatecolor' => [
            'groups' => [...self::ANIMATION, 'animationTarget', 'animationValue', 'animationAddition'],
            'attributes' => [],
        ],
        'animateColor' => [
            'groups' => [...self::ANIMATION, 'animationTarget', 'animationValue', 'animationAddition'],
            'attributes' => [],
        ],
        'animatemotion' => [
            'groups' => [...self::ANIMATION, 'animationValue', 'animationAddition'],
            'attributes' => ['keyPoints', 'path', 'rotate', 'origin'],
        ],
        'animateMotion' => [
            'groups' => [...self::ANIMATION, 'animationValue', 'animationAddition'],
            'attributes' => ['keyPoints', 'path', 'rotate', 'origin'],
        ],
        'animatetransform' => [
            'groups' => [...self::ANIMATION, 'animationTarget', 'animationValue', 'animationAddition'],
            'attributes' => ['type'],
        ],
        'animateTransform' => [
            'groups' => [...self::ANIMATION, 'animationTarget', 'animationValue', 'animationAddition'],
            'attributes' => ['type'],
        ],
        'circle' => ['groups' => self::GRAPHICS, 'attributes' => ['cx', 'cy', 'r', 'pathLength']],
        'clipPath' => [
            'groups' => [...self::BASE, 'transform', 'conditionalProcessing'],
            'attributes' => ['clipPathUnits'],
        ],
        'clippath' => [
            'groups' => [...self::BASE, 'transform', 'conditionalProcessing'],
            'attributes' => ['clipPathUnits'],
        ],
        'defs' => ['groups' => [...self::BASE, ...self::EVENTS, 'transform', 'conditionalProcessing'], 'attributes' => []],
        'desc' => ['groups' => [...self::BASE, ...self::EVENTS], 'attributes' => []],
        'ellipse' => ['groups' => self::GRAPHICS, 'attributes' => ['cx', 'cy', 'rx', 'ry', 'pathLength']],
        'embed' => ['groups' => self::HTML, 'attributes' => ['src', 'type', 'width', 'height']],
        'feBlend' => ['groups' => self::FILTER_PRIMITIVE_GROUPS, 'attributes' => ['in', 'in2', 'mode']],
        'feColorMatrix' => ['groups' => self::FILTER_PRIMITIVE_GROUPS, 'attributes' => ['in', 'type', 'values']],
        'feComponentTransfer' => ['groups' => self::FILTER_PRIMITIVE_GROUPS, 'attributes' => ['in']],
        'feComposite' => [
            'groups' => self::FILTER_PRIMITIVE_GROUPS,
            'attributes' => ['in', 'in2', 'operator', 'k1', 'k2', 'k3', 'k4'],
        ],
        'feConvolveMatrix' => [
            'groups' => self::FILTER_PRIMITIVE_GROUPS,
            'attributes' => [
                'in',
                'order',
                'kernelMatrix',
                'divisor',
                'bias',
                'targetX',
                'targetY',
                'edgeMode',
                'kernelUnitLength',
                'preserveAlpha',
            ],
        ],
        'feDiffuseLighting' => [
            'groups' => self::FILTER_PRIMITIVE_GROUPS,
            'attributes' => ['in', 'surfaceScale', 'diffuseConstant', 'kernelUnitLength'],
        ],
        'feDisplacementMap' => [
            'groups' => self::FILTER_PRIMITIVE_GROUPS,
            'attributes' => ['in', 'in2', 'scale', 'xChannelSelector', 'yChannelSelector'],
        ],
        'feDistantLight' => ['groups' => self::BASE, 'attributes' => ['azimuth', 'elevation']],
        'feDropShadow' => [
            'groups' => self::FILTER_PRIMITIVE_GROUPS,
            'attributes' => ['in', 'dx', 'dy', 'stdDeviation'],
        ],
        'feFlood' => ['groups' => self::FILTER_PRIMITIVE_GROUPS, 'attributes' => []],
        'feFuncA' => ['groups' => [...self::BASE, 'transferFunction'], 'attributes' => []],
        'feFuncB' => ['groups' => [...self::BASE, 'transferFunction'], 'attributes' => []],
        'feFuncG' => ['groups' => [...self::BASE, 'transferFunction'], 'attributes' => []],
        'feFuncR' => ['groups' => [...self::BASE, 'transferFunction'], 'attributes' => []],
        'feGaussianBlur' => [
            'groups' => self::FILTER_PRIMITIVE_GROUPS,
            'attributes' => ['in', 'stdDeviation', 'edgeMode'],
        ],
        'feImage' => [
            'groups' => [...self::FILTER_PRIMITIVE_GROUPS, 'xlink'],
            'attributes' => ['preserveAspectRatio', 'crossorigin', 'fetchpriority'],
        ],
        'feMerge' => ['groups' => self::FILTER_PRIMITIVE_GROUPS, 'attributes' => []],
        'feMergeNode' => ['groups' => self::BASE, 'attributes' => ['in']],
        'feMorphology' => ['groups' => self::FILTER_PRIMITIVE_GROUPS, 'attributes' => ['in', 'operator', 'radius']],
        'feOffset' => ['groups' => self::FILTER_PRIMITIVE_GROUPS, 'attributes' => ['in', 'dx', 'dy']],
        'fePointLight' => ['groups' => self::BASE, 'attributes' => ['x', 'y', 'z']],
        'feSpecularLighting' => [
            'groups' => self::FILTER_PRIMITIVE_GROUPS,
            'attributes' => ['in', 'surfaceScale', 'specularConstant', 'specularExponent', 'kernelUnitLength'],
        ],
        'feSpotLight' => [
            'groups' => self::BASE,
            'attributes' => [
                'x',
                'y',
                'z',
                'pointsAtX',
                'pointsAtY',
                'pointsAtZ',
                'specularExponent',
                'limitingConeAngle',
            ],
        ],
        'feTile' => ['groups' => self::FILTER_PRIMITIVE_GROUPS, 'attributes' => ['in']],
        'feTurbulence' => [
            'groups' => self::FILTER_PRIMITIVE_GROUPS,
            'attributes' => ['baseFrequency', 'numOctaves', 'seed', 'stitchTiles', 'type'],
        ],
        'filter' => [
            'groups' => [...self::BASE, 'xlink'],
            'attributes' => ['x', 'y', 'width', 'height', 'filterUnits', 'primitiveUnits', 'filterRes'],
        ],
        'font' => [
            'groups' => self::BASE,
            'attributes' => [
                'horiz-origin-x',
                'horiz-origin-y',
                'horiz-adv-x',
                'vert-origin-x',
                'vert-origin-y',
                'vert-adv-y',
            ],
        ],
        'foreignObject' => ['groups' => self::GRAPHICS, 'attributes' => ['x', 'y', 'width', 'height']],
        'g' => ['groups' => self::GRAPHICS, 'attributes' => []],
        'glyph' => [
            'groups' => self::BASE,
            'attributes' => [
                'unicode',
                'glyph-name',
                'd',
                'orientation',
                'arabic-form',
                'horiz-adv-x',
                'vert-origin-x',
                'vert-origin-y',
                'vert-adv-y',
            ],
        ],
        'glyphref' => [
            'groups' => [...self::BASE, 'xlink', 'textPositioning'],
            'attributes' => ['glyphRef', 'format'],
        ],
        'glyphRef' => [
            'groups' => [...self::BASE, 'xlink', 'textPositioning'],
            'attributes' => ['glyphRef', 'format'],
        ],
        'hkern' => ['groups' => ['core', 'legacy', 'rdfa'], 'attributes' => ['u1', 'g1', 'u2', 'g2', 'k']],
        'iframe' => [
            'groups' => self::HTML,
            'attributes' => ['src', 'name', 'width', 'height', 'referrerpolicy'],
        ],
        'image' => [
            'groups' => [...self::GRAPHICS, 'xlink'],
            'attributes' => [
                'x',
                'y',
                'width',
                'height',
                'preserveAspectRatio',
                'crossorigin',
                'decoding',
                'fetchpriority',
            ],
        ],
        'line' => ['groups' => self::GRAPHICS, 'attributes' => ['x1', 'y1', 'x2', 'y2', 'pathLength']],
        'linearGradient' => [
            'groups' => [...self::BASE, ...self::EVENTS, 'xlink'],
            'attributes' => ['x1', 'y1', 'x2', 'y2', 'gradientUnits', 'gradientTransform', 'spreadMethod'],
        ],
        'link' => [
            'groups' => self::HTML,
            'attributes' => [
                'href',
                'rel',
                'type',
                'media',
                'crossorigin',
                'hreflang',
                'sizes',
                'integrity',
                'referrerpolicy',
            ],
        ],
        'marker' => [
            'groups' => [...self::BASE, ...self::EVENTS, 'transform'],
            'attributes' => [
                'viewBox',
                'preserveAspectRatio',
                'refX',
                'refY',
                'markerUnits',
                'markerWidth',
                'markerHeight',
                'orient',
            ],
        ],
        'mask' => [
            'groups' => [...self::BASE, 'transform', 'conditionalProcessing'],
            'attributes' => ['x', 'y', 'width', 'height', 'maskUnits', 'maskContentUnits'],
        ],
        'metadata' => ['groups' => [...self::BASE, ...self::EVENTS], 'attributes' => []],
        'mpath' => ['groups' => [...self::BASE, ...self::EVENTS, 'xlink'], 'attributes' => []],
        'object' => ['groups' => self::HTML, 'attributes' => ['type', 'name', 'width', 'height']],
        'path' => ['groups' => self::GRAPHICS, 'attributes' => ['d', 'pathLength']],
        'pattern' => [
            'groups' => [...self::BASE, ...self::EVENTS, 'xlink', 'conditionalProcessing'],
            'attributes' => [
                'x',
                'y',
                'width',
                'height',
                'viewBox',
                'preserveAspectRatio',
                'patternUnits',
                'patternContentUnits',
                'patternTransform',
            ],
        ],
        'polygon' => ['groups' => self::GRAPHICS, 'attributes' => ['points', 'pathLength']],
        'polyline' => ['groups' => self::GRAPHICS, 'attributes' => ['points', 'pathLength']],
        'radialGradient' => [
            'groups' => [...self::BASE, ...self::EVENTS, 'xlink'],
            'attributes' => ['cx', 'cy', 'r', 'fx', 'fy', 'fr', 'gradientUnits', 'gradientTransform', 'spreadMethod'],
        ],
        'rect' => [
            'groups' => self::GRAPHICS,
            'attributes' => ['x', 'y', 'width', 'height', 'rx', 'ry', 'pathLength'],
        ],
        'script' => [
            'groups' => [...self::BASE, ...self::EVENTS, 'xlink'],
            'attributes' => ['type', 'crossorigin', 'fetchpriority', 'ev:event'],
        ],
        'set' => ['groups' => [...self::ANIMATION, 'animationTarget'], 'attributes' => ['to']],
        'stop' => ['groups' => [...self::BASE, ...self::EVENTS], 'attributes' => ['offset']],
        'style' => ['groups' => [...self::BASE, ...self::EVENTS], 'attributes' => ['type', 'media', 'title']],
        'svg' => [
            'groups' => [...self::GRAPHICS, 'documentEvents'],
            'attributes' => [
                'x',
                'y',
                'width',
                'height',
                'viewBox',
                'preserveAspectRatio',
                'zoomAndPan',
                'version',
                'baseProfile',
                'contentScriptType',
                'contentStyleType',
                'playbackorder',
                'timelinebegin',
                'playbackOrder',
                'timelineBegin',
                'snapshotTime',
                'syncBehaviorDefault',
                'syncToleranceDefault',
                'xmlns',
                'xmlns:xlink',
            ],
        ],
        'switch' => ['groups' => self::GRAPHICS, 'attributes' => []],
        'symbol' => [
            'groups' => self::GRAPHICS,
            'attributes' => ['x', 'y', 'width', 'height', 'viewBox', 'preserveAspectRatio', 'refX', 'refY'],
        ],
        'text' => ['groups' => [...self::GRAPHICS, 'textPositioning'], 'attributes' => ['lengthAdjust', 'textLength']],
        'textPath' => [
            'groups' => [...self::GRAPHICS, 'xlink'],
            'attributes' => ['startOffset', 'method', 'spacing', 'side', 'path', 'lengthAdjust', 'textLength'],
        ],
        'textpath' => [
            'groups' => [...self::GRAPHICS, 'xlink'],
            'attributes' => ['startOffset', 'method', 'spacing', 'side', 'path', 'lengthAdjust', 'textLength'],
        ],
        'title' => ['groups' => [...self::BASE, ...self::EVENTS], 'attributes' => []],
        'tref' => [
            'groups' => [...self::GRAPHICS, 'xlink', 'textPositioning'],
            'attributes' => ['lengthAdjust', 'textLength'],
        ],
        'tspan' => ['groups' => [...self::GRAPHICS, 'textPositioning'], 'attributes' => ['lengthAdjust', 'textLength']],
        'use' => ['groups' => [...self::GRAPHICS, 'xlink'], 'attributes' => ['x', 'y', 'width', 'height']],
        'view' => [
            'groups' => [...self::BASE, ...self::EVENTS, 'aria'],
            'attributes' => ['viewBox', 'preserveAspectRatio', 'zoomAndPan', 'viewTarget'],
        ],
        'vkern' => ['groups' => ['core', 'legacy', 'rdfa'], 'attributes' => ['u1', 'g1', 'u2', 'g2', 'k']],
    ];

    private function __construct() {}

    /**
     * @param string $element the element name as written in markup
     *
     * @return bool true if attribute data exists for the element
     */
    public static function isKnownElement(string $element): bool
    {
        return \array_key_exists($element, self::ELEMENTS);
    }

    /**
     * @param string $element the element name as written in markup
     *
     * @return array<string, true> a lookup of the qualified attribute names allowed on the element, empty for unknown elements
     */
    public static function forElement(string $element): array
    {
        /** @var array<string, array<string, true>> $cache */
        static $cache = [];

        return $cache[$element] ??= self::build($element);
    }

    /**
     * @return list<string> the names of every element with attribute data
     */
    public static function elements(): array
    {
        return array_keys(self::ELEMENTS);
    }

    /**
     * @param string $element the element name as written in markup
     *
     * @return array<string, true> the allowed attribute lookup
     */
    private static function build(string $element): array
    {
        if (!self::isKnownElement($element)) {
            return [];
        }

        $definition = self::ELEMENTS[$element];
        $names = $definition['attributes'];

        foreach ($definition['groups'] as $group) {
            $names = [...$names, ...self::GROUPS[$group]];
        }

        return array_fill_keys($names, true);
    }
}
