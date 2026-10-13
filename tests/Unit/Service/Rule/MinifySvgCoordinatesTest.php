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
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifySvgCoordinates;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifySvgCoordinates::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgNamespace::class)]
final class MinifySvgCoordinatesTest extends TestCase
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
        $svgOptimizer->addRule(new MinifySvgCoordinates());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
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

        yield 'Keeps a value with a non-ASCII character unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1.0é" height="5.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1.0&#xE9;" height="5"/></svg>',
        ];

        yield 'Keeps path data with a non-ASCII character unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.0 2.0é L3 4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.0 2.0&#xE9; L3 4"/></svg>',
        ];

        yield 'Keeps points with a non-ASCII character unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="1.0,2.0 é 3.0,4.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="1.0,2.0 &#xE9; 3.0,4.0"/></svg>',
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="1000000" y="-.0000001" width=".000001" height=".00000000001"/></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx=".0000000001" cy=".0000000001" r=".0000000001"/></svg>
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

        yield 'Optimizes viewBox coordinates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0.000000 0.000000 100.000000 100.000000">
                    <rect x="10.000000" y="20.000000" width="30.000000" height="40.000000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect x="10" y="20" width="30" height="40"/></svg>
                XML,
        ];

        yield 'Optimizes enable-background coordinates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" enable-background="new 0.000000 0.000000 200.000000 200.000000">
                    <circle cx="50.000000" cy="50.000000" r="25.000000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" enable-background="new 0 0 200 200"><circle cx="50" cy="50" r="25"/></svg>
                XML,
        ];

        yield 'Handles both viewBox and enable-background attributes together' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0.000000 0.000000 500.000000 500.000000" enable-background="new 0.000000 0.000000 500.000000 500.000000">
                    <path d="M10.000000 20.000000 L30.500000 40.500000"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 500 500" enable-background="new 0 0 500 500"><path d="M10 20 L30.5 40.5"/></svg>
                XML,
        ];

        yield 'Zero Coordinated Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="0.000" height="0.000"/>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="0" height="0"/>
                XML,
        ];

        yield 'Empty d attribute' => [
            '<svg><path d=""/></svg>',
            '<svg><path d=""/></svg>',
        ];

        yield 'Empty points attribute' => [
            '<svg><polyline points=""/></svg>',
            '<svg><polyline points=""/></svg>',
        ];

        yield 'Attributes with units are not affected' => [
            '<svg><rect x="10px" y="20pt" width="30%" height="40em"/></svg>',
            '<svg><rect x="10px" y="20pt" width="30%" height="40em"/></svg>',
        ];

        yield 'Consecutive decimals in path data stay separate numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.0.5L10 10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 .5L10 10"/></svg>',
        ];

        yield 'Zero with a decimal point before a comma becomes zero' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0.0,1L10 10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0,1L10 10"/></svg>',
        ];

        yield 'Negative zero with a decimal point becomes zero' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M-0.0 1L10 10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 1L10 10"/></svg>',
        ];

        yield 'Zero exponent followed by a decimal keeps the numbers apart' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M5e0.5L10 10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M5 .5L10 10"/></svg>',
        ];

        yield 'Three consecutive decimals in path data' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.5.5.50L.0.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.5.5.5L0 0"/></svg>',
        ];

        yield 'Explicit plus sign between numbers keeps a separator' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1+2L+3.0-4.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2L3-4"/></svg>',
        ];

        yield 'Exponents are shortened without merging numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.50E+02.5L1e-01 2.0e00"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.5e2.5L1e-1 2"/></svg>',
        ];

        yield 'Leading dot numbers are kept' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M.50.50L-.50-.50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M.5.5L-.5-.5"/></svg>',
        ];

        yield 'Trailing decimal point is removed' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1. 2.L3.,4."/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2L3,4"/></svg>',
        ];

        yield 'Compact arc flags are not merged into numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0a1 1 0 0110 10a1 1 0 1 0.50 1A2 2 0 1 1.0 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0a1 1 0 0110 10a1 1 0 1 0.5 1A2 2 0 1 10 0"/></svg>',
        ];

        yield 'Arc flags are tracked across repeated arc arguments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0a1.0 1.0 0.0 0 1 2.0 2.0 1.0 1.0 0.0 1 0.50 3.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0a1 1 0 0 1 2 2 1 1 0 1 0.5 3"/></svg>',
        ];

        yield 'Invalid path data is left unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.0 2.0 X 3.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1.0 2.0 X 3.0"/></svg>',
        ];

        yield 'Minifying that would join a stray dot leaves the value unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1.0."/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1.0."/></svg>',
        ];

        yield 'Points with zero decimals and commas' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0.0,0.0,10,0,10,10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0,0,10,0,10,10"/></svg>',
        ];

        yield 'Points with consecutive decimals stay separate numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="1.0.5 2.50.25"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="1 .5 2.5.25"/></svg>',
        ];

        yield 'viewBox with zero decimals and commas' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.0,0.0,10,10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0,0,10,10"/>',
        ];

        yield 'viewBox with negative zero and exponents' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-0.0 -0 1.0E1 10e-0"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1e1 10"/>',
        ];

        yield 'Lengths with units keep the unit' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="10.50px" y="0.50em" width="50.0%" height="1.0e1px"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="10.5px" y=".5em" width="50%" height="1e1px"/></svg>',
        ];

        yield 'Empty d attribute on namespaced path is left unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d=""/></svg>',
        ];
    }

    #[Test]
    public function ruleIsMarkedAsNotRisky(): void
    {
        self::assertFalse(MinifySvgCoordinates::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(MinifySvgCoordinates::shouldCheckSize());
    }
}
