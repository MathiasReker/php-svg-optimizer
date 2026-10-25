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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardTags;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveNonStandardTags::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveNonStandardTagsTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveNonStandardTags());

        $actual = $svgOptimizer->allowRisky()->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps the XHTML content of a foreignObject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><div xmlns="http://www.w3.org/1999/xhtml"><p>a</p><input type="checkbox" checked="checked"/></div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><div xmlns="http://www.w3.org/1999/xhtml"><p>a</p><input type="checkbox" checked="checked"/></div></foreignObject></svg>',
        ];

        yield 'Keeps unknown elements inside a foreignObject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><foo><rect width="1" height="1"/></foo></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><foo><rect width="1" height="1"/></foo></foreignObject></svg>',
        ];

        yield 'Keeps elements in another namespace, whose children do not render' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:foo><rect width="10" height="10"/></x:foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><x:foo><rect width="10" height="10"/></x:foo></svg>',
        ];

        yield 'Removes single unknown tag' => [
            '<svg><unknown/></svg>',
            '<svg/>',
        ];

        yield 'Removes nested unknown tags with their content' => [
            '<svg><g><foo><rect width="10" height="10"/></foo></g></svg>',
            '<svg><g/></svg>',
        ];

        yield 'Removes multiple unknown tags at root' => [
            '<svg><bad/><worse/><rect width="5" height="5"/></svg>',
            '<svg><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps standard tags intact' => [
            '<svg><rect width="10" height="10"/><circle cx="5" cy="5" r="3"/></svg>',
            '<svg><rect width="10" height="10"/><circle cx="5" cy="5" r="3"/></svg>',
        ];

        yield 'Keep dangerous tags' => [
            '<svg><script>alert(1)</script><rect width="5" height="5"/></svg>',
            '<svg><script>alert(1)</script><rect width="5" height="5"/></svg>',
        ];

        yield 'Removes unknown tags mixed with standard tags' => [
            '<svg><rect width="10" height="10"/><foo/><circle cx="5" cy="5" r="3"/></svg>',
            '<svg><rect width="10" height="10"/><circle cx="5" cy="5" r="3"/></svg>',
        ];

        yield 'Handles empty SVG' => [
            '<svg></svg>',
            '<svg/>',
        ];

        yield 'Removes unknown tags in defs' => [
            '<svg><defs><bad><rect width="5" height="5"/></bad></defs></svg>',
            '<svg><defs/></svg>',
        ];

        yield 'Removes multiple nested unknown tags' => [
            '<svg><g><foo><bar><rect width="10" height="10"/></bar></foo></g></svg>',
            '<svg><g/></svg>',
        ];

        yield 'Ignores text nodes' => [
            '<svg>Hello<rect width="5" height="5"/>World</svg>',
            '<svg>Hello<rect width="5" height="5"/>World</svg>',
        ];

        yield 'Keeps tags with different casing' => [
            '<svg><Rect width="10" height="10"/><CiRcLe cx="5" cy="5" r="3"/></svg>',
            '<svg><Rect width="10" height="10"/><CiRcLe cx="5" cy="5" r="3"/></svg>',
        ];

        yield 'Removes an unknown prefixed tag with its children' => [
            '<svg><custom:foo><rect width="10" height="10"/></custom:foo></svg>',
            '<svg/>',
        ];

        yield 'Keeps standard namespaced svg tag if normalized matches' => [
            '<svg><svg:rect width="10" height="10"/></svg>',
            '<svg><svg:rect width="10" height="10"/></svg>',
        ];

        yield 'Removes an unknown tag at root level with its children' => [
            '<svg><foo><g><rect width="10" height="10"/></g></foo></svg>',
            '<svg/>',
        ];

        yield 'Preserves sibling order when removing unknown tags' => [
            '<svg><rect id="a"/><foo/><rect id="b"/></svg>',
            '<svg><rect id="a"/><rect id="b"/></svg>',
        ];

        yield 'Removes multiple unknown sibling tags with children' => [
            '<svg><foo><rect width="1"/></foo><bar><rect width="2"/></bar></svg>',
            '<svg/>',
        ];

        yield 'Keeps comments untouched' => [
            '<svg><!-- comment --><rect width="5" height="5"/></svg>',
            '<svg><!-- comment --><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps CDATA sections' => [
            '<svg><![CDATA[some text]]><rect width="5" height="5"/></svg>',
            '<svg>some text<rect width="5" height="5"/></svg>',
        ];

        yield 'Removes deeply nested unknown tags with mixed content' => [
            '<svg><g>Text<foo>More<bar><rect width="5"/></bar></foo>End</g></svg>',
            '<svg><g>TextEnd</g></svg>',
        ];

        yield 'Does not remove svg root element' => [
            '<svg></svg>',
            '<svg/>',
        ];

        yield 'Keeps dangerous but valid tags consistently' => [
            '<svg><script>alert(1)</script><style>rect{fill:red}</style></svg>',
            '<svg><script>alert(1)</script><style>rect{fill:red}</style></svg>',
        ];

        yield 'Removes an unknown tag with its text' => [
            '<svg><foo>hello world</foo></svg>',
            '<svg/>',
        ];

        yield 'Keeps an unknown tag whose content has an id' => [
            '<svg><defs><foo><linearGradient id="g"/></foo></defs></svg>',
            '<svg><defs><foo><linearGradient id="g"/></foo></defs></svg>',
        ];

        yield 'Keeps an unknown tag with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foo id="a"><rect/></foo><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foo id="a"><rect/></foo><use href="#a"/></svg>',
        ];

        yield 'Keeps an unknown tag whose content has an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foo><rect xml:id="a"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foo><rect xml:id="a"/></foo></svg>',
        ];

        yield 'Keeps an unknown tag that contains a style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foo><style>rect{fill:red}</style></foo><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foo><style>rect{fill:red}</style></foo><rect/></svg>',
        ];

        yield 'Keeps an unknown tag that contains a script' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foo><script>alert(1)</script></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foo><script>alert(1)</script></foo></svg>',
        ];

        yield 'Keeps an unknown tag that contains an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><foo><animate href="#r" attributeName="x" to="5"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><foo><animate href="#r" attributeName="x" to="5"/></foo></svg>',
        ];

        yield 'Keeps an unknown element that has an effect itself' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><font-face><foo/></font-face></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><font-face/></svg>',
        ];

        yield 'Keeps an unknown child of a switch, which the switch may render instead of a later child' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><foo/><rect width="5" height="5"/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><foo/><rect width="5" height="5"/></switch></svg>',
        ];

        yield 'Removes an unknown element nested in a child of a switch' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g><foo/></g><rect width="5" height="5"/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><g/><rect width="5" height="5"/></switch></svg>',
        ];

        yield 'Removes a later unknown element after a kept one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foo id="a"/><bar><rect/></bar></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foo id="a"/></svg>',
        ];

        yield 'Removes mixed case unknown tag' => [
            '<svg><UnKnOwN/></svg>',
            '<svg/>',
        ];

        yield 'Removes tag with special characters' => [
            '<svg><custom-tag_1.2/></svg>',
            '<svg/>',
        ];

        yield 'Keeps the discard animation element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><discard href="#r" begin="1s"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><discard href="#r" begin="1s"/></svg>',
        ];

        yield 'Removes unknown elements in a document with a prefixed SVG namespace' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:foo><svg:rect/></svg:foo></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Removes deeply nested unknown elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a1><a2><a3><rect/></a3></a2></a1></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps known elements written in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><RECT/><Rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><RECT/><Rect/></svg>',
        ];

        yield 'Removes an unknown element after a known sibling' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps elements of other namespaces and their content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:y xmlns:x="urn:x"><x:z/></x:y></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:y xmlns:x="urn:x"><x:z/></x:y></svg>',
        ];

        yield 'Removes the text of an unknown element, which does not render' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text><foo>日本 😀</foo></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text/></svg>',
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
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect /><foo><circle r="1"/></foo>' . str_repeat('</g>', 60) . '</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'Handles a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" /><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '"/></svg>',
        ];

        yield 'Handles right-to-left, emoji, combining and astral text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text >שלום 😀 é 𝒜</text><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Handles values padded with tabs, newlines and no-break spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect  fill="&#9; red&#10;&#xA0;"/><foo><circle r="1"/></foo></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"><rect fill=\"&#9; red&#10;\u{a0}\"/></svg>",
        ];

        yield 'Handles comments between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><foo><circle r="1"/></foo><!--3--><rect /><!--4--></g><!--5--></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect/><!--4--></g><!--5--></svg>',
        ];

        yield 'Leaves a document with an external style sheet untouched' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect /><foo><circle r="1"/></foo></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect/><foo><circle r="1"/></foo></svg>',
        ];

        yield 'Handles processing instructions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><?foo bar?><rect /><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><?foo bar?><rect/></svg>',
        ];

        yield 'Keeps unknown elements when a style sheet depends on the siblings of an element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><g><foo/><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><g><foo/><rect/></g></svg>',
        ];

        yield 'Keeps unknown elements when a style sheet uses :has()' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g:has(foo) rect{fill:red}</style><g><foo/><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g:has(foo) rect{fill:red}</style><g><foo/><rect/></g></svg>',
        ];

        yield 'Removes unknown elements when a style sheet does not depend on siblings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><g><foo/><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><g><rect/></g></svg>',
        ];

        yield 'Handles CDATA sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text ><![CDATA[a<b&c]]></text><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>a&lt;b&amp;c</text></svg>',
        ];

        yield 'Handles a child that resets the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect /><foo><circle r="1"/></foo></x></g><rect /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect/><foo><circle r="1"/></foo></x></g><rect/></svg>',
        ];

        yield 'Handles a prefix redeclared to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect /></s:g><g xmlns:s="urn:other"><s:rect /></g><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect/></s:g><g xmlns:s="urn:other"><s:rect/></g></svg>',
        ];

        yield 'Handles entities and character references in values and text' => [
            '<!DOCTYPE svg [<!ENTITY e "v&#x41;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text  class="&e;">&e;&#x42;&#66;</text><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text class="vA">vABB</text></svg>',
        ];

        yield 'Handles an attribute in no namespace next to the same local name in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c" /><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c"/></svg>',
        ];

        yield 'Handles repeated elements where only a later one matches' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect /><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect/></svg>',
        ];

        yield 'Keeps known elements with a non-ASCII prefix' => [
            '<é:svg xmlns:é="http://www.w3.org/2000/svg"><é:rect/><é:foo/></é:svg>',
            '<é:svg xmlns:é="http://www.w3.org/2000/svg"><é:rect/></é:svg>',
        ];

        yield 'Keeps an element whose name folds to a known element name' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><mas\u{212A}/><foo/></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><mas\u{212A}/></svg>",
        ];

        yield 'Handles mixed-case element names' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect /><RECT /><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script><foo><circle r="1"/></foo></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect/><RECT/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
        ];
    }

    #[Test]
    public function optimizeKeepsAnUnknownRootElement(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<foo xmlns="http://www.w3.org/2000/svg"><bar/></foo>');

        (new RemoveNonStandardTags())->optimize($domDocument);

        self::assertSame('<foo xmlns="http://www.w3.org/2000/svg"/>', $domDocument->saveXML($domDocument->documentElement));
    }

    #[Test]
    public function optimizeHandlesADocumentWithoutRootElement(): void
    {
        $domDocument = new \DOMDocument();

        (new RemoveNonStandardTags())->optimize($domDocument);

        self::assertNull($domDocument->documentElement);
    }

    #[Test]
    public function ruleIsMarkedAsRisky(): void
    {
        self::assertFalse(RemoveNonStandardTags::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveNonStandardTags::shouldCheckSize());
    }
}
