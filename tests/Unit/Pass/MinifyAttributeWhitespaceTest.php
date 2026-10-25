<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Pass;

use MathiasReker\PhpSvgOptimizer\Exception\InvalidSvgException;
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Pass\MinifyAttributeWhitespace;
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\RegexFailureTrait;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifyAttributeWhitespace::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeNodeTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class MinifyAttributeWhitespaceTest extends TestCase
{
    use RegexFailureTrait;

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new MinifyAttributeWhitespace()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);

        $repeated = new Pipeline([new MinifyAttributeWhitespace()]);
        self::assertSame($actual, $repeated->run($actual));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Trims attributes whose names hold characters outside the basic multilingual plane, keeping ampersands' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x\u{1F600}=\"urn:x\"><rect x\u{1F600}:a\u{1F600}=\" a &amp; b \" b\u{1F600}=\"  c  \"/></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:x\u{1F600}=\"urn:x\"><rect x\u{1F600}:a\u{1F600}=\"a &amp; b\" b\u{1F600}=\"c\"/></svg>",
        ];
        yield 'Keeps the whitespace of event handlers' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="var a=1&#10;f()"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="var a=1&#10;f()"/>',
        ];

        yield 'Keeps the whitespace of ids, targets and animated attribute names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a id=" g" target=" _blank"/><set attributeName=" fill " to="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a id=" g" target=" _blank"/><set attributeName=" fill " to="red"/></svg>',
        ];

        yield 'Keeps the whitespace of filter primitive names, which are not trimmed' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><filter><feFlood result=" a"/><feBlend in=" a" in2="b " x=" 1"/></filter></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><filter><feFlood result=" a"/><feBlend in=" a" in2="b " x="1"/></filter></svg>',
        ];

        yield 'Keeps the whitespace of languages, which the lang pseudo-class compares untrimmed' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:lang(en){fill:red}</style><rect lang=" en" width=" 1"/><rect xml:lang="en "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:lang(en){fill:red}</style><rect lang=" en" width="1"/><rect xml:lang="en "/></svg>',
        ];

        yield 'Keeps a style with an escape' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:A\ ;  fill:red">t</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:A\ ;  fill:red">t</text></svg>',
        ];

        yield 'Leaves the document untouched when a style sheet selects attribute values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[class="a  b"]{fill:red}</style><rect class="a  b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[class="a  b"]{fill:red}</style><rect class="a  b"/></svg>',
        ];

        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps non-breaking spaces in attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect class="&#xA0;a&#xA0;&#xA0;b&#xA0;" width="5" height="5"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect class=\"\u{A0}a\u{A0}\u{A0}b\u{A0}\" width=\"5\" height=\"5\"/></svg>",
        ];

        yield 'collapses the whitespace of a mixed-case attribute, which is not a data attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect DATA-x="  a  " width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect DATA-x="a" width="5" height="5"/></svg>',
        ];

        yield 'collapses whitespace with entities' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect aria-label="  &amp;   &lt;  " width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect aria-label="&amp; &lt;" width="5" height="5"/></svg>',
        ];

        yield 'keeps whitespace in an element reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect class=" a  b " width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect class=" a  b " width="5" height="5"/></g></svg>',
        ];

        yield 'keeps a style with an escape untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family : \'a  \\\'  b\' ;">t</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family : \'a  \\\'  b\' ;">t</text></svg>',
        ];

        yield 'keeps the whitespace of attributes in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><input xmlns="http://www.w3.org/1999/xhtml" value="a   b" title=" l1&#10;l2 "/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><input xmlns="http://www.w3.org/1999/xhtml" value="a   b" title=" l1&#10;l2 "/></foreignObject></svg>',
        ];

        yield 'keeps the whitespace of attributes of svg elements inside a foreign object' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><rect class=" a  b " width="5" height="5"/></svg></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><rect class=" a  b " width="5" height="5"/></svg></foreignObject></svg>',
        ];

        yield 'keeps the whitespace of the character of a glyph' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><font><glyph unicode=" " horiz-adv-x="250"/></font></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><font><glyph unicode=" " horiz-adv-x="250"/></font></svg>',
        ];

        yield 'keeps the whitespace of links and downloads' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"><a href="a  b.svg" download=" c  d.svg"><use x:href=" #i "/></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"><a href="a  b.svg" download=" c  d.svg"><use x:href=" #i "/></a></svg>',
        ];

        yield 'keeps the whitespace of data attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect data-label="  a  b  " width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect data-label="  a  b  " width="5" height="5"/></svg>',
        ];

        yield 'keeps the whitespace of quoted strings in attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text font-family="  \'Open  Sans\',  serif ">t</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text font-family="\'Open  Sans\', serif">t</text></svg>',
        ];

        yield 'collapses whitespace in attributes of a prefixed svg element' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect class="  a   b " width="5" height="5"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect class="a b" width="5" height="5"/></svg:svg>',
        ];

        yield 'collapses whitespace with multibyte characters' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect aria-label="  &#xE6;  &#x65E5;&#x672C;  " width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect aria-label="æ 日本" width="5" height="5"/></svg>',
        ];

        yield 'collapses whitespace of an attribute after a verbatim one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect data-x=" a " class=" b " width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect data-x=" a " class="b" width="5" height="5"/></svg>',
        ];

        yield 'collapses whitespace of attributes sharing a local name in different namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect a:class=" b " class=" c " width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect a:class="b" class="c" width="5" height="5"/></svg>',
        ];

        yield 'Keeps text that looks like an attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">x="a      b"</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">x="a      b"</text></svg>',
        ];

        yield 'Keeps comments that look like an attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- x="a   b" --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- x="a   b" --></svg>',
        ];

        yield 'Keeps the whitespace of titles, which tooltips show' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="  a &amp;  &lt;b&gt;   &quot;c&quot; " title=" x&#10; y"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="  a &amp;  &lt;b&gt;   &quot;c&quot; " title=" x&#10; y"/></svg>',
        ];

        yield 'Keeps spaces between values in a style declaration' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="stroke-dasharray: 1  2 ; enable-background: new 0 0 400 220" width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="stroke-dasharray:1 2;enable-background:new 0 0 400 220" width="1" height="1"/></svg>',
        ];

        yield 'Keeps spaces in unquoted font family names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family: Arial Black , sans-serif;">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:Arial Black,sans-serif">a</text></svg>',
        ];

        yield 'Keeps quoted strings in a style unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family: \'Times  New ; Roman\' , serif">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="font-family:\'Times  New ; Roman\',serif">a</text></svg>',
        ];

        yield 'Keeps spaces inside functions and before important' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill: rgb(1 , 2 , 3) !important; transform: translate(1px 2px)" width="1" height="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:rgb(1,2,3) !important;transform:translate(1px 2px)" width="1" height="1"/></svg>',
        ];

        yield 'Removes Unnecessary Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">

                    <circle cx="50" cy="50" r="20" fill="red"/>

                    <rect x="10" y="10" width="30" height="30" fill="blue"/>

                    <!-- A comment -->
                    <text x="20" y="20"> Hello World </text>

                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/><!-- A comment --><text x="20" y="20"> Hello World </text></svg>
                XML,
        ];

        yield 'Only Whitespace and Comments' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">

                    <!-- A comment -->

                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><!-- A comment --></svg>
                XML,
        ];

        yield 'Multiple Elements and Mixed Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <circle cx="100" cy="100" r="50" fill="green" />

                    <!-- Comment -->

                    <rect x="20" y="20" width="100" height="100" fill="yellow"/>

                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><circle cx="100" cy="100" r="50" fill="green"/><!-- Comment --><rect x="20" y="20" width="100" height="100" fill="yellow"/></svg>
                XML,
        ];

        yield 'Whitespace in Attribute Names' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle  cx="50" cy="50" r="20" fill="red"/>
                    <rect  x="10"  y="10" width="30" height="30" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Attributes with No Value' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red" />
                    <rect x="10" y="10" width="30" height="30" fill="blue" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Mixed Whitespace Types in Attributes' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" \tcy="50" r="20" fill="red"/>
                    <rect x="10" \ny="10" width="30" height="30" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Attributes with Embedded Newlines' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="
                    red"/>
                    <rect x="10" y="10" width="30" height="30" fill="
                    blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Preserving Whitespace in Text Nodes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <text x="10" y="10">   Preserve   This   </text>
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><text x="10" y="10"> Preserve This </text><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Style Attributes with Complex Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" style=" fill : red ; stroke : black ; stroke-width : 2 ;"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" style="fill:red;stroke:black;stroke-width:2"/></svg>
                XML,
        ];

        yield 'Minimal SVG Content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Well-Formatted SVG' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Remove whitespace around attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox=" 0 0  150 100 " width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 100" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Handles Tabs, Spaces, and Newlines' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx=" 50 " cy="50" r="20" fill="red" />
                    <rect x="10" y="10" width="30" height="30" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Inline Style Attributes with Unnecessary Spaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" style=" fill : red ; stroke : black ; stroke-width : 2 ; "/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" style="fill:red;stroke:black;stroke-width:2"/></svg>
                XML,
        ];

        yield 'Mixed Content with Text Nodes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <text x="10" y="10">   Some   Text   Here   </text>
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><text x="10" y="10"> Some Text Here </text><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Self-Closing Tags with Spaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <path d="M10 10 h 80 v 80 h -80 Z" fill="none" stroke="black" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><path d="M10 10 h 80 v 80 h -80 Z" fill="none" stroke="black"/></svg>
                XML,
        ];

        yield 'Nested Elements with Varying Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g transform="translate(10 10)">
                        <circle cx="40" cy="40" r="20" fill="green"/>
                        <rect x="20" y="20" width="40" height="40" fill="yellow"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g transform="translate(10 10)"><circle cx="40" cy="40" r="20" fill="green"/><rect x="20" y="20" width="40" height="40" fill="yellow"/></g></svg>
                XML,
        ];

        yield 'Handles Nested Elements with Mixed Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150">
                    <g transform="translate(20 20)">
                        <circle cx="50" cy="50" r="30" fill="blue" />
                        <g>
                            <rect x="40" y="40" width="50" height="50" fill="yellow" />
                        </g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150"><g transform="translate(20 20)"><circle cx="50" cy="50" r="30" fill="blue"/><g><rect x="40" y="40" width="50" height="50" fill="yellow"/></g></g></svg>
                XML,
        ];

        yield 'Preserves CDATA Sections' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <script><![CDATA[
                        console.log("This is a script");
                    ]]></script>
                </svg>
                XML,
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg"><script>
                        console.log("This is a script");
                    </script></svg>
                EOD,
        ];

        yield 'Handles Mixed Content and Whitespace in SVG' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <text x="10" y="10">  Keep This Text   </text>
                    <g>
                        <circle cx="50" cy="50" r="25" fill="red" />
                        <rect x="30" y="30" width="40" height="40" fill="green" />
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><text x="10" y="10"> Keep This Text </text><g><circle cx="50" cy="50" r="25" fill="red"/><rect x="30" y="30" width="40" height="40" fill="green"/></g></svg>
                XML,
        ];

        yield 'Handles Whitespace in Complex Path Data' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <path d="M 10 10 C 20 20, 40 20, 50 10 S 70 10, 80 10" fill="none" stroke="black"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><path d="M 10 10 C 20 20, 40 20, 50 10 S 70 10, 80 10" fill="none" stroke="black"/></svg>
                XML,
        ];

        yield 'Removes Unnecessary Whitespace in Grouped Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="300" height="300">
                    <g>
                        <circle cx="50" cy="50" r="30" fill="orange" />
                        <rect x="70" y="70" width="60" height="60" fill="blue" />
                    </g>
                    <g>
                        <ellipse cx="150" cy="150" rx="40" ry="20" fill="pink" />
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><g><circle cx="50" cy="50" r="30" fill="orange"/><rect x="70" y="70" width="60" height="60" fill="blue"/></g><g><ellipse cx="150" cy="150" rx="40" ry="20" fill="pink"/></g></svg>
                XML,
        ];

        yield 'Handles Inline Styles in Nested Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150">
                    <g style="stroke: black; fill: none;">
                        <circle cx="75" cy="75" r="50" style="stroke-width: 2;"/>
                        <rect x="50" y="50" width="50" height="50" style="fill: red;"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150"><g style="stroke:black;fill:none"><circle cx="75" cy="75" r="50" style="stroke-width:2"/><rect x="50" y="50" width="50" height="50" style="fill:red"/></g></svg>
                XML,
        ];

        yield 'Whitespace After Self-Closing Tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" fill="red"  />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/></svg>
                XML,
        ];

        yield 'Multiple Lines in Tags' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="20" fill="red" />
                    <rect x="10" y="10" width="30" height="30" fill="blue" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Text with Extra Whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text x="10" y="10">   Some   Text   </text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text x="10" y="10"> Some Text </text></svg>
                XML,
        ];

        yield 'Attributes with Excess Whitespace Around Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle  cx="50" cy="50" r="20" fill=" red " />
                    <rect  x=" 10 "  y="10 " width="30" height=" 30 " fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" fill="red"/><rect x="10" y="10" width="30" height="30" fill="blue"/></svg>
                XML,
        ];

        yield 'Complex Inline Style with Multiple Spaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20" style=" fill : red ; stroke : black ; stroke-width : 2 ;" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20" style="fill:red;stroke:black;stroke-width:2"/></svg>
                XML,
        ];

        yield 'Space between tags' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle cx="50" cy="50" r="20"/>     <circle cx="50" cy="50" r="20"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="20"/><circle cx="50" cy="50" r="20"/></svg>
                XML,
        ];
    }

    #[RunInSeparateProcess]
    #[Test]
    public function keepsAStyleThatCannotBeMatched(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill : red ;  font-family: &quot;a  b&quot;"/></svg>');

        $this->withFailingRegularExpressions(static fn () => (new MinifyAttributeWhitespace())->apply($domDocument));

        self::assertSame('fill : red ;  font-family: "a  b"', $domDocument->documentElement?->firstElementChild?->getAttribute('style'));
    }
}
