<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Rules;

use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Models\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Services\Providers\StringProvider;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveInkscapeFootprints;
use MathiasReker\PhpSvgOptimizer\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Util\XmlProcessor;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
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
#[CoversClass(XmlProcessor::class)]
final class RemoveInkscapeFootprintsTest extends TestCase
{
    public static function svgProvider(): \Iterator
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
    }

    /**
     * @throws SvgValidationException
     */
    #[DataProvider('svgProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new RemoveInkscapeFootprints());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }
}
