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
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
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
        yield 'Leaves the document untouched when an animation sets the class' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="b"><set attributeName="class" to="a"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="b"><set attributeName="class" to="a"/></rect></svg>',
        ];

        yield 'Leaves the document untouched with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a" onclick="f()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a" onclick="f()"/></svg>',
        ];

        yield 'Keeps a declaration in CSS when an element sets the attribute to another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:foo;stroke:red}</style><rect class="a" fill="blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:foo}</style><rect class="a" fill="blue" stroke="red"/></svg>',
        ];

        yield 'Keeps classes matched by a class attribute selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}[class="a"]{stroke:blue}</style><rect class="a" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[class="a"]{stroke:blue}</style><rect class="a" width="5" height="5" fill="red"/></svg>',
        ];

        yield 'Keeps classes matched by a namespaced class attribute selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}rect[ *|CLASS^="a"]{stroke:blue}</style><rect class="a" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect[ *|CLASS^="a"]{stroke:blue}</style><rect class="a" width="5" height="5" fill="red"/></svg>',
        ];

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
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:green!important}.b{fill:red}</style><rect class="a b"/></svg>',
        ];

        yield 'Keeps a class used by an escaped class selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.b{fill:red}.\62{stroke:blue}</style><rect class="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.\62{stroke:blue}</style><rect class="b" fill="red"/></svg>',
        ];

        yield 'Keeps a longhand that a shorthand later in the rule overrides' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{marker:url(#m);marker-start:none}</style><path class="a" d="M0 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{marker:url(#m);marker-start:none}</style><path class="a" d="M0 0"/></svg>',
        ];

        yield 'Keeps a longhand that a shorthand in another class rule sets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.b{marker:url(#m)}.a{marker-start:none}</style><path class="a b" d="M0 0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.b{marker:url(#m)}.a{marker-start:none}</style><path class="a b" d="M0 0"/></svg>',
        ];

        yield 'Keeps declarations in a rule that resets all properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{all:initial;fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{all:initial;fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps a property declared twice in a rule as a fallback' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;fill:foo(1)}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;fill:foo(1)}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps every declaration of a property that stays CSS' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{font-size:10px !important;font-size:12px}</style><text class="a">x</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{font-size:10px !important;font-size:12px}</style><text class="a">x</text></svg>',
        ];

        yield 'Keeps the case of custom property names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{--Main:red;--main:blue;fill:var(--Main)}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{--Main:red;--main:blue}</style><rect class="a" fill="var(--Main)"/></svg>',
        ];

        yield 'Keeps a property in every rule when a class is also on an HTML element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.b{fill:blue}</style><rect class="a b"/><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" class="a">x</div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.b{fill:blue}</style><rect class="a b"/><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" class="a">x</div></foreignObject></svg>',
        ];

        yield 'Keeps a style sheet of another type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/xsl">.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/xsl">.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps a style sheet whose type is padded with whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type=" text/css ">.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type=" text/css ">.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Converts a style sheet with an empty type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="">.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Keeps a style element with an element child, whose text browsers do not apply' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><x>.a{fill:red}</x></style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style><x>.a{fill:red}</x></style><rect class="a"/></svg>',
        ];

        yield 'Keeps a style sheet with a title, which may be an alternative style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style title="x">rect{stroke:blue}</style><style title="y">.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style title="x">rect{stroke:blue}</style><style title="y">.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Converts a style sheet with an uppercase CSS type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="TEXT/CSS">.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Keeps a style element in a foreign namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style xmlns="urn:x">.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style xmlns="urn:x">.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Converts an XHTML style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><style xmlns="http://www.w3.org/1999/xhtml">.a{fill:red}</style></foreignObject><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject/><rect fill="red"/></svg>',
        ];

        yield 'Keeps classes when a style sheet is imported' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(x.css);</style><style>.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(x.css);</style><style>.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps classes when an xml-stylesheet processing instruction links a style sheet' => [
            '<?xml-stylesheet href="x.css"?><svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a"/></svg>',
            '<?xml-stylesheet href="x.css"?><svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps classes when a link element links a style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="x.css"/></foreignObject><style>.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="x.css"/></foreignObject><style>.a{fill:red}</style><rect class="a"/></svg>',
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

        yield 'Keeps a longhand when other CSS sets its shorthand' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path{marker:url(#m)}.a{marker-start:none}</style><path class="a" d="M0 0L10 10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path{marker:url(#m)}.a{marker-start:none}</style><path class="a" d="M0 0L10 10"/></svg>',
        ];

        yield 'Keeps a shorthand when other CSS sets a longhand of it' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>svg{overflow-x:hidden}.a{overflow:visible}</style><svg class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>svg{overflow-x:hidden}.a{overflow:visible}</style><svg class="a"/></svg>',
        ];

        yield 'Keeps a property when other CSS resets all properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{all:initial}.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{all:initial}.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps a shorthand when other CSS sets one of its longhands' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{-webkit-mask-image:none}.a{mask:url(#m)}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{-webkit-mask-image:none}.a{mask:url(#m)}</style><rect class="a"/></svg>',
        ];

        yield 'Converts a property when other CSS sets a property with the same prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill-opacity:.5}.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill-opacity:.5}</style><rect fill="red"/></svg>',
        ];

        yield 'Keeps classes on elements outside the SVG namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{display:none}</style><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" class="a">hi</div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{display:none}</style><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" class="a">hi</div></foreignObject></svg>',
        ];

        yield 'Keeps a property when other CSS selects the attribute it becomes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}[fill]{stroke:blue}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}[fill]{stroke:blue}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps a property when other CSS selects the attribute it becomes by value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}rect[ fill="red"]{stroke:blue}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}rect[ fill="red"]{stroke:blue}</style><rect class="a"/></svg>',
        ];

        yield 'Converts a property when other CSS selects a longer attribute name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}[fill-rule]{stroke:blue}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[fill-rule]{stroke:blue}</style><rect fill="red"/></svg>',
        ];

        yield 'Converts classes in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:style>.a{fill:red}</svg:style><svg:rect class="a"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect fill="red"/></svg:svg>',
        ];

        yield 'Converts rules in a CDATA section' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[.a{fill:red}]]></style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Lets the later of two class rules win, whatever the order of the classes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.b{fill:blue}.a{fill:red}</style><rect class="a b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Lets a rule of a later style element win' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><style>.b{fill:blue}</style><rect class="b a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="blue"/></svg>',
        ];

        yield 'Keeps a class rule that an id selector competes with' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#r{fill:blue}.a{fill:red}</style><rect id="r" class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#r{fill:blue}.a{fill:red}</style><rect id="r" class="a"/></svg>',
        ];

        yield 'Keeps a class rule that a universal selector competes with' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>*{fill:blue}.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>*{fill:blue}.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps an animation of the converted property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a"><set attributeName="fill" to="blue"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"><set attributeName="fill" to="blue"/></rect></svg>',
        ];

        yield 'Converts on deeply nested elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><g><g><g><g><rect class="a"/></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><rect fill="red"/></g></g></g></g></svg>',
        ];

        yield 'Keeps a rule with a non-ASCII class name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.café{fill:red}</style><rect class="café"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.café{fill:red}</style><rect class="café"/></svg>',
        ];

        yield 'Keeps inherit and currentColor values as written' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:inherit;stroke:currentColor}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="inherit" stroke="currentColor"/></svg>',
        ];

        yield 'Keeps an element whose class is case-sensitively different' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.A{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect class="a"/></svg>',
        ];

        yield 'Converts a rule that a pseudo-class rule for another property follows' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.a:hover{stroke:blue}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a:hover{stroke:blue}</style><rect class="a" fill="red"/></svg>',
        ];

        yield 'Keeps a rule that a pseudo-class rule for the same property follows' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.a:hover{fill:blue}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.a:hover{fill:blue}</style><rect class="a"/></svg>',
        ];

        yield 'Leaves a document without a style element unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect class="a"/></svg>',
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps a class selector inside an :is() list, whose specificity is that of its most specific argument' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect.b{fill:blue}:is(#q, .a, #z){fill:red}</style><rect class="a b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect.b{fill:blue}:is(#q, .a, #z){fill:red}</style><rect class="a b"/></svg>',
        ];

        yield 'Keeps class selectors inside :where() and :not() lists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>:where(.a, .b){stroke:red}rect:not(.a, .b){fill:red}.c{opacity:.5}</style><rect class="a c"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>:where(.a, .b){stroke:red}rect:not(.a, .b){fill:red}</style><rect class="a" opacity=".5"/></svg>',
        ];

        yield 'Keeps a class used by a [class~=a] selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}[class~=a]{stroke:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[class~=a]{stroke:red}</style><rect class="a" fill="red"/></svg>',
        ];

        yield 'Keeps a property selected by a case-insensitive attribute selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}[fill="RED" i]{stroke:blue}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}[fill="RED" i]{stroke:blue}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps style sheets with a supports block untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports (fill:red){.a{fill:red}}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports (fill:red){.a{fill:red}}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps style sheets with a layer block untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@layer x{.a{fill:red}}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@layer x{.a{fill:red}}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps style sheets with a layer statement untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@layer x;.a{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@layer x;.a{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps style sheets with a container block untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@container (min-width:1px){.a{fill:red}}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@container (min-width:1px){.a{fill:red}}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps style sheets with nested rules untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;&amp;:hover{fill:blue}}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;&amp;:hover{fill:blue}}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps style sheets with a comment inside a selector untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a/* c */{fill:red}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a/* c */{fill:red}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps a rule with an escaped class selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a\:b{fill:red}</style><rect class="a:b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a\:b{fill:red}</style><rect class="a:b"/></svg>',
        ];

        yield 'Converts a class written with a character reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="&#97;"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Converts the rules of several plain and CDATA style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><style><![CDATA[.b{stroke:blue}]]></style><style>.c{opacity:.5}</style><rect class="a b c"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red" stroke="blue" opacity=".5"/></svg>',
        ];

        yield 'Converts a class in a document with redeclared namespace prefixes' => [
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><s:style>.a{fill:red}</s:style><t:g xmlns:t="http://www.w3.org/2000/svg"><t:rect class="a"/></t:g></s:svg>',
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><t:g xmlns:t="http://www.w3.org/2000/svg"><t:rect fill="red"/></t:g></s:svg>',
        ];

        yield 'Keeps a class on an element in another namespace after converting an earlier element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}.b{stroke:red}</style><rect class="b"/><x:rect xmlns:x="urn:x" class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect stroke="red"/><x:rect xmlns:x="urn:x" class="a"/></svg>',
        ];

        yield 'Keeps the case of a mixed-case class name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.MyClass{fill:red}</style><rect class="MyClass"/><rect class="myclass"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/><rect class="myclass"/></svg>',
        ];

        yield 'Keeps an important value in any spelling' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red ! IMPORTANT}</style><rect class="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red ! IMPORTANT}</style><rect class="a"/></svg>',
        ];

        yield 'Keeps an important declaration of an earlier style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red!important}</style><style>.b{stroke:red}</style><rect class="a"/><rect class="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red!important}</style><rect class="a"/><rect stroke="red"/></svg>',
        ];

        yield 'Keeps a declaration when an element of any selector of the list sets the attribute to another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a,.b{fill:foo}</style><rect class="a" fill="blue"/><rect class="b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:foo}.b{fill:foo}</style><rect class="a" fill="blue"/><rect class="b"/></svg>',
        ];

        yield 'Keeps a property in a later rule that an earlier rule has to keep' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;fill:blue}</style><style>.b{fill:green}</style><rect class="a b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;fill:blue}</style><style>.b{fill:green}</style><rect class="a b"/></svg>',
        ];
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideIsIdempotentCases')]
    #[Test]
    public function isIdempotent(string $content): void
    {
        $once = new SvgOptimizer(new StringProvider($content));
        $once->addRule(new ConvertCssClassesToAttributes());

        $expected = $once->optimize()->getContent();

        $twice = new SvgOptimizer(new StringProvider($expected));
        $twice->addRule(new ConvertCssClassesToAttributes());

        self::assertSame($expected, $twice->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIsIdempotentCases(): iterable
    {
        yield 'partly converted' => ['<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;font-size:2px}</style><rect class="a"/></svg>'];

        yield 'blocked property' => ['<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red;fill:blue}.b{stroke:red}</style><rect class="a b"/></svg>'];
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

    #[Test]
    public function handlesManyNamespaceDeclarationsInLinearTime(): void
    {
        $small = $this->secondsToOptimize(2_000);
        $large = $this->secondsToOptimize(8_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><style>.a{fill:red}</style><rect class="a"/></svg>');

        $start = hrtime(true);
        (new ConvertCssClassesToAttributes())->optimize($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
