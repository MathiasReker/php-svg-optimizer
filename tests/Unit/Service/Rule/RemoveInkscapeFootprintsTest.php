<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\AbstractXmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveInkscapeFootprints::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(AbstractXmlProcessor::class)]
final class RemoveInkscapeFootprintsTest extends TestCase
{
    /**
     * @throws SvgValidationException
     */
    #[DataProvider('provideOptimizeCases')]
    public function testOptimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveInkscapeFootprints());

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
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><script>        console.log("Hello Inkscape");    </script><rect width="100" height="100"/></svg>
                XML,
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
    }
}
