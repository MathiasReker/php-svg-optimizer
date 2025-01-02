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
use MathiasReker\PhpSvgOptimizer\Services\Rules\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifySvgCoordinates::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MinifySvgCoordinatesTest extends TestCase
{
    public static function svgCoordinatesProvider(): \Iterator
    {
        yield 'Optimize Path Coordinates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <path d="M10.000000 20.000000 L30.500000 40.500000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><path d="M10 20 L30.5 40.5"/></svg>
                XML,
        ];

        yield 'Optimize Rect Coordinates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10.000000" y="20.000000" width="30.000000" height="40.000000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="20" width="30" height="40"/></svg>
                XML,
        ];

        yield 'Optimize Circle Coordinates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50.000000" cy="50.000000" r="25.000000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="25"/></svg>
                XML,
        ];

        yield 'Optimize Polyline Coordinates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <polyline points="10.000000,20.000000 30.000000,40.000000 50.500000,60.500000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><polyline points="10,20 30,40 50.5,60.5"/></svg>
                XML,
        ];

        yield 'Handles Large and Small Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="1000000.000000" y="-0.0000001" width="0.000001" height="0.00000000001"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="1000000" y="-0.0000001" width="0.000001" height="0.00000000001"/></svg>
                XML,
        ];

        yield 'Processes Mixed Decimal and Integer Coordinates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <line x1="10.500" y1="20" x2="30" y2="40.5"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><line x1="10.5" y1="20" x2="30" y2="40.5"/></svg>
                XML,
        ];

        yield 'Minifies Coordinates in Complex Paths' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <path d="M10.0000 20.0000 L30.0000 40.0000 Q50.0000 60.0000 70.0000 80.0000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><path d="M10 20 L30 40 Q50 60 70 80"/></svg>
                XML,
        ];

        yield 'Handles Single-Value Coordinates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="1.0" cy="1.0" r="1.000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="1" cy="1" r="1"/></svg>
                XML,
        ];

        yield 'Keeps Already Minified Coordinates Unchanged' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="20" width="30" height="40"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="20" width="30" height="40"/></svg>
                XML,
        ];

        yield 'Handles Coordinates with Scientific Notation' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="1e-5" cy="1e5" r="2e2"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="1e-5" cy="1e5" r="2e2"/></svg>
                XML,
        ];

        yield 'Handles Zero Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="0.0000000" y="0.0000000" width="0.0000000" height="0.0000000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="0" y="0" width="0" height="0"/></svg>
                XML,
        ];

        yield 'Handles Very Small Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="0.0000000001" cy="0.0000000001" r="0.0000000001"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="0.0000000001" cy="0.0000000001" r="0.0000000001"/></svg>
                XML,
        ];

        yield 'Handles Very Large Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="1000000000.000000" cy="1000000000.000000" r="1000000000.000000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="1000000000" cy="1000000000" r="1000000000"/></svg>
                XML,
        ];

        yield 'Handles Mixed Scientific and Decimal Notation' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="1e-10" cy="1e10" r="1e-5"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="1e-10" cy="1e10" r="1e-5"/></svg>
                XML,
        ];

        yield 'Handles Negative Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="-10.000000" cy="-20.000000" r="-30.000000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="-10" cy="-20" r="-30"/></svg>
                XML,
        ];

        yield 'Handles Multiple Coordinated Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10.0000" y="20.0000" width="30.0000" height="40.0000"/>
                    <circle cx="50.0000" cy="50.0000" r="25.0000"/>
                    <path d="M10.0000 20.0000 L30.0000 40.0000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="20" width="30" height="40"/><circle cx="50" cy="50" r="25"/><path d="M10 20 L30 40"/></svg>
                XML,
        ];
    }

    /**
     * @throws SvgValidationException
     */
    #[DataProvider('svgCoordinatesProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new MinifySvgCoordinates());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }
}
