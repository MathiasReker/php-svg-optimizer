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

        yield 'Overwrites existing attributes, which the inline style beats' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="#000" style="fill:#f00;stroke:#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Keeps important declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:green!important;stroke:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:green!important" stroke="red"/></svg>',
        ];

        yield 'Keeps inline styles when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect style="fill:green"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect style="fill:green"/></svg>',
        ];

        yield 'Uses the last of repeated declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;fill:green"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="green"/></svg>',
        ];

        yield 'Converts properties with whitespace before the colon' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill :red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Keeps colons in values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(http://example.com/a.svg#g)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(http://example.com/a.svg#g)"/></svg>',
        ];

        yield 'Keeps properties that only match after non-ASCII case folding' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="stro&#x212A;e:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="stro&#x212A;e:red"/></svg>',
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

        yield 'Drops colon-less declaration but still converts valid one' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="foo;fill:red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>
                XML,
        ];

        yield 'Keeps inline styles when an external style sheet is linked' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:green"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:green"/></svg>',
        ];

        yield 'Keeps a semicolon inside url() as part of the value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(data:image/png;base64,AA);stroke:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(data:image/png;base64,AA)" stroke="red"/></svg>',
        ];

        yield 'Keeps a semicolon inside a string as part of the value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:&quot;a;b&quot;;fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:&quot;a;b&quot;" fill="red"/></svg>',
        ];

        yield 'Keeps an escaped semicolon as part of the value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:a\;b;fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:a\;b" fill="red"/></svg>',
        ];

        yield 'Keeps a semicolon inside nested parentheses as part of the value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(a(b;c));stroke:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(a(b;c))" stroke="red"/></svg>',
        ];

        yield 'Leaves a style with an unclosed parenthesis untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(a;stroke:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:url(a;stroke:red"/></svg>',
        ];

        yield 'Leaves a style with an unclosed string untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:&quot;a;fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:&quot;a;fill:red"/></svg>',
        ];
    }

    #[Test]
    public function ruleIsMarkedAsNotRisky(): void
    {
        self::assertFalse(ConvertInlineStylesToAttributes::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(ConvertInlineStylesToAttributes::shouldCheckSize());
    }
}
