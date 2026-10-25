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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgElementAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\CssDeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
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
#[CoversClass(RemoveNonStandardAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(CssDeclarationTrait::class)]
#[CoversTrait(DocumentScriptTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(SvgAttribute::class)]
#[UsesClass(SvgElementAttributes::class)]
#[UsesClass(SvgNamespace::class)]
final class RemoveNonStandardAttributesTest extends TestCase
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
        $svgOptimizer->addPass(new RemoveNonStandardAttributes());

        $actual = $svgOptimizer
            ->allowRisky()
            ->optimize()
            ->getContent();

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps the attributes of the XHTML content of a foreignObject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10" foo="1"><div xmlns="http://www.w3.org/1999/xhtml" hidden="hidden"><input type="checkbox" checked="checked" value="1"/></div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><div xmlns="http://www.w3.org/1999/xhtml" hidden="hidden"><input type="checkbox" checked="checked" value="1"/></div></foreignObject></svg>',
        ];

        yield 'Keeps the attributes of elements inside a foreignObject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><rect width="1" points="1 2"/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><rect width="1" points="1 2"/></foreignObject></svg>',
        ];

        yield 'Keeps standard width and height' => [
            '<svg width="100" height="50"><rect width="10" height="10"/></svg>',
            '<svg width="100" height="50"><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes single unknown attribute' => [
            '<svg foo="bar"></svg>',
            '<svg/>',
        ];

        yield 'Removes multiple unknown attributes' => [
            '<svg foo="1" bar="2" baz="3"></svg>',
            '<svg/>',
        ];

        yield 'Keeps allowed and removes unknown on same element' => [
            '<svg width="100" foo="bar" height="50"></svg>',
            '<svg width="100" height="50"/>',
        ];

        yield 'Removes unknown attribute on child element' => [
            '<svg><rect width="10" custom="x"/></svg>',
            '<svg><rect width="10"/></svg>',
        ];

        yield 'Removes unknown attributes on deeply nested elements' => [
            '<svg><g><g><rect width="10" evil="true"/></g></g></svg>',
            '<svg><g><g><rect width="10"/></g></g></svg>',
        ];

        yield 'Keeps xml, xlink, data-* and on* attributes' => [
            '<svg xml:lang="en" data-test="1" onload="alert(1)" onfake="oops"><use xlink:href="#a" onclick="x()"/></svg>',
            '<svg xml:lang="en" data-test="1" onload="alert(1)" onfake="oops"><use xlink:href="#a" onclick="x()"/></svg>',
        ];

        yield 'Removes attributes the specification does not define for the element' => [
            '<svg xlink:href="#a" points="1 2" viewBox="0 0 1 1"><rect d="M0 0" href="#a" width="1"/><path points="1 2" d="M0 0"/><stop offset="1" x="1"/></svg>',
            '<svg viewBox="0 0 1 1"><rect width="1"/><path d="M0 0"/><stop offset="1"/></svg>',
        ];

        yield 'Keeps ev:event bound to the XML Events namespace where allowed' => [
            '<svg xmlns:ev="http://www.w3.org/2001/xml-events"><script ev:event="load"/><rect ev:event="load" width="1"/></svg>',
            '<svg xmlns:ev="http://www.w3.org/2001/xml-events"><script ev:event="load"/><rect width="1"/></svg>',
        ];

        yield 'Keeps attributes the specification defines for the element' => [
            '<svg><animate attributeName="x" min="1s" max="2s" dur="3s"/><textPath method="align" startOffset="1">a</textPath><linearGradient gradientUnits="userSpaceOnUse" x1="0"/></svg>',
            '<svg><animate attributeName="x" min="1s" max="2s" dur="3s"/><textPath method="align" startOffset="1">a</textPath><linearGradient gradientUnits="userSpaceOnUse" x1="0"/></svg>',
        ];

        yield 'Keeps shared presentation and aria attributes on every SVG element' => [
            '<svg fill="red" aria-label="x"><g stroke="blue" role="img"><circle r="1" opacity=".5"/></g></svg>',
            '<svg fill="red" aria-label="x"><g stroke="blue" role="img"><circle r="1" opacity=".5"/></g></svg>',
        ];

        yield 'Falls back to the global allowlist for unknown elements and keeps event attributes' => [
            '<svg><unknown width="1" d="M0 0" foo="bar" onclick="x()"/></svg>',
            '<svg><unknown width="1" d="M0 0" onclick="x()"/></svg>',
        ];

        yield 'Removes HTML-only attributes from unknown elements' => [
            '<svg><unknown accept="x" action="/post" width="1" value="1"/></svg>',
            '<svg><unknown width="1"/></svg>',
        ];

        yield 'Keeps data-* but removes similar-looking attribute' => [
            '<svg data-test="1" dataTest="2"></svg>',
            '<svg data-test="1"/>',
        ];

        yield 'Keeps on* event attributes, which removeScripts removes' => [
            '<svg onclick="doSomething()" onmouseover="hover()" onfake="fail()" width="1"></svg>',
            '<svg onclick="doSomething()" onmouseover="hover()" onfake="fail()" width="1"/>',
        ];

        yield 'Removes name attribute' => [
            '<svg><rect id="a" name="getElementById"/></svg>',
            '<svg><rect id="a"/></svg>',
        ];

        yield 'Removes attributes with javascript-like values if name is unknown' => [
            '<svg foo="javascript:alert(1)" width="10"></svg>',
            '<svg width="10"/>',
        ];

        yield 'Removes namespaced attribute not explicitly allowed' => [
            '<svg><script ev:event="load" custom:attr="x"/></svg>',
            '<svg><script ev:event="load"/></svg>',
        ];

        yield 'Removes attribute bound to a foreign namespace even if its local name is allowed' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:evil"><a x:href="#a" x:onclick="1" href="#b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:evil"><a href="#b"/></svg>',
        ];

        yield 'Keeps known xlink attributes and removes unknown ones' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#a" xlink:title="t" xlink:foo="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#a" xlink:title="t"/></svg>',
        ];

        yield 'Keeps xlink attributes bound via a non-standard prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><use l:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><use l:href="#a"/></svg>',
        ];

        yield 'Removes unknown and dangerous xml attributes' => [
            '<svg xml:space="preserve" xml:base="http://evil.com/" xml:foo="1"></svg>',
            '<svg xml:space="preserve"/>',
        ];

        yield 'Removes HTML-only attributes' => [
            '<svg><image src="x.png" srcset="x.png 1x" width="10"/><g action="/post" background="x.png" value="1"/></svg>',
            '<svg><image width="10"/><g/></svg>',
        ];

        yield 'Removes data-* attribute with invalid name' => [
            '<svg data-="1" data-ok.v2="2"></svg>',
            '<svg data-ok.v2="2"/>',
        ];

        yield 'Keeps attributes on defs and symbols' => [
            '<svg><defs><symbol id="icon" viewBox="0 0 10 10"/></defs></svg>',
            '<svg><defs><symbol id="icon" viewBox="0 0 10 10"/></defs></svg>',
        ];

        yield 'Handles empty svg element' => [
            '<svg></svg>',
            '<svg/>',
        ];

        yield 'Handles svg with whitespace only' => [
            '<svg>   </svg>',
            '<svg/>',
        ];

        yield 'Removes unknown attributes but keeps structure' => [
            '<svg foo="1"><g bar="2"><rect baz="3"/></g></svg>',
            '<svg><g><rect/></g></svg>',
        ];

        yield 'Keeps attributes with mixed case if standard' => [
            '<svg viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"><rect/></svg>',
            '<svg viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"><rect/></svg>',
        ];

        yield 'Keeps the attributes of a discard element' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r"/><discard id="d" href="#r" xlink:href="#r" begin="1s" systemLanguage="en" bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r"/><discard id="d" href="#r" xlink:href="#r" begin="1s" systemLanguage="en"/></svg>',
        ];

        yield 'Removes unknown attributes in a document with a prefixed SVG namespace' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="1" bogus="1"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="1"/></svg:svg>',
        ];

        yield 'Removes attribute names written in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect WIDTH="1" Width="2" width="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="3"/></svg>',
        ];

        yield 'Keeps an XLink attribute bound to a custom prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><use f:href="#a" f:bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="http://www.w3.org/1999/xlink"><use f:href="#a"/></svg>',
        ];

        yield 'Keeps inherit and currentColor presentation attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="inherit" stroke="currentColor"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g fill="inherit" stroke="currentColor"/></svg>',
        ];

        yield 'Keeps animation attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName="x" from="0" to="1" dur="1s" begin="0s" repeatCount="indefinite" fill="freeze"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect><animate attributeName="x" from="0" to="1" dur="1s" begin="0s" repeatCount="indefinite" fill="freeze"/></rect></svg>',
        ];

        yield 'Removes an unknown attribute only after a known one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1" foo="2" y="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect x="1" y="3"/></svg>',
        ];

        yield 'Handles an empty document' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"/>',
        ];

        yield 'Handles a root with only text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">text</svg>',
        ];

        yield 'Handles a 60 levels deep structure' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect bogus="1"/>' . str_repeat('</g>', 60) . '</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'Handles a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '"/></svg>',
        ];

        yield 'Handles right-to-left, emoji, combining and astral text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text bogus="1">שלום 😀 é 𝒜</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Handles values padded with tabs, newlines and no-break spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect bogus="1" fill="&#9; red&#10;&#xA0;"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"><rect fill=\"&#9; red&#10;\u{a0}\"/></svg>",
        ];

        yield 'Handles comments between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect bogus="1"/><!--4--></g><!--5--></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect/><!--4--></g><!--5--></svg>',
        ];

        yield 'Leaves a document with an external style sheet untouched' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect bogus="1"/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect bogus="1"/></svg>',
        ];

        yield 'Leaves a document with a linked style sheet untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><rect bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><h:link xmlns:h="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/><rect bogus="1"/></svg>',
        ];

        yield 'Leaves a document with an imported style sheet untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><rect bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><rect bogus="1"/></svg>',
        ];

        yield 'Handles processing instructions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><?foo bar?><rect bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><?foo bar?><rect/></svg>',
        ];

        yield 'Keeps attributes that a style sheet selects' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><style>[foo]{fill:red} [*|bar]{fill:blue}</style><rect foo="1" x:bar="2" baz="3" x:baz="4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><style>[foo]{fill:red} [*|bar]{fill:blue}</style><rect foo="1" x:bar="2"/></svg>',
        ];

        yield 'Keeps SVG 2 and HTML global attributes that apply to SVG elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style nonce="n">rect{fill:red}</style><rect part="icon" slot="s" aria-description="d" aria-braillelabel="b" aria-brailleroledescription="r" aria-colindextext="c" aria-rowindextext="w"/><foo nonce="n" part="p" slot="s" aria-description="d"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style nonce="n">rect{fill:red}</style><rect part="icon" slot="s" aria-description="d" aria-braillelabel="b" aria-brailleroledescription="r" aria-colindextext="c" aria-rowindextext="w"/><foo nonce="n" part="p" slot="s" aria-description="d"/></svg>',
        ];

        yield 'Handles CDATA sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text bogus="1"><![CDATA[a<b&c]]></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>a&lt;b&amp;c</text></svg>',
        ];

        yield 'Handles a child that resets the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect bogus="1"/></x></g><rect bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect bogus="1"/></x></g><rect/></svg>',
        ];

        yield 'Handles a prefix redeclared to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect bogus="1"/></s:g><g xmlns:s="urn:other"><s:rect bogus="1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect/></s:g><g xmlns:s="urn:other"><s:rect bogus="1"/></g></svg>',
        ];

        yield 'Handles entities and character references in values and text' => [
            '<!DOCTYPE svg [<!ENTITY e "v&#x41;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text bogus="1" class="&e;">&e;&#x42;&#66;</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text class="vA">vABB</text></svg>',
        ];

        yield 'Handles an attribute in no namespace next to the same local name in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c" bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b"/></svg>',
        ];

        yield 'Handles repeated elements where only a later one matches' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect bogus="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect/></svg>',
        ];

        yield 'Handles mixed-case element names' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect bogus="1"/><RECT bogus="1"/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect/><RECT/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
        ];

        yield 'Keeps event attributes in any case and removes them in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" ONCLICK="a()" OnLoad="b()" x:onclick="c()"><rect onfocusin="d()" x:onload="e()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" ONCLICK="a()" OnLoad="b()"><rect onfocusin="d()"/></svg>',
        ];
    }

    #[Test]
    public function handlesManyElementsWithAttributesInLinearTime(): void
    {
        $small = $this->secondsToOptimize(5_000);
        $large = $this->secondsToOptimize(20_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    #[Test]
    public function handlesManyNamespaceDeclarationsInLinearTime(): void
    {
        $small = $this->secondsToOptimizeNamespaces(2_000);
        $large = $this->secondsToOptimizeNamespaces(16_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimizeNamespaces(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><rect foo="x"/></svg>');

        $start = hrtime(true);
        (new RemoveNonStandardAttributes())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><defs>' . implode('', array_map(static fn (int $i): string => '<linearGradient id="g' . $i . '"><stop offset="0" stop-color="#' . $i . '"/></linearGradient>', range(1, $count))) . '</defs>' . implode('', array_map(static fn (int $i): string => '<rect fill="url(#g' . $i . ')"/>', range(1, $count))) . '</svg>');

        $start = hrtime(true);
        (new RemoveNonStandardAttributes())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
