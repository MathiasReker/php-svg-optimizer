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
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyPathData;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifyPathData::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class MinifyPathDataTest extends TestCase
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
        $svgOptimizer->addRule(new MinifyPathData());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Drops repeated command letters and separators' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 10.50 20.0 L 30 40 L 50 60 Z"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M10.5 20 30 40 50 60z"/></svg>',
        ];

        yield 'Keeps path data with a separator after closepath' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 z,"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 z,"/></svg>',
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

        yield 'Keeps arc flags separated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0 A 10 10 0 1 0 0.5 0.5 a1 1 0 11 2 2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0A10 10 0 1 0 .5.5a1 1 0 1 1 2 2"/></svg>',
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

        yield 'Leaves numbers after a close path untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 z 3 4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 z 3 4"/></svg>',
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

        yield 'Leaves data with an invalid arc flag untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 a 1 1 0 2 0 1 1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 0 0 a 1 1 0 2 0 1 1"/></svg>',
        ];

        yield 'Leaves non-ASCII data untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 L 3 4 é"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 L 3 4 &#xE9;"/></svg>',
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

        yield 'Leaves data with a close path argument untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 z 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M 1 2 z 0"/></svg>',
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
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(MinifyPathData::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(MinifyPathData::shouldCheckSize());
    }
}
