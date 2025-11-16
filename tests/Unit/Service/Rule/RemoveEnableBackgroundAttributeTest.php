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
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEnableBackgroundAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveEnableBackgroundAttribute::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class RemoveEnableBackgroundAttributeTest extends TestCase
{
    /**
     * @throws SvgValidationException
     */
    #[DataProvider('provideOptimizeCases')]
    public function testOptimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveEnableBackgroundAttribute());

        $actual = $svgOptimizer->allowRisky()->optimize()->getContent();

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes enable-background for svg with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Keeps enable-background for svg with different dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background for mask with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <mask xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                        <rect x="10" y="10" width="30" height="30"/>
                    </mask>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><mask xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></mask></svg>
                XML,
        ];

        yield 'Keeps enable-background for pattern with different dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                        <rect x="10" y="10" width="30" height="30"/>
                    </pattern>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100"><rect x="10" y="10" width="30" height="30"/></pattern></svg>
                XML,
        ];

        yield 'Removes enable-background for pattern with matching dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                        <rect x="10" y="10" width="30" height="30"/>
                    </pattern>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><pattern xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></pattern></svg>
                XML,
        ];

        yield 'Removes enable-background for svg with no filter' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 100 50">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Keeps enable-background if no filter and dimensions don’t match' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="new 0 0 200 100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Keeps enable-background when value does not match regex' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="invalid value">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" enable-background="invalid value"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background from style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="enable-background:new 0 0 100 50;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background from mixed style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="fill:red; enable-background:new 0 0 100 50;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="fill:red;"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background in style if dimensions do not match' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="enable-background:new 0 0 200 100;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background in style if invalid value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="enable-background:invalid value;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes enable-background from style if it matches dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50" style="enable-background:new 0 0 100 50;">
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];
    }
}
