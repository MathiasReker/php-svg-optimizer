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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyTextElements;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveEmptyTextElements::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(SvgValidationException::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
final class RemoveEmptyTextElementsTest extends TestCase
{
    /**
     * @throws SvgValidationException
     */
    #[DataProvider('provideOptimizeCases')]
    public function testOptimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveEmptyTextElements());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes empty <text> element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text/>
                    <rect width="10" height="10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>
                XML,
        ];

        yield 'Removes empty <tspan> element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text><tspan/></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes <tref> with empty xlink:href' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text><tref xlink:href=""/></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps <text> with children' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>Hello<tspan>World</tspan></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text>Hello<tspan>World</tspan></text></svg>
                XML,
        ];

        yield 'Keeps <tspan> with text' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text><tspan>Text</tspan></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text><tspan>Text</tspan></text></svg>
                XML,
        ];

        yield 'Removes multiple empty <text> and <tspan>' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text/><text><tspan/></text><rect width="1" height="1"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>
                XML,
        ];

        yield 'Removes nested empty elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>
                        <tspan><tref xlink:href=""/></tspan>
                        <tspan>Content</tspan>
                    </text>
                    <text/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text><tspan>Content</tspan></text></svg>
                XML,
        ];

        yield 'Keeps elements with whitespace-only text if parent is non-empty' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>   <tspan>Text</tspan>   </text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text><tspan>Text</tspan></text></svg>
                XML,
        ];

        yield 'Removes empty SVG elements gracefully' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps comments and mixed content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text><!-- comment --><tspan>Text</tspan></text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text><!-- comment --><tspan>Text</tspan></text></svg>
                XML,
        ];

        yield 'Removes deeply nested empty elements with siblings' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>
                        <tspan><tref xlink:href=""/></tspan>
                        <tspan></tspan>
                    </text>
                    <rect width="2" height="2"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="2" height="2"/></svg>
                XML,
        ];
    }
}
