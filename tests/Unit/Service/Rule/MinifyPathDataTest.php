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
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataParserTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataSerializerTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\PathDataTokenTrait;
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
#[CoversClass(MinifyPathData::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(NumberFormatterTrait::class)]
#[CoversTrait(PathDataParserTrait::class)]
#[CoversTrait(PathDataSerializerTrait::class)]
#[CoversTrait(PathDataTokenTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
final class MinifyPathDataTest extends TestCase
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
        $svgOptimizer->addPass(new MinifyPathData());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Leaves the document untouched when a style sheet selects attribute values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[d="M 0 0 L 1 0"]{fill:red}</style><path d="M 0 0 L 1 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[d="M 0 0 L 1 0"]{fill:red}</style><path d="M 0 0 L 1 0"/></svg>',
        ];

        yield 'Minifies the commands before a number with a trailing decimal point and keeps the rest' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 L 400. 400"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L 400. 400"/></svg>',
        ];

        yield 'Drops repeated command letters and separators' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 10.50 20.0 L 30 40 L 50 60 Z"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M10.5 20 30 40 50 60z"/></svg>',
        ];

        yield 'Minifies the commands before a separator after closepath and keeps the separator' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 z,"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0z,"/></svg>',
        ];

        yield 'Writes relative linetos along one axis as h and v' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0,0 l 10,0 l 0,-5 l 3 3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h10v-5l3 3"/></svg>',
        ];

        yield 'Keeps a zero-length relative lineto' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 l 0 0 L 5 5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0l0 0L5 5"/></svg>',
        ];

        yield 'Keeps an absolute lineto along one axis' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 L 10 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 10 0"/></svg>',
        ];

        yield 'Omits separators before negative and decimal numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0.5 0.5 L -0.5 -0.5 0.25 .5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M.5.5-.5-.5.25.5"/></svg>',
        ];

        yield 'Keeps a separator before a decimal after an integer' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 0.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 .5"/></svg>',
        ];

        yield 'Writes the arguments after arc flags without separators' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 A 10 10 0 1 0 0.5 0.5 a1 1 0 11 2 2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0A10 10 0 10.5.5a1 1 0 112 2"/></svg>',
        ];

        yield 'Shortens exponents without changing the value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1e+05 1.50E-3 h +1 v 2e0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1e5 1.5e-3h1v2"/></svg>',
        ];

        yield 'Omits the separator before a decimal after an exponent' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1e5 0.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1e5.5"/></svg>',
        ];

        yield 'Keeps a moveto letter after a moveto' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="m1 2 3 4 M 5 6 7 8"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="m1 2 3 4M5 6 7 8"/></svg>',
        ];

        yield 'Keeps every close path' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 z z m 1 1 Z"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0zzm1 1z"/></svg>',
        ];

        yield 'Keeps curve commands' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 C 1 1 2 2 3 3 S 4 4 5 5 Q 6 6 7 7 T 8 8"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0C1 1 2 2 3 3S4 4 5 5Q6 6 7 7T8 8"/></svg>',
        ];

        yield 'Never rounds' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0.123456789012345678901 1.000000000000000000001"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M.123456789012345678901 1.000000000000000000001"/></svg>',
        ];

        yield 'Minifies the commands before numbers after a close path and keeps the numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 z 3 4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2z 3 4"/></svg>',
        ];

        yield 'Leaves data not starting with a moveto untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="L 1 2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="L 1 2"/></svg>',
        ];

        yield 'Leaves data with a trailing comma untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2,"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2,"/></svg>',
        ];

        yield 'Leaves data with a missing argument untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1"/></svg>',
        ];

        yield 'Minifies the commands before an invalid arc flag and keeps the arc command' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 a 1 1 0 2 0 1 1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0a 1 1 0 2 0 1 1"/></svg>',
        ];

        yield 'Leaves non-ASCII data untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 L 3 4 é"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 L 3 4 é"/></svg>',
        ];

        yield 'Leaves already minimal data unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2h3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2h3"/></svg>',
        ];

        yield 'Leaves empty data unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d=""/></svg>',
        ];

        yield 'Ignores d on other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><glyph d="M 0 0 L 1 1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><glyph d="M 0 0 L 1 1"/></svg>',
        ];

        yield 'Trims surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="  M 1 2 L 3 4  "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2 3 4"/></svg>',
        ];

        yield 'Accepts commas between argument groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1, 2, 3, 4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2 3 4"/></svg>',
        ];

        yield 'Keeps data when minifying would not make it shorter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1,2,3,4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1,2,3,4"/></svg>',
        ];

        yield 'Splits numbers on signs and decimal points' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 -2 L .5 .5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1-2 .5.5"/></svg>',
        ];

        yield 'Keeps relative and absolute commands distinct' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 l 1 1 L 2 2 l 3 3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0l1 1L2 2l3 3"/></svg>',
        ];

        yield 'Keeps every vertical and horizontal lineto' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 H 10 V 10 h -10 v -10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0H10V10h-10v-10"/></svg>',
        ];

        yield 'Leaves data with a leading comma untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M ,1 2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M ,1 2"/></svg>',
        ];

        yield 'Minifies the commands before a close path argument and keeps the argument' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 z 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2z 0"/></svg>',
        ];

        yield 'Leaves data with a double comma untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1,,2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1,,2"/></svg>',
        ];

        yield 'Accepts whitespace around commas' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 , 2 L 3 ,4 , 5 , 6"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2 3 4 5 6"/></svg>',
        ];

        yield 'Leaves data with text before the first command untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="x M 1 2 L 3 4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="x M 1 2 L 3 4"/></svg>',
        ];

        yield 'Minifies each path independently' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 L 3 4"/><path d="L 1 2"/><path d="M 0.0 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 2 3 4"/><path d="L 1 2"/><path d="M0 0"/></svg>',
        ];

        yield 'Minifies path data in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:path d="M 1.0 2.0 L 3 4"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:path d="M1 2 3 4"/></svg:svg>',
        ];

        yield 'Writes repeated moveto pairs as implicit linetos' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 10 0 10 10 m 1 1 2 0 2 2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 10 0 10 10m1 1h2l2 2"/></svg>',
        ];

        yield 'Writes arc flags without separators' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 a 1 1 0 01 1.0 1.0 1 1 0 1 0 2 2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0a1 1 0 011 1 1 1 0 102 2"/></svg>',
        ];

        yield 'Accepts an exponent followed by a sign' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1e2-1E+02 L 1.5e-1-.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1e2-1e2 1.5e-1-.5"/></svg>',
        ];

        yield 'Never rounds huge and tiny values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 123456789012345678901234567890.0 0.00000000000000000001 L 1E-07 0.1234567890123456789"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M123456789012345678901234567890 .00000000000000000001 1e-7.1234567890123456789"/></svg>',
        ];

        yield 'Leaves path data with an incomplete exponent untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1e 2 L 3 4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1e 2 L 3 4"/></svg>',
        ];

        yield 'Leaves path data with double signs untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M --1 2 L 3 4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M --1 2 L 3 4"/></svg>',
        ];

        yield 'Leaves the document untouched when path data is animated, as interpolation needs matching commands' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 L 1 1"><animate attributeName="d" values="M 0 0 L 1 1;M 0 0 L 2 2"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 L 1 1"><animate attributeName="d" values="M 0 0 L 1 1;M 0 0 L 2 2"/></path></svg>',
        ];

        yield 'Writes negative zero relative linetos as h and v' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 l -0 5 l 5 -0.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0v5h5"/></svg>',
        ];

        yield 'Minifies the commands before an incomplete argument group and the numbers after it' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 10.0,10.0 L 90.0,10.0 L 90.0,0.0 C 10 20 30 40 50 60 70 80 90 95 5 L 0.50 0.50"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M10 10 90 10 90 0C 10 20 30 40 50 60 70 80 90 95 5 L .5 .5"/></svg>',
        ];

        yield 'Minifies the numbers of path data that starts with an invalid command' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="L 10.0,10.0 20.0,20.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="L 10,10 20,20"/></svg>',
        ];

        yield 'Minifies the numbers after an invalid command and keeps a separator that a number would join' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 2 3 Z 0.50 0.50 a 1 1 0 0 1 0.5 0.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 2 3 Z .5 .5 a 1 1 0 0 1 .5 .5"/></svg>',
        ];

        yield 'Keeps invalid path data with a character that is no path token' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 1 # 2.0 2.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 1 # 2.0 2.0"/></svg>',
        ];

        yield 'Keeps invalid path data with a trailing decimal point' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="L 1. 2.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="L 1. 2.0"/></svg>',
        ];

        yield 'Keeps path data with non-ASCII characters' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0.50 0.50 Ｌ 1 1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0.50 0.50 Ｌ 1 1"/></svg>',
        ];

        yield 'Writes repeated arc arguments without separators after the flags' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0a1 1 0 1 1 10 10 1 1 0 0 0 -5 5A 2 2 0 0 1 .5 .5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0a1 1 0 1110 10 1 1 0 00-5 5A2 2 0 01.5.5"/></svg>',
        ];

        yield 'Separates numbers after an invalid command that would join once minified' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 1 1 L1.0.5 2.50.5 1e0.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 1 1 L1 .5 2.5.5 1 .5"/></svg>',
        ];

        yield 'Keeps arc flags after an invalid command apart from the numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 1 1 a1 1 0 0110.0 10.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 1 1 a1 1 0 0110 10"/></svg>',
        ];
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeIsIdempotentCases')]
    #[Test]
    public function optimizeIsIdempotent(string $optimized): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($optimized));
        $svgOptimizer->addPass(new MinifyPathData());

        self::assertSame($optimized, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideOptimizeIsIdempotentCases(): iterable
    {
        foreach (self::provideOptimizeCases() as $name => [, $expected]) {
            yield $name => [$expected];
        }
    }
}
