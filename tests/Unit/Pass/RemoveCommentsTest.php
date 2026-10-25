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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Xml\Parser;
use MathiasReker\PhpSvgOptimizer\Xml\Serializer;
use MathiasReker\PhpSvgOptimizer\Xml\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveComments::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveCommentsTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveComments()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Removes Single Comment' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- This is a comment -->
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes Multiple Comments' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- First comment -->
                    <rect x="10" y="10" width="30" height="30"/>
                    <!-- Second comment -->
                    <circle cx="50" cy="50" r="20"/>
                    <!-- Third comment -->
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML,
        ];

        yield 'Handles No Comments' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30"/>
                    <circle cx="50" cy="50" r="20"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML,
        ];

        yield 'Handles Empty Document' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Removes Comments in <defs> Element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <defs>
                        <!-- Gradient Definition -->
                        <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1" />
                            <stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <rect x="10" y="10" width="30" height="30" fill="url(#grad1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><defs><linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1"/><stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1"/></linearGradient></defs><rect x="10" y="10" width="30" height="30" fill="url(#grad1)"/></svg>
                XML,
        ];

        yield 'Removes Comments in Nested Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <!-- Group Comment -->
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Removes Comments with Special Characters' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- Special characters: !@#$%^&*() -->
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Handles Comments with XML Special Characters' => [
            <<<'EOD'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- Comment with XML special characters: & < > -->
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                EOD,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Handles Comments with Inline SVG Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <defs>
                        <!-- Inline SVG Comment -->
                        <linearGradient id="grad1">
                            <stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1"/>
                            <stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1"/>
                        </linearGradient>
                    </defs>
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><defs><linearGradient id="grad1"><stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1"/><stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1"/></linearGradient></defs><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Handles Complex SVG Document' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- Outer comment -->
                    <g>
                        <rect x="10" y="10" width="30" height="30"/>
                        <!-- Inner comment -->
                        <circle cx="50" cy="50" r="20"/>
                        <defs>
                            <!-- Comment in defs -->
                            <linearGradient id="grad1">
                                <stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1"/>
                                <stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1"/>
                            </linearGradient>
                        </defs>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/><defs><linearGradient id="grad1"><stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1"/><stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1"/></linearGradient></defs></g></svg>
                XML,
        ];

        yield 'Comment before svg tag' => [
            <<<'XML'
                <?xml version="1.0" encoding="utf-8"?>
                <!-- comment -->
                <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
                <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                    width="500px" height="50px" viewBox="0 0 500 50" enable-background="new 0 0 500 50" xml:space="preserve">
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" width="500px" height="50px" viewBox="0 0 500 50" enable-background="new 0 0 500 50" xml:space="preserve"/>
                XML,
        ];

        yield 'Preserves License Comment' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!--! This is a license comment -->
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><!--! This is a license comment --><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Removes Normal Comment But Preserves License Comment' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- Normal comment -->
                    <!--! License comment -->
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><!--! License comment --><rect x="10" y="10" width="30" height="30"/></svg>
                XML,
        ];

        yield 'Preserves Multiple License Comments' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!--! License comment 1 -->
                    <rect x="10" y="10" width="30" height="30"/>
                    <!--! License comment 2 -->
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><!--! License comment 1 --><rect x="10" y="10" width="30" height="30"/><!--! License comment 2 --></svg>
                XML,
        ];

        yield 'Removes a comment inside a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}<!-- x -->b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}b{}</style></svg>',
        ];

        yield 'Keeps preserved whitespace around a removed comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">a <!-- x --> b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">a  b</text></svg>',
        ];

        yield 'Removes a later comment after a kept legal comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!--! legal --><g><!-- drop --></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!--! legal --><g/></svg>',
        ];

        yield 'Removes comments with multibyte text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!-- 日本 😀 --><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
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
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect /><!-- c -->' . str_repeat('</g>', 60) . '</svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape">' . str_repeat('<g>', 60) . '<rect/>' . str_repeat('</g>', 60) . '</svg>',
        ];

        yield 'Handles a very long attribute value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '" /><!-- c --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect id="' . str_repeat('a', 10_000) . '"/></svg>',
        ];

        yield 'Handles right-to-left, emoji, combining and astral text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text >שלום 😀 é 𝒜</text><!-- c --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>שלום 😀 é 𝒜</text></svg>',
        ];

        yield 'Handles values padded with tabs, newlines and no-break spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect  fill="&#9; red&#10;&#xA0;"/><!-- c --></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:inkscape=\"http://www.inkscape.org/namespaces/inkscape\"><rect fill=\"&#9; red&#10;\u{a0}\"/></svg>",
        ];

        yield 'Handles comments between every node' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><!--1--><g><!--2--><!-- c --><!--3--><rect /><!--4--></g><!--5--></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><rect/></g></svg>',
        ];

        yield 'Handles processing instructions' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect /><!-- c --></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><?foo bar?><rect/></svg>',
        ];

        yield 'Handles CDATA sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text ><![CDATA[a<b&c]]></text><!-- c --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text>a&lt;b&amp;c</text></svg>',
        ];

        yield 'Handles a child that resets the default namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect /><!-- c --></x></g><rect /></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><g><x xmlns=""><rect/></x></g><rect/></svg>',
        ];

        yield 'Handles a prefix redeclared to another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect /></s:g><g xmlns:s="urn:other"><s:rect /></g><!-- c --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:s="http://www.w3.org/2000/svg"><s:g><s:rect/></s:g><g xmlns:s="urn:other"><s:rect/></g></svg>',
        ];

        yield 'Handles entities and character references in values and text' => [
            '<!DOCTYPE svg [<!ENTITY e "v&#x41;">]><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text  class="&e;">&e;&#x42;&#66;</text><!-- c --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><text class="vA">vABB</text></svg>',
        ];

        yield 'Handles an attribute in no namespace next to the same local name in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c" /><!-- c --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" xmlns:o="urn:o"><use href="#a" xlink:href="#b" o:href="#c"/></svg>',
        ];

        yield 'Handles repeated elements where only a later one matches' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect /><!-- c --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><rect/><rect fill="red"/><rect/></svg>',
        ];

        yield 'Handles mixed-case element names' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect /><RECT /><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script><!-- c --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"><Rect/><RECT/><Script>x</Script><SCRIPT>x</SCRIPT><s:script xmlns:s="http://www.w3.org/2000/svg">x</s:script></svg>',
        ];

        yield 'Keeps only comments that start with an exclamation mark' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!--!LEGAL--><!--! --><!-- ! --><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!--!LEGAL--><!--! --><rect/></svg>',
        ];
    }

    #[Test]
    public function handlesManyNamespaceDeclarationsInLinearTime(): void
    {
        $small = $this->secondsToOptimize(2_000);
        $large = $this->secondsToOptimize(16_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><rect/><!-- a --></svg>');

        $start = hrtime(true);
        (new RemoveComments())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
