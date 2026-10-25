<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Exception\RiskyRulesNotAllowedException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveInkscapeFootprints::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(CssDeclarationTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
final class RemoveInkscapeFootprintsTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addPass(new RemoveInkscapeFootprints());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes inkscape and sodipodi namespaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd">
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Removes inkscape specific attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">
                    <rect inkscape:label="Rectangle" width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="red"/></svg>
                XML,
        ];

        yield 'Removes sodipodi specific attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd">
                    <rect sodipodi:abswidth="100" width="100" height="100" fill="green"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="green"/></svg>
                XML,
        ];

        yield 'Does not remove non-Inkscape or non-Sodipodi attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="100" height="100" fill="yellow"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="yellow"/></svg>
                XML,
        ];

        yield 'Handles SVG without Inkscape or Sodipodi namespaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="40" fill="orange"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="orange"/></svg>
                XML,
        ];

        yield 'Handles SVG with multiple Inkscape attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">
                    <circle inkscape:label="circle1" inkscape:style="stroke:black;fill:none" cx="50" cy="50" r="40"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40"/></svg>
                XML,
        ];

        yield 'Handles SVG with multiple Sodipodi attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd">
                    <rect sodipodi:abswidth="100" sodipodi:absheight="100" width="100" height="100" fill="purple"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="purple"/></svg>
                XML,
        ];

        yield 'Handles SVG with mixed Inkscape and Sodipodi attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd">
                    <circle inkscape:label="circle1" sodipodi:abswidth="100" cx="50" cy="50" r="40"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40"/></svg>
                XML,
        ];

        yield 'Handles SVG without any attributes to remove' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 10 H 90 V 90 H 10 Z"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><path d="M10 10 H 90 V 90 H 10 Z"/></svg>
                XML,
        ];

        yield 'Handles SVG with other namespaces that are not removed' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:custom="http://www.customnamespace.com">
                    <rect custom:attribute="value" width="100" height="100" fill="brown"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:custom="http://www.customnamespace.com"><rect custom:attribute="value" width="100" height="100" fill="brown"/></svg>
                XML,
        ];

        yield 'Handles empty SVG without any elements or attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes inkscape and sodipodi on nested elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd">
                    <g inkscape:groupmode="layer" sodipodi:role="layer">
                        <rect inkscape:label="Rect" sodipodi:abswidth="100" width="100" height="100" fill="blue"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><rect width="100" height="100" fill="blue"/></g></svg>
                XML,
        ];

        yield 'Removes inkscape and sodipodi attributes on attributes with similar names' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd">
                    <rect inkscapelabel="should-not-remove" sodipodiabswidth="should-not-remove" inkscape:label="remove-this" sodipodi:abswidth="remove-this" width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect inkscapelabel="should-not-remove" sodipodiabswidth="should-not-remove" width="100" height="100"/></svg>
                XML,
        ];

        yield 'Removes multiple namespaces but keeps default ones intact' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="#someId" inkscape:label="label"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#someId"/></svg>
                XML,
        ];

        yield 'Handles SVG with CDATA section inside elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">
                    <script><![CDATA[
                        console.log("Hello Inkscape");
                    ]]></script>
                    <rect inkscape:label="rect" width="100" height="100"/>
                </svg>
                XML,
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg"><script>
                        console.log("Hello Inkscape");
                    </script><rect width="100" height="100"/></svg>
                EOD,
        ];

        yield 'Handles elements with multiple attributes including inkscape and sodipodi' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">
                    <ellipse inkscape:label="ellipse" sodipodi:cx="50" cx="50" cy="50" rx="40" ry="20" fill="pink"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><ellipse cx="50" cy="50" rx="40" ry="20" fill="pink"/></svg>
                XML,
        ];

        yield 'Handles comments and whitespace preservation correctly' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">
                    <!-- This is a comment -->
                    <rect inkscape:label="rect" width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><!-- This is a comment --><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles self-closing tags with inkscape attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">
                    <line inkscape:label="line1" x1="0" y1="0" x2="100" y2="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><line x1="0" y1="0" x2="100" y2="100"/></svg>
                XML,
        ];

        yield 'SVG with only Inkscape/Sodipodi namespaces' => [
            <<<'XML'
                <svg xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"/>
                XML,
            <<<'XML'
                <svg/>
                XML,
        ];

        yield 'SVG with Inkscape/Sodipodi elements and attributes, but no namespaces' => [
            <<<'XML'
                <svg>
                    <g inkscape:groupmode="layer" sodipodi:role="layer">
                        <rect inkscape:label="Rect" sodipodi:abswidth="100" width="100" height="100" fill="blue"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg><g inkscape:groupmode="layer" sodipodi:role="layer"><rect inkscape:label="Rect" sodipodi:abswidth="100" width="100" height="100" fill="blue"/></g></svg>
                XML,
        ];

        yield 'Removes genuine Sodipodi and Inkscape namespaced elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">
                    <sodipodi:namedview id="base"/>
                    <inkscape:custom id="thing"/>
                    <rect width="100" height="100" fill="cyan"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="cyan"/></svg>
                XML,
        ];

        foreach (self::EDITOR_NAMESPACE_URIS as $editor => $namespaceUri) {
            yield 'Removes ' . $editor . ' elements, attributes and declarations' => [
                '<svg xmlns="http://www.w3.org/2000/svg" xmlns:e="' . $namespaceUri . '"><e:data e:id="1"><e:item/>text</e:data><g e:id="2"><rect e:type="x" width="1"/></g></svg>',
                '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1"/></g></svg>',
            ];

            yield 'Removes foreignObject requiring the ' . $editor . ' extension' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><switch><foreignObject requiredExtensions="' . $namespaceUri . '" width="1" height="1"/><rect width="1"/></switch></svg>',
                '<svg xmlns="http://www.w3.org/2000/svg"><switch><rect width="1"/></switch></svg>',
            ];
        }

        yield 'Removes Adobe Illustrator footprints declared through DTD entities' => [
            <<<'XML'
                <?xml version="1.0" encoding="utf-8"?>
                <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd" [
                    <!ENTITY ns_extend "http://ns.adobe.com/Extensibility/1.0/">
                    <!ENTITY ns_ai "http://ns.adobe.com/AdobeIllustrator/10.0/">
                    <!ENTITY ns_graphs "http://ns.adobe.com/Graphs/1.0/">
                    <!ENTITY ns_vars "http://ns.adobe.com/Variables/1.0/">
                    <!ENTITY ns_sfw "http://ns.adobe.com/SaveForWeb/1.0/">
                ]>
                <svg xmlns:x="&ns_extend;" xmlns:i="&ns_ai;" xmlns:graph="&ns_graphs;" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" i:viewOrigin="0 0" i:rulerOrigin="0 0" i:pageBounds="0 100 100 0">
                    <switch>
                        <foreignObject requiredExtensions="&ns_ai;" x="0" y="0" width="1" height="1">
                            <i:pgfRef xlink:href="#adobe_illustrator_pgf"/>
                        </foreignObject>
                        <g i:extraneous="self">
                            <rect width="100" height="100" fill="red"/>
                        </g>
                    </switch>
                    <i:pgf id="adobe_illustrator_pgf">eJzsvQ</i:pgf>
                    <x:sfw><graph:graph/></x:sfw>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><switch><g><rect width="100" height="100" fill="red"/></g></switch></svg>
                XML,
        ];

        yield 'Removes editor elements bound to the default namespace through a DTD entity' => [
            <<<'XML'
                <!DOCTYPE svg [
                    <!ENTITY ns_sfw "http://ns.adobe.com/SaveForWeb/1.0/">
                ]>
                <svg xmlns="http://www.w3.org/2000/svg"><sfw xmlns="&ns_sfw;"><slices/><sliceSourceBounds height="1" width="1"/></sfw><rect width="1"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>
                XML,
        ];

        yield 'Keeps nodes declared through a DTD entity resolving to another namespace' => [
            <<<'XML'
                <!DOCTYPE svg [
                    <!ENTITY ns_custom "http://www.customnamespace.com">
                ]>
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:c="&ns_custom;"><c:data/><rect c:id="1" width="1"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:data/><rect c:id="1" width="1"/></svg>
                XML,
        ];

        yield 'Removes Adobe Illustrator nodes from the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><sfw xmlns="http://ns.adobe.com/SaveForWeb/1.0/"><slices/></sfw><rect width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1"/></svg>',
        ];

        yield 'Removes Sketch attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns"><g sketch:type="MSPage" fill="none"><path sketch:type="MSShapeGroup" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="none"><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes Serif attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:serif="http://www.serif.com/"><g serif:id="Layer 1"><rect width="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1"/></g></svg>',
        ];

        yield 'Removes Microsoft Visio elements and attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:v="http://schemas.microsoft.com/visio/2003/SVGExtensions/"><v:documentProperties v:langID="1033"><v:pageProperties v:drawingScale="1"/></v:documentProperties><g v:mID="0" v:index="1"><v:userDefs><v:ud v:nameU="x"/></v:userDefs><rect width="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1"/></g></svg>',
        ];

        yield 'Removes Adobe SVG viewer extension attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" a:scriptImplementation="Adobe"><g a:adobe-blending-mode="multiply"><rect width="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1"/></g></svg>',
        ];

        yield 'Matches editor namespaces by URI regardless of the prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ink="http://www.inkscape.org/namespaces/inkscape" xmlns:sp="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"><sp:namedview/><g ink:label="Layer" ink:groupmode="layer"><rect width="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1"/></g></svg>',
        ];

        yield 'Keeps editor prefixes bound to other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.customnamespace.com"><inkscape:data/><rect inkscape:label="x" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.customnamespace.com"><inkscape:data/><rect inkscape:label="x" width="1"/></svg>',
        ];

        yield 'Removes namespace declarations on nested elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns:serif="http://www.serif.com/" serif:id="a"><rect xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" sketch:type="b" width="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="1"/></g></svg>',
        ];

        yield 'Keeps editor elements containing other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:i="http://ns.adobe.com/AdobeIllustrator/10.0/"><i:wrapper i:id="1"><linearGradient id="g"/></i:wrapper><rect fill="url(#g)" width="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:i="http://ns.adobe.com/AdobeIllustrator/10.0/"><i:wrapper><linearGradient id="g"/></i:wrapper><rect fill="url(#g)" width="1"/></svg>',
        ];

        yield 'Keeps editor elements containing deeply nested other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><v:a xmlns:v="http://schemas.microsoft.com/visio/2003/SVGExtensions/"><v:b><path id="p" d="M0 0h1"/></v:b></v:a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><v:a xmlns:v="http://schemas.microsoft.com/visio/2003/SVGExtensions/"><v:b><path id="p" d="M0 0h1"/></v:b></v:a></svg>',
        ];

        yield 'Removes foreignObject when one of several required extensions is an editor extension' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><foreignObject requiredExtensions=" http://www.w3.org/1999/xhtml  http://ns.adobe.com/AdobeIllustrator/10.0/ " width="1" height="1"/><rect width="1"/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><rect width="1"/></switch></svg>',
        ];

        yield 'Keeps foreignObject requiring other extensions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><foreignObject requiredExtensions="http://www.w3.org/1999/xhtml" width="1" height="1"/><rect width="1"/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><foreignObject requiredExtensions="http://www.w3.org/1999/xhtml" width="1" height="1"/><rect width="1"/></switch></svg>',
        ];

        yield 'Keeps foreignObject with an empty required extensions attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject requiredExtensions=" " width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject requiredExtensions=" " width="1" height="1"/></svg>',
        ];

        yield 'Keeps foreignObject with a required extension that only contains an editor URI' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/x" width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/x" width="1" height="1"/></svg>',
        ];

        yield 'Keeps other elements requiring an editor extension' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"><rect width="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"><rect width="1"/></g></svg>',
        ];

        yield 'Keeps foreignObject elements from other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><c:foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/></svg>',
        ];

        yield 'Removes foreignObject requiring an editor extension in documents without a namespace' => [
            '<svg><foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/><rect width="1"/></svg>',
            '<svg><rect width="1"/></svg>',
        ];

        yield 'Keeps unused declarations of other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com" xmlns:serif="http://www.serif.com/"><g xmlns:d="http://www.customnamespace.com/d"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><g xmlns:d="http://www.customnamespace.com/d"/></svg>',
        ];

        yield 'Keeps attributes from the XML and XLink namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:serif="http://www.serif.com/" xml:space="preserve"><use xlink:href="#a" xml:lang="en" serif:id="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"><use xlink:href="#a" xml:lang="en"/></svg>',
        ];

        yield 'Removes editor attributes bound to a custom prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:ik="http://www.inkscape.org/namespaces/inkscape"><rect ik:label="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes an editor namespace declared on a nested element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" inkscape:label="x"><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/></g></svg>',
        ];

        yield 'Keeps an editor element that holds SVG content' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><inkscape:foo><rect/></inkscape:foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><inkscape:foo><rect/></inkscape:foo></svg>',
        ];

        yield 'Removes editor attributes deep in nested groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><g><g><rect inkscape:label="x"/></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><rect/></g></g></g></svg>',
        ];

        yield 'Keeps a foreignObject that requires a non-editor extension' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><foreignObject requiredExtensions="http://example.com/ext"><x:a xmlns:x="urn:x"/></foreignObject></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><foreignObject requiredExtensions="http://example.com/ext"><x:a xmlns:x="urn:x"/></foreignObject></switch></svg>',
        ];

        yield 'Handles an empty document' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>',
        ];

        yield 'Handles a root with only text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">text</svg>',
        ];

        yield 'Handles a 60 levels deep structure' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect inkscape:label="x"/>' . str_repeat('</g>', 60) . '</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">' . str_repeat('<g>', 60) . '<rect/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'Handles a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" inkscape:label="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="' . str_repeat('a', 10_000) . '"/></svg>',
        ];

        yield 'Handles right-to-left, emoji, combining and astral text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text inkscape:label="x">שלום 😀 é 𝒜</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><text>שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Handles values padded with tabs, newlines and no-break spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect inkscape:label="x" fill="&#9; red&#10;&#xA0;"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\"><rect fill=\"&#9; red&#10;\u{a0}\"/></svg>",
        ];

        yield 'Handles comments between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect inkscape:label="x"/><!--4--></g><!--5--></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><!--1--><g><!--2--><!--3--><rect/><!--4--></g><!--5--></svg>',
        ];

        yield 'Leaves a document with an external style sheet untouched' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect inkscape:label="x"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect inkscape:label="x"/></svg>',
        ];

        yield 'Leaves a document with a linked style sheet untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><rect inkscape:label="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><rect inkscape:label="x"/></svg>',
        ];

        yield 'Leaves a document with an imported style sheet untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><style>@import url(a.css);</style><rect inkscape:label="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><style>@import url(a.css);</style><rect inkscape:label="x"/></svg>',
        ];

        yield 'Handles processing instructions' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect inkscape:label="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><?foo bar?><rect/></svg>',
        ];

        yield 'Keeps editor attributes that a style sheet selects' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><style>rect[*|label]{fill:red}</style><rect inkscape:label="x" inkscape:groupmode="layer"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><style>rect[*|label]{fill:red}</style><rect inkscape:label="x"/></svg>',
        ];

        yield 'Keeps editor elements when a style sheet depends on the siblings of an element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"><style>rect:first-child{fill:red}</style><g><sodipodi:namedview sodipodi:docname="a"/><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"><style>rect:first-child{fill:red}</style><g><sodipodi:namedview/><rect/></g></svg>',
        ];

        yield 'Keeps an Illustrator foreignObject when a style sheet depends on the siblings of an element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g:first-child{fill:red}</style><switch><foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/><g/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g:first-child{fill:red}</style><switch><foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/><g/></switch></svg>',
        ];

        yield 'Handles CDATA sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text inkscape:label="x"><![CDATA[a<b&c]]></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><text>a&lt;b&amp;c</text></svg>',
        ];

        yield 'Handles a child that resets the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect inkscape:label="x"/></x></g><rect inkscape:label="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><x xmlns=""><rect/></x></g><rect/></svg>',
        ];

        yield 'Handles a prefix redeclared to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect inkscape:label="x"/></s:g><g xmlns:s="urn:other"><s:rect inkscape:label="x"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect/></s:g><g xmlns:s="urn:other"><s:rect/></g></svg>',
        ];

        yield 'Handles entities and character references in values and text' => [
            '<!DOCTYPE svg [<!ENTITY e "v&#x41;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text inkscape:label="x" class="&e;">&e;&#x42;&#66;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><text class="vA">vABB</text></svg>',
        ];

        yield 'Handles an attribute in no namespace next to the same local name in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c" inkscape:label="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c"/></svg>',
        ];

        yield 'Handles repeated elements where only a later one matches' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect inkscape:label="x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect/><rect fill="red"/><rect/></svg>',
        ];

        yield 'Removes an unused editor declaration whose prefix is bound to a used editor namespace elsewhere' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:i="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"><style>[*|role]{fill:red}</style><rect i:role="x"/><g xmlns:i="http://www.inkscape.org/namespaces/inkscape"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[*|role]{fill:red}</style><rect xmlns:i="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" i:role="x"/><g/></svg>',
        ];

        yield 'Keeps another declaration of the prefix of an unused editor namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:i="http://www.inkscape.org/namespaces/inkscape"><g xmlns:i="urn:other"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns:i="urn:other"/></svg>',
        ];

        yield 'Handles mixed-case element names' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect inkscape:label="x"/><RECT inkscape:label="x"/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><Rect/><RECT/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
        ];

        yield 'Keeps an editor extension element that has an id or contains an SVG element with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:i="http://ns.adobe.com/AdobeIllustrator/10.0/"><foreignObject id="f" requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"><i:pgfRef/></foreignObject><foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"><svg><rect id="r"/></svg></foreignObject><foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"><i:pgf id="p"/></foreignObject><rect width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject id="f" requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"/><foreignObject requiredExtensions="http://ns.adobe.com/AdobeIllustrator/10.0/"><svg><rect id="r"/></svg></foreignObject><rect width="1" height="1"/></svg>',
        ];
    }

    private const array EDITOR_NAMESPACE_URIS = [
        'Inkscape' => 'http://www.inkscape.org/namespaces/inkscape',
        'Sodipodi' => 'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd',
        'Adobe Illustrator' => 'http://ns.adobe.com/AdobeIllustrator/10.0/',
        'Adobe SVG viewer extensions' => 'http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/',
        'Adobe extensibility' => 'http://ns.adobe.com/Extensibility/1.0/',
        'Adobe flows' => 'http://ns.adobe.com/Flows/1.0/',
        'Adobe generic custom namespace' => 'http://ns.adobe.com/GenericCustomNamespace/1.0/',
        'Adobe graphs' => 'http://ns.adobe.com/Graphs/1.0/',
        'Adobe image replacement' => 'http://ns.adobe.com/ImageReplacement/1.0/',
        'Adobe save for web' => 'http://ns.adobe.com/SaveForWeb/1.0/',
        'Adobe variables' => 'http://ns.adobe.com/Variables/1.0/',
        'Adobe XPath' => 'http://ns.adobe.com/XPath/1.0/',
        'Sketch' => 'http://www.bohemiancoding.com/sketch/ns',
        'Serif' => 'http://www.serif.com/',
        'Microsoft Visio' => 'http://schemas.microsoft.com/visio/2003/SVGExtensions/',
    ];

    #[Test]
    public function handlesManyNamespaceDeclarationsInLinearTime(): void
    {
        $small = $this->secondsToOptimize(2_000);
        $large = $this->secondsToOptimize(16_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    #[Test]
    public function handlesManyRepeatedEditorNamespaceDeclarationsInLinearTime(): void
    {
        $small = $this->secondsToOptimizeRepeatedDeclarations(1_000);
        $large = $this->secondsToOptimizeRepeatedDeclarations(4_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimizeRepeatedDeclarations(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg">' . str_repeat('<g xmlns:i="http://www.inkscape.org/namespaces/inkscape"/>', $count) . '</svg>');

        $start = hrtime(true);
        (new RemoveInkscapeFootprints())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><rect inkscape:label="x"/><inkscape:x/></svg>');

        $start = hrtime(true);
        (new RemoveInkscapeFootprints())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
