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
use MathiasReker\PhpSvgOptimizer\Pass\Pipeline;
use MathiasReker\PhpSvgOptimizer\Pass\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementRemovalTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveMetadata::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(ElementRemovalTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class RemoveMetadataTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideRemovesMetadataCases')]
    #[Test]
    public function removesMetadata(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveMetadata()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideRemovesMetadataCases(): iterable
    {
        yield 'Keeps metadata that a switch may render' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><metadata/><rect width="5" height="5"/></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><switch><metadata/><rect width="5" height="5"/></switch></svg>',
        ];

        yield 'Keeps metadata when a style sheet depends on siblings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect+rect{fill:red}</style><rect/><metadata/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect+rect{fill:red}</style><rect/><metadata/><rect/></svg>',
        ];

        yield 'Keeps metadata in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><metadata xmlns="http://www.w3.org/1999/xhtml">m</metadata></foreignObject><metadata/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><metadata xmlns="http://www.w3.org/1999/xhtml">m</metadata></foreignObject></svg>',
        ];

        yield 'Keeps metadata that contains an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><metadata><set href="#r" attributeName="x" to="1"/></metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><metadata><set href="#r" attributeName="x" to="1"/></metadata></svg>',
        ];

        yield 'Removes single <metadata> tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>This is some metadata</metadata>
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Removes multiple <metadata> tags' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>This is some metadata</metadata>
                    <metadata>This is more metadata</metadata>
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/></svg>
                XML,
        ];

        yield 'Removes nested elements inside <metadata>' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata><data>Some nested data</data></metadata>
                    <rect width="100" height="100" fill="green"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="green"/></svg>
                XML,
        ];

        yield 'No <metadata> tag present' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="yellow"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="yellow"/></svg>
                XML,
        ];

        yield 'No <metadata> tag, only <title> and <desc>' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <title>SVG Title</title>
                    <desc>SVG Description</desc>
                    <rect width="100" height="100" fill="orange"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><title>SVG Title</title><desc>SVG Description</desc><rect width="100" height="100" fill="orange"/></svg>
                XML,
        ];

        yield 'Removes <metadata> from different layers' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <defs>
                        <metadata>Metadata in defs</metadata>
                    </defs>
                    <g>
                        <metadata>Metadata in g</metadata>
                        <rect width="100" height="100" fill="pink"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><defs/><g><rect width="100" height="100" fill="pink"/></g></svg>
                XML,
        ];

        yield 'Keeps non-metadata content intact' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>This is metadata</metadata>
                    <title>SVG Title</title>
                    <desc>SVG Description</desc>
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><title>SVG Title</title><desc>SVG Description</desc><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Handles complex SVG structure' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>Metadata 1</metadata>
                    <defs>
                        <metadata>Metadata 2</metadata>
                    </defs>
                    <g>
                        <metadata>Metadata 3</metadata>
                        <rect width="100" height="100" fill="purple"/>
                    </g>
                    <metadata>Metadata 4</metadata>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><defs/><g><rect width="100" height="100" fill="purple"/></g></svg>
                XML,
        ];

        yield 'Well-formed XML after removal' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>Some metadata</metadata>
                    <rect width="100" height="100" fill="black"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="black"/></svg>
                XML,
        ];

        yield 'Empty <metadata> tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata></metadata>
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Self-closing <metadata> tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata />
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'SVG without xmlns attribute' => [
            <<<'XML'
                <svg width="100" height="100">
                    <metadata>Some metadata</metadata>
                    <rect width="100" height="100" fill="green"/>
                </svg>
                XML,
            <<<'XML'
                <svg width="100" height="100"><rect width="100" height="100" fill="green"/></svg>
                XML,
        ];

        yield 'Special characters in <metadata>' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>Special characters: &amp; &lt; &gt;</metadata>
                    <rect width="100" height="100" fill="yellow"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="yellow"/></svg>
                XML,
        ];

        yield 'Large <metadata> content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec vel libero ac metus venenatis gravida.
                    </metadata>
                    <rect width="100" height="100" fill="pink"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="pink"/></svg>
                XML,
        ];

        yield 'Nested <metadata> with other elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>
                        <g><rect width="50" height="50" fill="blue"/></g>
                    </metadata>
                    <rect width="100" height="100" fill="purple"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="purple"/></svg>
                XML,
        ];

        yield 'Removes nested metadata elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><metadata><metadata/></metadata><g><metadata>m</metadata></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Removes metadata in a document with a prefixed SVG namespace' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:metadata>m</svg:metadata><svg:rect/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect/></svg:svg>',
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
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect /><metadata>m</metadata>' . str_repeat('</g>', 60) . '</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'Handles a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" /><metadata>m</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '"/></svg>',
        ];

        yield 'Handles right-to-left, emoji, combining and astral text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text >שלום 😀 é 𝒜</text><metadata>m</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Handles values padded with tabs, newlines and no-break spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect  fill="&#9; red&#10;&#xA0;"/><metadata>m</metadata></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"><rect fill=\"&#9; red&#10;\u{a0}\"/></svg>",
        ];

        yield 'Handles comments between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><metadata>m</metadata><!--3--><rect /><!--4--></g><!--5--></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!--3--><rect/><!--4--></g><!--5--></svg>',
        ];

        yield 'Leaves the document untouched with an external style sheet' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect /><metadata>m</metadata></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect/><metadata>m</metadata></svg>',
        ];

        yield 'Handles CDATA sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text ><![CDATA[a<b&c]]></text><metadata>m</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>a&lt;b&amp;c</text></svg>',
        ];

        yield 'Keeps elements in an element reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect /><metadata>m</metadata></x></g><rect /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect/><metadata>m</metadata></x></g><rect/></svg>',
        ];

        yield 'Handles a prefix redeclared to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect /></s:g><g xmlns:s="urn:other"><s:rect /></g><metadata>m</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect/></s:g><g xmlns:s="urn:other"><s:rect/></g></svg>',
        ];

        yield 'Handles entities and character references in values and text' => [
            '<!DOCTYPE svg [<!ENTITY e "v&#x41;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text  class="&e;">&e;&#x42;&#66;</text><metadata>m</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text class="vA">vABB</text></svg>',
        ];

        yield 'Handles an attribute in no namespace next to the same local name in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c" /><metadata>m</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c"/></svg>',
        ];

        yield 'Handles repeated elements where only a later one matches' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect /><metadata>m</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect/></svg>',
        ];

        yield 'Handles mixed-case element names' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect /><RECT /><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script><metadata>m</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect/><RECT/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
        ];

        yield 'Keeps metadata elements written in another case and removes a prefixed one' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><METADATA>a</METADATA><Metadata>b</Metadata><s:metadata xmlns:s="http://www.w3.org/2000/svg">c</s:metadata><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><METADATA>a</METADATA><Metadata>b</Metadata><rect/></svg>',
        ];

        yield 'Keeps metadata with a descendant that can be referenced by id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><metadata id="m"><g><rect id="r"/></g></metadata><metadata id="n"><g/></metadata><metadata><g xml:id="x"/></metadata><use href="#r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><metadata id="m"><g><rect id="r"/></g></metadata><metadata><g xml:id="x"/></metadata><use href="#r"/></svg>',
        ];
    }
}
