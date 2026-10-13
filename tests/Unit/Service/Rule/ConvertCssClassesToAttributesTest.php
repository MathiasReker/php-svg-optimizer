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

        yield 'Remove malformed CSS declarations' => [
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
                <svg xmlns="http://www.w3.org/2000/svg"><rect/><circle stroke="#000"/></svg>
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

        yield 'Removes empty style tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style></style>
                    <rect/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>
                XML,
        ];

        yield 'Keeps style tag with non-convertible rules' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.a { font-size: 12px; }</style>
                    <rect class="a"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>.a{font-size:12px}</style><rect class="a"/></svg>
                XML,
        ];

        yield 'Converts grouped comma-separated class selectors sharing a declaration block' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>.foo, .bar { fill: #f00; }</style>
                    <rect class="foo"/>
                    <circle class="bar"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00"/><circle fill="#f00"/></svg>
                XML,
        ];

        yield 'Preserves non-class CSS rules such as id selectors instead of dropping them' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>#foo { fill: red; } .bar { stroke: blue; }</style>
                    <rect id="foo"/>
                    <circle class="bar"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><style>#foo{fill: red;}</style><rect id="foo"/><circle stroke="blue"/></svg>
                XML,
        ];

        yield 'Handles multiple classes on one element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <style>
                        .a { fill: #f00; }
                        .b { stroke: #00f; }
                    </style>
                    <rect class="a b"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00" stroke="#00f"/></svg>
                XML,
        ];

        yield 'Keeps the other classes of an element whose rule is partly converted' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;animation-name:x}.b:hover{stroke:blue}</style><path class="a b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{animation-name:x}.b:hover{stroke:blue}</style><path class="a b" fill="red"/></svg>',
        ];

        yield 'Keeps a class that a later rule still refers to' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.a{animation-name:x}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{animation-name:x}</style><path class="a" fill="red"/></svg>',
        ];

        yield 'Keeps a class that a selector of another style element refers to' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><style>g .a{stroke:blue}</style><g><path class="a"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g .a{stroke:blue}</style><g><path class="a" fill="red"/></g></svg>',
        ];

        yield 'Removes a class whose name only prefixes a class that remaining CSS refers to' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.a-b:hover,.ab:hover,#a:hover{stroke:blue}</style><path class="a a-b ab"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a-b:hover,.ab:hover,#a:hover{stroke:blue}</style><path class="a-b ab" fill="red"/></svg>',
        ];

        yield 'Converts classes separated by any whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><path class="b&#9;a"/><path class="a&#10;"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path class="b" fill="red"/><path fill="red"/></svg>',
        ];

        yield 'Converts a style element that follows an empty one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style></style><style>.a{fill:red}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="red"/></svg>',
        ];

        yield 'Splits a class selector that follows another selector into its own rule' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g, .a{fill:red}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}g{fill:red}</style><path class="a"/></svg>',
        ];

        yield 'Keeps properties that a rule with another selector also sets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}.a{fill:green;stroke:blue}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}.a{fill:green}</style><rect class="a" stroke="blue"/></svg>',
        ];

        yield 'Keeps properties that an unconvertible style element sets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media screen{rect{fill:red}}</style><style>.a{fill:green}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media screen{rect{fill:red}}</style><style>.a{fill:green}</style><rect class="a"/></svg>',
        ];

        yield 'Converts a property that other CSS only mentions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#x{transition: opacity 1s;fill-opacity:1}.a{opacity:.5}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#x{transition: opacity 1s;fill-opacity:1}</style><rect opacity=".5"/></svg>',
        ];

        yield 'Keeps a property that other CSS sets in any case and spacing' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#x{stroke:blue;FILL :red; opacity:0}.a{fill:green;opacity:1}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#x{stroke:blue;FILL :red; opacity:0}.a{fill:green;opacity:1}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps comments in declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red/* c */}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red/* c */}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps an important declaration ahead of a later class' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:green!important}.b{fill:red}</style><rect class="a b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:green!important}</style><rect class="a" fill="red"/></svg>',
        ];

        yield 'Keeps style elements for other media untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print">.a{fill:red}</style><style>.b{stroke:red}</style><rect class="a b" fill="green"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print">.a{fill:red}</style><rect class="a" fill="green" stroke="red"/></svg>',
        ];

        yield 'Converts style elements for all media' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media=" ALL ">.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Skips declarations without a colon' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{garbage;fill:red}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="red"/></svg>',
        ];

        yield 'Keeps colons in values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:url(http://example.com/a.svg#g)}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="url(http://example.com/a.svg#g)"/></svg>',
        ];

        yield 'Keeps important declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red !important;stroke:blue}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red !important}</style><path class="a" stroke="blue"/></svg>',
        ];

        yield 'Keeps style sheets with at-rules untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media (min-width:1px){.a{fill:red}}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media (min-width:1px){.a{fill:red}}</style><path class="a"/></svg>',
        ];

        yield 'Keeps style sheets with comments untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/* c */.a{fill:red}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/* c */.a{fill:red}</style><path class="a"/></svg>',
        ];

        yield 'Keeps style sheets with text outside rules untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}}</style><path class="a"/></svg>',
        ];

        yield 'Keeps properties that only match after non-ASCII case folding' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{stro&#x212A;e:red}</style><path class="a"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>.a{stro\u{212A}e:red}</style><path class=\"a\"/></svg>",
        ];

        yield 'Keeps a semicolon inside url() as part of the value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:url(data:image/png;base64,AA);stroke:red}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path fill="url(data:image/png;base64,AA)" stroke="red"/></svg>',
        ];

        yield 'Keeps a semicolon inside a string as part of the value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{font-family:"a;b";fill:red}</style><text class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{font-family:"a;b"}</style><text class="a" fill="red"/></svg>',
        ];

        yield 'Keeps style sheets with an unclosed parenthesis untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:url(a;stroke:red}</style><path class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:url(a;stroke:red}</style><path class="a"/></svg>',
        ];

        yield 'Keeps style sheets with a brace inside a string untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{font-family:"}";fill:red}</style><text class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{font-family:"}";fill:red}</style><text class="a"/></svg>',
        ];
    }

    #[Test]
    public function ruleIsMarkedAsNotRisky(): void
    {
        self::assertFalse(ConvertCssClassesToAttributes::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(ConvertCssClassesToAttributes::shouldCheckSize());
    }
}
