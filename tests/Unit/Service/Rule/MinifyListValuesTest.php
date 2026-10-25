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
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyListValues;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifyListValues::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MinifyListValuesTest extends TestCase
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
        $svgOptimizer->addRule(new MinifyListValues());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Leaves the document untouched when a style sheet selects attribute values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[points="0, 0"]{fill:red}</style><polygon points="0, 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[points="0, 0"]{fill:red}</style><polygon points="0, 0"/></svg>',
        ];

        yield 'Keeps a list with a trailing decimal point, which browsers reject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0 0 400. 400"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0 0 400. 400"/></svg>',
        ];

        yield 'Shortens viewBox' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.0, 0.0, 100.0, 50.50"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 50.5"/>',
        ];

        yield 'Shortens a list followed by an attribute sharing its local name' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><polyline points="0.0, 0.0, 10.0, 10.0" a:points="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><polyline points="0 0 10 10" a:points="1"/></svg>',
        ];

        yield 'Keeps a space before negative numbers outside points' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-10.0,-10.0,20.0,20.0"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-10 -10 20 20"/>',
        ];

        yield 'Keeps a space before fractions outside points' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke-dasharray="0.5, 0.25, 1.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke-dasharray=".5 .25 1.5"/></svg>',
        ];

        yield 'Omits separators in points where the coordinates stay distinct' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="0.5,-1.0 1.5,0.25 10,0.5 1e2,0.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points=".5-1 1.5.25 10 .5 1e2.5"/></svg>',
        ];

        yield 'Shortens polyline points' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="10, 20, 30, 40"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="10 20 30 40"/></svg>',
        ];

        yield 'Never rounds' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0.123456789012345678901 1.000000000000000000001"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 .123456789012345678901 1.000000000000000000001"/>',
        ];

        yield 'Shortens exponents and signs' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="+0 -0.0 1.50E+02 2E-03"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1.5e2 2e-3"/>',
        ];

        yield 'Shortens filter number lists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter filterRes="10.0 20.0"><feTurbulence baseFrequency="0.050, 0.10"/><feGaussianBlur stdDeviation="2.0, 3.0"/><feConvolveMatrix order="3, 3" kernelMatrix="1.0 0 0 0 1.0 0 0 0 1.0" kernelUnitLength="1.0 1.0"/><feMorphology radius="1.0, 2.0"/><feFuncR tableValues="0.0 0.50 1.0"/></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter filterRes="10 20"><feTurbulence baseFrequency=".05 .1"/><feGaussianBlur stdDeviation="2 3"/><feConvolveMatrix order="3 3" kernelMatrix="1 0 0 0 1 0 0 0 1" kernelUnitLength="1 1"/><feMorphology radius="1 2"/><feFuncR tableValues="0 .5 1"/></filter></svg>',
        ];

        yield 'Shortens single numbers of list attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><feGaussianBlur stdDeviation="2.0"/><path stroke-dasharray="5.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><feGaussianBlur stdDeviation="2"/><path stroke-dasharray="5"/></svg>',
        ];

        yield 'Shortens color matrix values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><feColorMatrix values="1.0 0 0 0 0, 0 1.0 0 0 0, 0 0 1.0 0 0, 0 0 0 1.0 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><feColorMatrix values="1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 1 0"/></svg>',
        ];

        yield 'Keeps semicolons in color matrix values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><feColorMatrix values="1.0;0.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><feColorMatrix values="1.0;0.0"/></svg>',
        ];

        yield 'Shortens animation timing lists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="opacity" values="0.0; 0.50; 1.0" keyTimes="0.0; 0.50; 1.0" keySplines="0.5, 0.0, 0.5, 1.0; 0.0 0.0 1.0 1.0"/><animateMotion keyPoints="0.0; 1.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="opacity" values="0;.5;1" keyTimes="0;.5;1" keySplines=".5 0 .5 1;0 0 1 1"/><animateMotion keyPoints="0;1"/></svg>',
        ];

        yield 'Shortens transform and motion animation values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><animateTransform type="rotate" values="0.0 50.0 50.0; 360.0 50.0 50.0"/><animateMotion values="0.0, 0.0; 10.0, 10.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animateTransform type="rotate" values="0 50 50;360 50 50"/><animateMotion values="0 0;10 10"/></svg>',
        ];

        yield 'Keeps animation values of attributes that are not numeric' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="class" values="01; 02"/><animate attributeName="fill" values="0;1"/><animate values="1.0; 2.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="class" values="01; 02"/><animate attributeName="fill" values="0;1"/><animate values="1.0; 2.0"/></svg>',
        ];

        yield 'Keeps animation values holding anything other than numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="width" values="10.0px; 20.0"/><animate attributeName="x" values="0.0; 50%"/><animate attributeName="d" values="M0 0L1 1; M0 0L2 2"/><animate attributeName="opacity" values="0.0; inherit"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="width" values="10.0px; 20.0"/><animate attributeName="x" values="0.0; 50%"/><animate attributeName="d" values="M0 0L1 1; M0 0L2 2"/><animate attributeName="opacity" values="0.0; inherit"/></svg>',
        ];

        yield 'Keeps values with an empty part' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="opacity" values="0.0; 1.0;" keyTimes="0.0;;1.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="opacity" values="0.0; 1.0;" keyTimes="0.0;;1.0"/></svg>',
        ];

        yield 'Keeps values of other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><set values="1.0 2.0"/><g values="1.0 2.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><set values="1.0 2.0"/><g values="1.0 2.0"/></svg>',
        ];

        yield 'Keeps semicolons only in semicolon lists' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.0 0.0;10.0 10.0"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.0 0.0;10.0 10.0"/>',
        ];

        yield 'Shortens text position lists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1.0 2.0" y="3.0, 4.0" dx="0.5 -0.5" dy="0 1.0" rotate="0.0 45.0">ab<tspan x="1.0 2.0" rotate="90.0, 0">ab</tspan></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1 2" y="3 4" dx=".5 -.5" dy="0 1" rotate="0 45">ab<tspan x="1 2" rotate="90 0">ab</tspan></text></svg>',
        ];

        yield 'Leaves single text positions to MinifyNumericValues' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1.0" y="2.0" dx="0.5" dy="0.5" rotate="45.0">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text x="1.0" y="2.0" dx="0.5" dy="0.5" rotate="45">a</text></svg>',
        ];

        yield 'Keeps text attributes on other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><feOffset dx="1.0 2.0"/><rect x="1.0 2.0"/><animateMotion rotate="45.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><feOffset dx="1.0 2.0"/><rect x="1.0 2.0"/><animateMotion rotate="45.0"/></svg>',
        ];

        yield 'Keeps values with units, percentages and keywords' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke-dasharray="5.0px, 10.0"/><path stroke-dasharray="5.0%, 10.0"/><path stroke-dasharray="none"/><path stroke-dasharray="inherit"/><text x="1.0em 2.0">ab</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke-dasharray="5.0px, 10.0"/><path stroke-dasharray="5.0%, 10.0"/><path stroke-dasharray="none"/><path stroke-dasharray="inherit"/><text x="1.0em 2.0">ab</text></svg>',
        ];

        yield 'Keeps malformed lists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="1.0,,2.0"/><polygon points=",1.0 2.0"/><polygon points="1.0 2.0,"/><polygon points="1.0-2.0"/><polygon points="1.5.5 2.0"/><polygon points="1e 2.0"/><polygon points=". 2.0"/><polygon points=""/><polygon points="  "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polygon points="1.0,,2.0"/><polygon points=",1.0 2.0"/><polygon points="1.0 2.0,"/><polygon points="1.0-2.0"/><polygon points="1.5.5 2.0"/><polygon points="1e 2.0"/><polygon points=". 2.0"/><polygon points=""/><polygon points="  "/></svg>',
        ];

        yield 'Keeps values that are not shorter' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0,0,10,10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0,0,10,10"/>',
        ];

        yield 'Trims surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="  0   0   10   10  "/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"/>',
        ];

        yield 'Keeps unlisted and namespaced attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g data-points="1.0 2.0" class="1.0 2.0" x:viewBox="0.0 0.0 1.0 1.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><g data-points="1.0 2.0" class="1.0 2.0" x:viewBox="0.0 0.0 1.0 1.0"/></svg>',
        ];

        yield 'Keeps lists of elements in other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:polyline xmlns:x="urn:x" points="1.0 2.0"/><foreignObject width="1" height="1"><div xmlns="http://www.w3.org/1999/xhtml" viewBox="0.0 0.0 1.0 1.0"/></foreignObject><polyline points="1.0 2.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:polyline xmlns:x="urn:x" points="1.0 2.0"/><foreignObject width="1" height="1"><div xmlns="http://www.w3.org/1999/xhtml" viewBox="0.0 0.0 1.0 1.0"></div></foreignObject><polyline points="1 2"/></svg>',
        ];

        yield 'Shortens lists in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" viewBox="0.0 0 10.0 10"><svg:text x="1.0 2.0"><svg:tspan dy="0.50 1.0">a</svg:tspan></svg:text></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><svg:text x="1 2"><svg:tspan dy=".5 1">a</svg:tspan></svg:text></svg:svg>',
        ];

        yield 'Shortens lists in a document without a namespace' => [
            '<svg viewBox="0.0 0 10.0 10"/>',
            '<svg viewBox="0 0 10 10"/>',
        ];

        yield 'Shortens animated viewBox values and padded key times' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="viewBox" values="0 0 10.0 10;0 0 20.0 20" keyTimes="0; 0.50; 1" keySplines="0.0,0.0,1.0,1.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="viewBox" values="0 0 10 10;0 0 20 20" keyTimes="0;.5;1" keySplines="0 0 1 1"/></svg>',
        ];

        yield 'Never rounds huge and tiny list values' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0.000 0.00000000000000000001 123456789012345678901234567890.0 1E+02"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 .00000000000000000001 123456789012345678901234567890 1e2"/>',
        ];

        yield 'Shortens dash arrays separated by commas' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke-dasharray="5.0, 10.0"/><path stroke-dasharray="5.0,"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke-dasharray="5 10"/><path stroke-dasharray="5.0,"/></svg>',
        ];

        yield 'Keeps lists in style attributes and style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path{stroke-dasharray:5.0 10.0}</style><path style="stroke-dasharray:5.0 10.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path{stroke-dasharray:5.0 10.0}</style><path style="stroke-dasharray:5.0 10.0"/></svg>',
        ];

        yield 'Keeps lists with non-ASCII digits' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 １0.0 10.0"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 １0.0 10.0"/>',
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
        $svgOptimizer->addRule(new MinifyListValues());

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

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(MinifyListValues::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(MinifyListValues::shouldCheckSize());
    }
}
