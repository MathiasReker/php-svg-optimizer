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
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyTransformations;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifyTransformations::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MinifyTransformationsTest extends TestCase
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
        $svgOptimizer->addPass(new MinifyTransformations());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Leaves the document untouched when a style sheet selects attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[transform]{fill:red}</style><rect transform="translate(0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[transform]{fill:red}</style><rect transform="translate(0)"/></svg>',
        ];

        yield 'Leaves the document untouched with an external style sheet' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect transform="translate(0)"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect transform="translate(0)"/></svg>',
        ];

        yield 'Keeps a transform with a trailing decimal point' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect transform="translate(200.)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect transform="translate(200.)"/></svg>',
        ];

        yield 'Removes Identity Transforms' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0) scale(1,1) rotate(0) skewX(0) skewY(0)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Shortens Non-Identity Transforms' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10,20) scale(2,2)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10 20)scale(2)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Handles Mixed Identity and Non-Identity Transforms' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0) scale(1,1) translate(10,20)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10 20)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Removes Empty Transform Attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Removes Empty Transform Attribute when 0' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Removes Multiple Identity Transform Steps' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0) scale(1,1) rotate(0) skewX(0)">
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Handles Complex Transformations' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10,10) rotate(45) scale(0.5)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10 10)rotate(45)scale(.5)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Retains Nested Transformations' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10,10)">
                        <g transform="rotate(30)">
                            <rect x="10" y="10" width="30" height="30"/>
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,10)"><g transform="rotate(30)"><rect x="10" y="10" width="30" height="30"/></g></g></svg>
                XML,
        ];

        yield 'Keeps Transformations with Percentages' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="scale(50%,50%)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="scale(50%,50%)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Handles Multiple Transformations' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10,20) scale(2,0.5) rotate(45) skewX(10) skewY(20)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10 20)scale(2 .5)rotate(45)skewX(10)skewY(20)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Handles Nested Identity Transformations' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0)">
                        <g transform="scale(1,1)">
                            <circle cx="50" cy="50" r="20"/>
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><g><circle cx="50" cy="50" r="20"/></g></g></svg>
                XML,
        ];

        yield 'Keeps Complex Transformations with Percentages and Units' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10%,20%) scale(50%,50%) rotate(45deg)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10%,20%) scale(50%,50%) rotate(45deg)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Keeps Single Transformations with Percentages' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="scale(75%)">
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="scale(75%)"><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Keeps Transformations with Decimal Percentages' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(12.5%,10%)">
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(12.5%,10%)"><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Handles Mixed Identity Transformations with Non-Identity' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0) scale(1,1) rotate(45)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="rotate(45)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Handles Whitespaces Between Transform Functions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="  translate( 0 , 0 )    scale(  1 , 1 )  ">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Handles Scientific Notation as Identity' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0e0,0e0) scale(1e0,1e0)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Drops Zero Center from Rotation' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="rotate(90 0 0)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="rotate(90)"><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Removes Identity Matrix' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(1 0 0 1 0 0)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Removes Comma-Separated Identity Matrix' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(1,0,0,1,0,0)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Removes Comma-Separated Identity Matrix With Spaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(1, 0, 0, 1, 0, 0)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Writes Comma-Separated Translation Matrix as Translate' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(1,0,0,1,10,20)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10 20)"><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Writes Translation Matrix as Translate' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(1 0 0 1 10 20)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10 20)"><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Writes Degenerate Matrix with Zeros as Scale' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(0 0 0 0 0 0)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="scale(0)"><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Handles Missing Transform Attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Keeps Transform Attribute with Semicolons' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0); scale(1,1);">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(0,0); scale(1,1);"><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Shortens numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(010.50, -0.50)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10.5-.5)"/></svg>',
        ];

        yield 'Never rounds long decimals' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0.123456789012345678901, 1.000000000000000000001)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(.123456789012345678901 1.000000000000000000001)"/></svg>',
        ];

        yield 'Never treats a near-one scale as identity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(1.0000000000000000001, 1.0000000000000000001)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(1.0000000000000000001)"/></svg>',
        ];

        yield 'Never treats a near-zero translation as identity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0.0000000000000000001, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(.0000000000000000001)"/></svg>',
        ];

        yield 'Never treats a near-zero rotation as identity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(0.0000000000000000001)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(.0000000000000000001)"/></svg>',
        ];

        yield 'Never treats a near-zero skew as identity' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="skewX(-0.0000000000000000001)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="skewX(-.0000000000000000001)"/></svg>',
        ];

        yield 'Shortens exponents' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1.50E+02, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1.5e2)"/></svg>',
        ];

        yield 'Omits separator before a negative number' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1, -2)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1-2)"/></svg>',
        ];

        yield 'Omits separator before a fraction after a fraction' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45.5, 0.5, 0.5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45.5.5.5)"/></svg>',
        ];

        yield 'Omits separator before a fraction after an exponent' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1e2, 0.5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1e2.5)"/></svg>',
        ];

        yield 'Keeps separator before a fraction after an integer' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45, 0.5, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45 .5 1)"/></svg>',
        ];

        yield 'Drops zero vertical translation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5)"/></svg>',
        ];

        yield 'Keeps zero horizontal translation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0, 5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0 5)"/></svg>',
        ];

        yield 'Removes negative zero translation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(-0, -0.0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Collapses uniform scale' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2, 2.0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"/></svg>',
        ];

        yield 'Collapses negative uniform scale' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(-1, -1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(-1)"/></svg>',
        ];

        yield 'Keeps non-uniform scale' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2, 3)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2 3)"/></svg>',
        ];

        yield 'Keeps scale with different notations of equal values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(1e1, 10)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(1e1 10)"/></svg>',
        ];

        yield 'Keeps scale of one and two' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(1, 2)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(1 2)"/></svg>',
        ];

        yield 'Keeps scale of two and one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2 1)"/></svg>',
        ];

        yield 'Removes identity scale written with decimals' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(1.0, 01)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Removes identity gradientTransform without a reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="a" gradientTransform="translate(0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="a"/></svg>',
        ];

        yield 'Keeps identity gradientTransform on a gradient with href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="a" gradientTransform="rotate(45)"/><linearGradient id="b" href="#a" gradientTransform="translate(0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="a" gradientTransform="rotate(45)"/><linearGradient id="b" href="#a" gradientTransform="scale(1)"/></svg>',
        ];

        yield 'Keeps identity gradientTransform on a gradient with xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><radialGradient id="b" xlink:href="#a" gradientTransform="matrix(1 0 0 1 0 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><radialGradient id="b" xlink:href="#a" gradientTransform="scale(1)"/></svg>',
        ];

        yield 'Keeps short identity patternTransform on a pattern with href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><pattern id="b" href="#a" patternTransform="scale(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><pattern id="b" href="#a" patternTransform="scale(1)"/></svg>',
        ];

        yield 'Keeps identity patternTransform on a pattern with a custom xlink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"><pattern id="b" x:href="#a" patternTransform="rotate(0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"><pattern id="b" x:href="#a" patternTransform="scale(1)"/></svg>',
        ];

        yield 'Removes identity transform on an element with href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a" transform="translate(0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><use href="#a"/></svg>',
        ];

        yield 'Keeps negative scale' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(-1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(-1)"/></svg>',
        ];

        yield 'Removes zero rotation around a center' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(0, 10, 10)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Removes negative zero rotation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(-0.0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Keeps rotation center with a zero coordinate' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45, 0, 5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45 0 5)"/></svg>',
        ];

        yield 'Keeps rotation center with a zero vertical coordinate' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45, 5, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45 5 0)"/></svg>',
        ];

        yield 'Drops zero rotation center written with decimals' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45, 0.0, -0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(45)"/></svg>',
        ];

        yield 'Removes zero skews' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="skewX(0.0) skewY(-0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Keeps non-zero skews' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="skewX(0.5) skewY(-0.5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="skewX(.5)skewY(-.5)"/></svg>',
        ];

        yield 'Writes scaling matrix as scale' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(2, 0, 0, 3, 0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2 3)"/></svg>',
        ];

        yield 'Writes uniform scaling matrix as scale' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(2, 0, 0, 2.0, 0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"/></svg>',
        ];

        yield 'Writes horizontal translation matrix as translate' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0, 1, 5, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5)"/></svg>',
        ];

        yield 'Writes vertical translation matrix as translate' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0, 1, 0, 5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0 5)"/></svg>',
        ];

        yield 'Keeps skewing matrix' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0.5, 0, 1, 0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1 .5 0 1 0 0)"/></svg>',
        ];

        yield 'Keeps matrix skewing the other axis' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0.5, 1, 0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1 0 .5 1 0 0)"/></svg>',
        ];

        yield 'Keeps matrix that scales and translates horizontally' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(2, 0, 0, 1, 3, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(2 0 0 1 3 0)"/></svg>',
        ];

        yield 'Keeps matrix that scales and translates vertically' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(2, 0, 0, 3, 0, 5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(2 0 0 3 0 5)"/></svg>',
        ];

        yield 'Keeps matrix with vertical scale and translation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0, 2, 3, 4)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1 0 0 2 3 4)"/></svg>',
        ];

        yield 'Never merges transforms' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1, 2) translate(3, 4)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1 2)translate(3 4)"/></svg>',
        ];

        yield 'Removes separators between transforms' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1) , scale(2)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1)scale(2)"/></svg>',
        ];

        yield 'Removes comma between transforms' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1),scale(2)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1)scale(2)"/></svg>',
        ];

        yield 'Allows whitespace before the parenthesis' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate (5, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(5)"/></svg>',
        ];

        yield 'Keeps value that is not shorter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1,2)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1,2)"/></svg>',
        ];

        yield 'Keeps already minified value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(5)"/></svg>',
        ];

        yield 'Keeps translate with too many arguments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0, 0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0, 0, 0)"/></svg>',
        ];

        yield 'Keeps rotate with two arguments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(0, 0)"/></svg>',
        ];

        yield 'Keeps matrix with five arguments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0, 1, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1, 0, 0, 1, 0)"/></svg>',
        ];

        yield 'Keeps skew with two arguments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="skewX(0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="skewX(0, 0)"/></svg>',
        ];

        yield 'Keeps transform without arguments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate() scale(1, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate() scale(1, 1)"/></svg>',
        ];

        yield 'Keeps unknown transform functions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translateX(0) scale(1, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translateX(0) scale(1, 1)"/></svg>',
        ];

        yield 'Keeps transform functions with wrong case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="Translate(0) scale(1, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="Translate(0) scale(1, 1)"/></svg>',
        ];

        yield 'Keeps transforms with units' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10px, 0) scale(1, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(10px, 0) scale(1, 1)"/></svg>',
        ];

        yield 'Keeps unclosed transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0, 0) scale(1, 1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0, 0) scale(1, 1"/></svg>',
        ];

        yield 'Keeps transform with double comma' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1,,2) scale(1, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1,,2) scale(1, 1)"/></svg>',
        ];

        yield 'Keeps transforms separated by two commas' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1),,scale(1, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1),,scale(1, 1)"/></svg>',
        ];

        yield 'Keeps transform list with a leading comma' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform=", translate(0, 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform=", translate(0, 0)"/></svg>',
        ];

        yield 'Keeps transform with trailing text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0, 0) x"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(0, 0) x"/></svg>',
        ];

        yield 'Keeps numbers without separators' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1.5.5) scale(1, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(1.5.5) scale(1, 1)"/></svg>',
        ];

        yield 'Keeps empty transform' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform=""/></svg>',
        ];

        yield 'Minifies gradient and pattern transforms' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient gradientTransform="translate(5, 0) rotate(0)"/><pattern patternTransform="scale(2, 2)"/><radialGradient gradientTransform="scale(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient gradientTransform="translate(5)"/><pattern patternTransform="scale(2)"/><radialGradient/></svg>',
        ];

        yield 'Keeps other attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g data-transform="translate(5, 0)" transform-origin="0 0" class="scale(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g data-transform="translate(5, 0)" transform-origin="0 0" class="scale(1)"/></svg>',
        ];

        yield 'Minifies transforms in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g transform="translate(10.0, 0)"/><svg:g transform="scale(1)"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g transform="translate(10)"/><svg:g/></svg:svg>',
        ];

        yield 'Keeps transforms in style attributes and animations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="transform:translate(0px)" transform="translate(1.0)"><animateTransform attributeName="transform" type="translate" from="0.0" to="10.0"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="transform:translate(0px)" transform="translate(1)"><animateTransform attributeName="transform" type="translate" from="0.0" to="10.0"/></g></svg>',
        ];

        yield 'Keeps rotate with one or three arguments only' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(10.0)"/><g transform="rotate(10.0 1.0 2.0)"/><g transform="rotate(10 1 2 3)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="rotate(10)"/><g transform="rotate(10 1 2)"/><g transform="rotate(10 1 2 3)"/></svg>',
        ];

        yield 'Minifies transforms with newlines and tabs between arguments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="matrix(2&#10;0&#9;0 2 0 0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(2)"/></svg>',
        ];

        yield 'Keeps transforms with non-ASCII digits' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(１0)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g transform="translate(１0)"/></svg>',
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
        $svgOptimizer->addPass(new MinifyTransformations());

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
