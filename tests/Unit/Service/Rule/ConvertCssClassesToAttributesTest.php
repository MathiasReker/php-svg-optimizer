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
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertCssClassesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertCssClassesToAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(SvgInlineStyleProperty::class)]
final class ConvertCssClassesToAttributesTest extends TestCase
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
        $svgOptimizer->addRule(new ConvertCssClassesToAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Converts class-based CSS to attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        .red { fill: #f00; stroke: #0f0; }
                    </style>
                    <rect class="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Handles multiple classes, only converts matching ones' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        .blue { fill: #00f; }
                    </style>
                    <circle class="blue other"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle class="other" fill="#00f"/></svg>
                XML,
        ];

        yield 'Removes style tag after conversion' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.cls { stroke:#000; }</style>
                    <line class="cls"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><line stroke="#000"/></svg>
                XML,
        ];

        yield 'Ignores CSS properties not supported by SVG' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.cls { font-size:12px; fill:#f00; }</style>
                    <rect class="cls"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>.cls{font-size:12px}</style><rect class="cls" fill="#f00"/></svg>
                XML,
        ];

        yield 'Handles multiple CSS classes in one style tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        .a { fill:#f00; }
                        .b { stroke:#00f; opacity:0.5; }
                    </style>
                    <rect class="a"/>
                    <circle class="b"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00"/><circle stroke="#00f" opacity="0.5"/></svg>
                XML,
        ];

        yield 'Handles multiple style tags' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.a { fill:#f00; }</style>
                    <style>.b { stroke:#0f0; }</style>
                    <rect class="a"/>
                    <circle class="b"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00"/><circle stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Ignores malformed CSS declarations' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        .bad { fill }
                        .ok { stroke:#000; }
                    </style>
                    <rect class="bad"/>
                    <circle class="ok"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect class="bad"/><circle stroke="#000"/></svg>
                XML,
        ];

        yield 'Keeps class when partially converted' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.cls { fill:#f00; test:test; }</style>
                    <rect class="cls"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>.cls{test:test}</style><rect class="cls" fill="#f00"/></svg>
                XML,
        ];

        yield 'Removes empty class attribute after conversion' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.only { fill:#0f0; }</style>
                    <rect class="only"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#0f0"/></svg>
                XML,
        ];

        yield 'Ignores unused class definitions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.unused { fill:#f00; }</style>
                    <circle/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle/></svg>
                XML,
        ];

        yield 'Handles nested elements correctly' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.nested { stroke:#00f; }</style>
                    <g class="nested">
                        <path d="M0 0 L10 10"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g stroke="#00f"><path d="M0 0 L10 10"/></g></svg>
                XML,
        ];

        yield 'Handles mixed case and extra whitespace in CSS' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        .MyClass {   FiLL :  #ABC ;  stroke : #DEF ; }
                    </style>
                    <rect class="MyClass"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#ABC" stroke="#DEF"/></svg>
                XML,
        ];
    }
}
