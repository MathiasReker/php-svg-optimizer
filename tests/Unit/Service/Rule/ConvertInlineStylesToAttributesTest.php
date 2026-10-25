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
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertInlineStylesToAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgInlineStyleProperty;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
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
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(CssDeclarationTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
final class ConvertInlineStylesToAttributesTest extends TestCase
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
        $svgOptimizer->addPass(new ConvertInlineStylesToAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps a style whose conversion would be one byte longer' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red; foo:1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red; foo:1"/></svg>',
        ];

        yield 'Keeps a longhand inline that an earlier shorthand of it overrides' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" style="marker: url(#m) ;  marker-start: none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" style="marker:url(#m);marker-start:none"/></svg>',
        ];

        yield 'Keeps a shorthand inline when a later longhand of it stays inline' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><svg style="overflow: visible ;  overflow-x: hidden"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><svg style="overflow:visible;overflow-x:hidden"/></svg>',
        ];
        yield 'Leaves the document untouched with a linked style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><rect style="fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><rect style="fill:red"/></svg>',
        ];

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
                <svg xmlns="http://www.w3.org/2000/svg"><rect style="123abc:#f00;fill:#0f0"/></svg>
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

        yield 'Keeps a declaration in the style when the element has the attribute with another value, which may be invalid CSS' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect fill="#000" style="fill:#f00;stroke:#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect fill="#000" style="fill:#f00;stroke:#0f0"/></svg>
                XML,
        ];

        yield 'Converts part of a style when the result is shorter, writing the rest without spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill: red; foo: 1; bar: 2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="foo:1;bar:2" fill="red"/></svg>',
        ];

        yield 'Keeps a style whose conversion would be longer' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;foo:1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;foo:1"/></svg>',
        ];

        yield 'Converts a style when the result has the same length' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;  foo:1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="foo:1" fill="red"/></svg>',
        ];

        yield 'Keeps important declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:green!important;stroke:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:green!important;stroke:red"/></svg>',
        ];

        yield 'Keeps inline styles when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect style="fill:green"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect style="fill:green"/></svg>',
        ];

        yield 'Keeps repeated declarations, as an invalid later one falls back to the earlier one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;fill:foo(1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;fill:foo(1)"/></svg>',
        ];

        yield 'Keeps repeated declarations in any case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;FILL:green;stroke:blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;FILL:green;stroke:blue"/></svg>',
        ];

        yield 'Keeps a longhand that a later shorthand overrides' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" style="marker:url(#m);marker-start:none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0" style="marker:url(#m);marker-start:none"/></svg>',
        ];

        yield 'Keeps a shorthand when a longhand of it stays inline' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><svg style="overflow-x:hidden;overflow:visible"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><svg style="overflow-x:hidden;overflow:visible"/></svg>',
        ];

        yield 'Keeps a mask shorthand when a mask longhand stays inline' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="mask-image:url(#a);mask:none;fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="mask-image:url(#a);mask:none;fill:red"/></svg>',
        ];

        yield 'Keeps declarations next to a reset of all properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="all:initial;fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="all:initial;fill:red"/></svg>',
        ];

        yield 'Converts a property whose value mentions a shorthand' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="font-family:all;fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="font-family:all;fill:red"/></svg>',
        ];

        yield 'Leaves a style with a comment untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="/*x;*/fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="/*x;*/fill:red"/></svg>',
        ];

        yield 'Leaves styles of elements outside the SVG namespace untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" style="opacity:.5;display:none">x</div></foreignObject><rect style="opacity:.5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" style="opacity:.5;display:none">x</div></foreignObject><rect opacity=".5"/></svg>',
        ];

        yield 'Keeps the case of custom property names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="--Main:red;fill:var(--Main)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="--Main:red;fill:var(--Main)"/></svg>',
        ];

        yield 'Converts styles in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect style="fill:red"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect fill="red"/></svg:svg>',
        ];

        yield 'Keeps inline styles when an XHTML style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><style xmlns="http://www.w3.org/1999/xhtml">rect{fill:blue}</style></foreignObject><rect style="fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><style xmlns="http://www.w3.org/1999/xhtml">rect{fill:blue}</style></foreignObject><rect style="fill:red"/></svg>',
        ];

        yield 'Keeps an animation of a converted property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red"><set attributeName="fill" to="blue"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"><set attributeName="fill" to="blue"/></rect></svg>',
        ];

        yield 'Keeps inherit and currentColor values as written' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:inherit;stroke:currentColor"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="inherit" stroke="currentColor"/></svg>',
        ];

        yield 'Keeps ampersands and unicode in values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:\'a&amp;b\';fill:url( &quot;#ü&quot; )">x</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:\'a&amp;b\';fill:url( &quot;#ü&quot; )">x</text></svg>',
        ];

        yield 'Keeps important declarations in any spelling' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red ! IMPORTANT;stroke:blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red ! IMPORTANT;stroke:blue"/></svg>',
        ];

        yield 'Converts mixed-case property names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="Fill-Opacity:.5;STROKE-width:2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill-opacity=".5" stroke-width="2"/></svg>',
        ];

        yield 'Converts styles of deeply nested elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><rect style="fill:red"/></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><rect fill="red"/></g></g></g></g></svg>',
        ];

        yield 'Converts styles in a document with redeclared namespace prefixes' => [
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><t:rect xmlns:t="http://www.w3.org/2000/svg" style="fill:red"/></s:svg>',
            '<s:svg xmlns:s="http://www.w3.org/2000/svg"><t:rect xmlns:t="http://www.w3.org/2000/svg" fill="red"/></s:svg>',
        ];

        yield 'Leaves styles of elements in another namespace untouched after converting an earlier one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red"/><x:rect xmlns:x="urn:x" style="fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/><x:rect xmlns:x="urn:x" style="fill:red"/></svg>',
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps inline styles when an xml-stylesheet processing instruction links a style sheet' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red"/></svg>',
        ];

        yield 'Converts the style of a later element after skipping an earlier one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red !important"/><rect style="fill:blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red !important"/><rect fill="blue"/></svg>',
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
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="stroKe:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="stroKe:red"/></svg>',
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
                <svg xmlns="http://www.w3.org/2000/svg"><rect style="invalid-prop:1;another-invalid:2"/></svg>
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
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:&quot;a;b&quot;;fill:red"/></svg>',
        ];

        yield 'Keeps an escaped semicolon as part of the value' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:a\;b;fill:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:a\;b;fill:red"/></svg>',
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

        yield 'Drops a declaration without a property name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;:blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Keeps a longhand that the last declaration overrides as a shorthand' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path style="marker-end:url(#a);marker:url(#b)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path style="marker-end:url(#a);marker:url(#b)"/></svg>',
        ];
    }
}
