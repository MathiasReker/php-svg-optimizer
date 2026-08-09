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
#[CoversClass(XmlFormatter::class)]
final class MinifyTransformationsTest extends TestCase
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
        $svgOptimizer->addRule(new MinifyTransformations());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
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

        yield 'Retains Non-Identity Transforms' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10,20) scale(2,2)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,20) scale(2,2)"><rect x="10" y="10" width="30" height="30"/></g></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,20)"><rect x="10" y="10" width="30" height="30"/></g></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,10) rotate(45) scale(0.5)"><rect x="10" y="10" width="30" height="30"/></g></svg>
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

        yield 'Handles Transformations with Percentages' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="scale(50%,50%)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="scale(0.5,0.5)"><rect x="10" y="10" width="30" height="30"/></g></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10,20) scale(2,0.5) rotate(45) skewX(10) skewY(20)"><rect x="10" y="10" width="30" height="30"/></g></svg>
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

        yield 'Handles Complex Transformations with Percentages' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10%,20%) scale(50%,50%) rotate(45deg)">
                        <rect x="10" y="10" width="30" height="30"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(0.1,0.2) scale(0.5,0.5) rotate(45deg)"><rect x="10" y="10" width="30" height="30"/></g></svg>
                XML,
        ];

        yield 'Handles Single Transformations with Percentages' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="scale(75%)">
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="scale(0.75)"><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Handles Transformations with Decimal Percentages' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(12.5%,10%)">
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(0.125,0.1)"><circle cx="50" cy="50" r="20"/></g></svg>
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

        yield 'Keeps Rotation with Non-Zero Angle and Zero Center' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="rotate(90 0 0)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="rotate(90 0 0)"><circle cx="10" cy="10" r="5"/></g></svg>
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

        yield 'Keeps Comma-Separated Non-Identity Matrix' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(1,0,0,1,10,20)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="matrix(1,0,0,1,10,20)"><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Keeps Non-Identity Matrix' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(1 0 0 1 10 20)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="matrix(1 0 0 1 10 20)"><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];

        yield 'Handles Degenerate Matrix with Zeros' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="matrix(0 0 0 0 0 0)">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="matrix(0 0 0 0 0 0)"><circle cx="10" cy="10" r="5"/></g></svg>
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

        yield 'Handles Transform Attribute with Extra Semicolons or Commas' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(0,0); scale(1,1);">
                        <circle cx="10" cy="10" r="5"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle cx="10" cy="10" r="5"/></g></svg>
                XML,
        ];
    }
}
