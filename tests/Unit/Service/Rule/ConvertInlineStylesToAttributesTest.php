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
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertInlineStylesToAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(SvgInlineStyleProperty::class)]
final class ConvertInlineStylesToAttributesTest extends TestCase
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
        $svgOptimizer->addRule(new ConvertInlineStylesToAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Converts fill and stroke from style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill:#f00;stroke:#0f0;stroke-width:2"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00" stroke="#0f0" stroke-width="2"/></svg>
                XML,
        ];

        yield 'Preserves non-SVG style properties' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle style="fill:#00f;display:none;opacity:0.5"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle fill="#00f" display="none" opacity="0.5"/></svg>
                XML,
        ];

        yield 'Ignores malformed style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <path style="fill;stroke"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><path style="fill;stroke"/></svg>
                XML,
        ];

        yield 'Handles multiple elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill:#f00"/>
                    <circle style="stroke:#0f0;stroke-width:1"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00"/><circle stroke="#0f0" stroke-width="1"/></svg>
                XML,
        ];

        yield 'Ignores empty style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <path style=""/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><path style=""/></svg>
                XML,
        ];

        yield 'Handles style with only whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle style="   "/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle style="   "/></svg>
                XML,
        ];

        yield 'Handles incomplete declaration with missing value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill:;stroke:#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Ignore invalid property name' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="123abc:#f00;fill:#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect style="123abc:#f00" fill="#0f0"/></svg>
                XML,
        ];

        yield 'Converts uppercase style properties' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle style="FiLL:#f00;STROKE:#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle fill="#f00" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Keeps existing attributes without overwriting' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="#000" style="fill:#f00;stroke:#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#000" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Leaves unknown properties when valid SVG props converted' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle style="fill:#f00;cursor:pointer;stroke:#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle fill="#f00" cursor="pointer" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Removes style attribute when fully converted' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <ellipse style="fill:#0f0;stroke:#f00"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><ellipse fill="#0f0" stroke="#f00"/></svg>
                XML,
        ];

        yield 'Handles style with extra semicolons gracefully' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <line style="fill:#f00;;stroke:#0f0;;"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><line fill="#f00" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Handles nested elements each with style' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g style="fill:#f00">
                        <rect style="stroke:#0f0;opacity:0.8"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g fill="#f00"><rect stroke="#0f0" opacity="0.8"/></g></svg>
                XML,
        ];

        yield 'Handles style attributes with dashes and opacity variants' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <path style="stroke-width:2;stroke-opacity:0.7;fill-opacity:0.3"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><path stroke-width="2" stroke-opacity="0.7" fill-opacity="0.3"/></svg>
                XML,
        ];

        yield 'Handles empty style declaration' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill:;"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Handles style with only invalid properties' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="invalid-prop: 1; another-invalid: 2;"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect style="invalid-prop:1; another-invalid:2"/></svg>
                XML,
        ];

        yield 'Handles style with no value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill:"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];
    }
}
