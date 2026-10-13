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
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyNumericValues;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifyNumericValues::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MinifyNumericValuesTest extends TestCase
{
    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new MinifyNumericValues());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Shortens lengths and drops px' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="+10.0" y="0.50" width="01.50px" height="2PX" stroke-width="1.0px"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y=".5" width="1.5" height="2PX" stroke-width="1"/></svg>',
        ];

        yield 'Shortens a length followed by an attribute sharing its local name' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect x="10.0" a:x="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect x="10" a:x="1"/></svg>',
        ];

        yield 'Shortens unitless numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0.50" fill-opacity="1.0" stroke-miterlimit="04"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".5" fill-opacity="1" stroke-miterlimit="4"/></svg>',
        ];

        yield 'Keeps px on unitless numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="1px"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="1px"/></svg>',
        ];

        yield 'Keeps percentages and other units' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="50%" height="1em" x="1in"/><stop offset="50.0%"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="50%" height="1em" x="1in"/><stop offset="50.0%"/></svg>',
        ];

        yield 'Keeps lists and keywords' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1.0 2.0" letter-spacing="normal">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1.0 2.0" letter-spacing="normal">a</text></svg>',
        ];

        yield 'Keeps unlisted attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g data-x="1.0" class="0.50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g data-x="1.0" class="0.50"/></svg>',
        ];

        yield 'Never rounds' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="0.123456789012345678901"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity=".123456789012345678901"/></svg>',
        ];

        yield 'Writes zero as 0' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="-0.0" y="00"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0"/></svg>',
        ];

        yield 'Keeps negative values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-dashoffset="-01.50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke-dashoffset="-1.5"/></svg>',
        ];

        yield 'Trims surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x=" 1.0 " opacity=" 0.5 "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1" opacity=".5"/></svg>',
        ];

        yield 'Shortens exponents' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1.50E+02"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1.5e2"/></svg>',
        ];

        yield 'Keeps namespaced attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g x:opacity="0.50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g x:opacity="0.50"/></svg>',
        ];

        yield 'Keeps px on offsets of filter primitives, where they are numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feOffset dx="5px" dy="2px"/><feDropShadow dx="5px" dy="2px"/></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feOffset dx="5px" dy="2px"/><feDropShadow dx="5px" dy="2px"/></filter></svg>',
        ];

        yield 'Shortens offsets of filter primitives without px' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feOffset dx="5.0" dy="+02"/><feDropShadow dx="-0.50" dy="1e0"/></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feOffset dx="5" dy="2"/><feDropShadow dx="-.5" dy="1"/></filter></svg>',
        ];

        yield 'Keeps px on light source positions, where they are numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feDiffuseLighting><fePointLight x="5px" y="2px" z="3px"/></feDiffuseLighting><feSpecularLighting><feSpotLight x="5px" y="2px" z="3px" pointsAtX="1px" pointsAtY="1px" pointsAtZ="1px"/></feSpecularLighting></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feDiffuseLighting><fePointLight x="5px" y="2px" z="3px"/></feDiffuseLighting><feSpecularLighting><feSpotLight x="5px" y="2px" z="3px" pointsAtX="1px" pointsAtY="1px" pointsAtZ="1px"/></feSpecularLighting></filter></svg>',
        ];

        yield 'Shortens light source positions without px' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feDiffuseLighting><fePointLight x="5.0" y="0.50" z="03"/></feDiffuseLighting></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feDiffuseLighting><fePointLight x="5" y=".5" z="3"/></feDiffuseLighting></filter></svg>',
        ];

        yield 'Drops px from filter primitive subregions and text offsets, where they are lengths' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feOffset x="5px" y="2px" width="10px" height="10px"/></filter><text dx="5px" dy="2px">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feOffset x="5" y="2" width="10" height="10"/></filter><text dx="5" dy="2">a</text></svg>',
        ];

        yield 'Keeps integer attributes written with a decimal point or exponent' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feTurbulence numOctaves="2.0"/><feTurbulence numOctaves="1e1"/><feTurbulence numOctaves="+2"/><feTurbulence numOctaves="2px"/></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feTurbulence numOctaves="2.0"/><feTurbulence numOctaves="1e1"/><feTurbulence numOctaves="+2"/><feTurbulence numOctaves="2px"/></filter></svg>',
        ];

        yield 'Shortens integer attributes written as plain integers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feTurbulence numOctaves=" 02 "/></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter id="f"><feTurbulence numOctaves="2"/></filter></svg>',
        ];

        yield 'Keeps values that are not numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="."/><rect x="1e"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g opacity="."/><rect x="1e"/></svg>',
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(MinifyNumericValues::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(MinifyNumericValues::shouldCheckSize());
    }
}
