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
use MathiasReker\PhpSvgOptimizer\Service\Rule\MergeStyles;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MergeStyles::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MergeStylesTest extends TestCase
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
        $svgOptimizer->addPass(new MergeStyles());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimizeIsIdempotent(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($expected));
        $svgOptimizer->addPass(new MergeStyles());

        self::assertSame($expected, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Merges style elements in document order' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}</style><g/><style>b{fill:blue}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}b{fill:blue}</style><g/></svg>',
        ];

        yield 'Merges style elements without surrounding whitespace' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style> a{} </style><style>\n\tb{}\n</style><style>c{}</style></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}b{}c{}</style></svg>',
        ];

        yield 'Merges nested style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>a{}</style></defs><g><g><style>b{}</style></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>a{}b{}</style></defs><g><g/></g></svg>',
        ];

        yield 'Merges style elements with the CSS type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css">a{}</style><style type="TEXT/CSS">b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css">a{}b{}</style></svg>',
        ];

        yield 'Merges style elements with an empty type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="">a{}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="">a{}b{}</style></svg>',
        ];

        yield 'Keeps style elements with a type padded with whitespace, which browsers do not apply' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style type=" text/css">b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style type=" text/css">b{}</style></svg>',
        ];

        yield 'Keeps style elements with a type followed by whitespace, which browsers do not apply' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css ">a{}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css ">a{}</style><style>b{}</style></svg>',
        ];

        yield 'Keeps style elements with another type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style type="text/less">b{}</style><style/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style type="text/less">b{}</style><style/></svg>',
        ];

        yield 'Merges style elements with the same media' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print">a{}</style><style media=" print ">b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print">a{}b{}</style></svg>',
        ];

        yield 'Merges style elements for all media' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="all">a{}</style><style>b{}</style><style media=" ALL ">c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="all">a{}b{}c{}</style></svg>',
        ];

        yield 'Wraps style sheets with different media in media rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print"> a{} </style><style>b{}</style><style media="screen and (min-width: 1px)">c{}</style><style media="all">d{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media print{a{}}b{}@media screen and (min-width: 1px){c{}}d{}</style></svg>',
        ];

        yield 'Wraps style sheets with media query lists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style media="screen, print and (orientation:landscape), (min-aspect-ratio: 16/9), (width &gt;= 1.5em)">b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}@media screen, print and (orientation:landscape), (min-aspect-ratio: 16/9), (width &gt;= 1.5em){b{}}</style></svg>',
        ];

        yield 'Keeps style elements with media that cannot be wrapped' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style media="print{">b{}</style><style/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style media="print{">b{}</style><style/></svg>',
        ];

        yield 'Keeps style elements with media whose parenthesis is not closed' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="screen and (min-width:10px">a{}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="screen and (min-width:10px">a{}</style><style>b{}</style></svg>',
        ];

        yield 'Keeps style elements with media whose parenthesis is not opened' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="screen)">a{}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="screen)">a{}</style><style>b{}</style></svg>',
        ];

        yield 'Keeps style elements with markup delimiters and different media' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style media="print">&lt;!-- b{}</style><style/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style media="print">&lt;!-- b{}</style><style/></svg>',
        ];

        yield 'Keeps style elements with a markup end delimiter and different media' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print">a{}</style><style>--&gt; b{}</style><style/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print">a{}</style><style>--&gt; b{}</style><style/></svg>',
        ];

        yield 'Merges style elements with markup delimiters and the same media' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>&lt;!-- a{} --&gt; b{}</style><style>c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>&lt;!-- a{} --&gt; b{}c{}</style></svg>',
        ];

        yield 'Keeps style sheets ending with a markup delimiter' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>&lt;!-- a{} --&gt;</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>&lt;!-- a{} --&gt;</style><style>b{}</style></svg>',
        ];

        yield 'Merges style elements with comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/* a */</style><style>b{}/* c */</style><style>d{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/* a */b{}/* c */d{}</style></svg>',
        ];

        yield 'Merges style elements in character data sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[a>b{content:"<&>"}]]></style><style>c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a&gt;b{content:"&lt;&amp;&gt;"}c{}</style></svg>',
        ];

        yield 'Merges style elements with strings, URLs, nested rules and Unicode' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{content:"}\"{"}@supports (display:grid){@media print{b{fill:url("#c}")}}}</style><style>.é{width:calc(var(--x) + 1px)}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{content:"}\"{"}@supports (display:grid){@media print{b{fill:url("#c}")}}}.é{width:calc(var(--x) + 1px)}</style></svg>',
        ];

        yield 'Merges namespaced style elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:style>a{}</svg:style><svg:style>b{}</svg:style></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:style>a{}b{}</svg:style></svg:svg>',
        ];

        yield 'Merges style sheets with escaped delimiters' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a\}{content:"\"}"}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a\}{content:"\"}"}b{}</style></svg>',
        ];

        yield 'Merges style sheets with comments between every token' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/*1*/a/*2*/{/*3*/}/*4*/</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/*1*/a/*2*/{/*3*/}/*4*/b{}</style></svg>',
        ];

        yield 'Merges style sheets with data URIs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:url(data:x;y}/*z*/)}</style><style>c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:url(data:x;y}/*z*/)}c{}</style></svg>',
        ];

        yield 'Merges style sheets with at-rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@font-face{font-family:A;src:url(a.woff2)}@keyframes k{from{opacity:0}to{opacity:1}}</style><style>@layer a{b{}}@property --x{syntax:"*";inherits:false}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@font-face{font-family:A;src:url(a.woff2)}@keyframes k{from{opacity:0}to{opacity:1}}@layer a{b{}}@property --x{syntax:"*";inherits:false}</style></svg>',
        ];

        yield 'Wraps at-rules in media rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style media="print">@font-face{font-family:A}.b{&amp;:hover{c:d}}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}@media print{@font-face{font-family:A}.b{&amp;:hover{c:d}}}</style></svg>',
        ];

        yield 'Merges style elements with several character data sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[a{}]]> <![CDATA[b>c{}]]></style><style><![CDATA[d{}]]></style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{} b&gt;c{}d{}</style></svg>',
        ];

        yield 'Keeps style sheets with an unclosed single quoted string' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{content:\'b}</style><style>c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{content:\'b}</style><style>c{}</style></svg>',
        ];

        yield 'Removes empty style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style> </style><style>a{}</style><style/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style></svg>',
        ];

        yield 'Removes an empty style element after a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style></style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style></svg>',
        ];

        yield 'Merges into the first style element with content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print"> </style><style>a{}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}b{}</style></svg>',
        ];

        yield 'Removes style elements without content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style/><style> </style><g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Keeps a single style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="all"> a{} </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="all"> a{} </style></svg>',
        ];

        yield 'Keeps documents without style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>',
        ];

        yield 'Keeps style elements with other attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style id="b">b{}</style><style/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style id="b">b{}</style><style/></svg>',
        ];

        yield 'Keeps style elements with a title' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style title="a">a{}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style title="a">a{}</style><style>b{}</style></svg>',
        ];

        yield 'Keeps style elements with namespaced attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style xml:space="preserve">b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style xml:space="preserve">b{}</style></svg>',
        ];

        yield 'Keeps style elements in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><foreignObject><style xmlns="http://www.w3.org/1999/xhtml">b{}</style></foreignObject><style>c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><foreignObject><style xmlns="http://www.w3.org/1999/xhtml">b{}</style></foreignObject><style>c{}</style></svg>',
        ];

        yield 'Keeps style elements outside the SVG namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><x:style xmlns:x="urn:x">b{}</x:style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><x:style xmlns:x="urn:x">b{}</x:style></svg>',
        ];

        yield 'Keeps style elements when a link element is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="b.css"/></foreignObject><style>c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="b.css"/></foreignObject><style>c{}</style></svg>',
        ];

        yield 'Keeps style elements with child nodes other than text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>b{}<!-- c --></style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>b{}<!-- c --></style></svg>',
        ];

        yield 'Keeps style elements in a switch' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><switch><g><style>b{}</style></g></switch></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><switch><g><style>b{}</style></g></switch></svg>',
        ];

        yield 'Keeps style elements in an element with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><symbol id="s"><g><style>b{}</style></g></symbol></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><symbol id="s"><g><style>b{}</style></g></symbol></svg>',
        ];

        yield 'Keeps style elements in an element with a system language' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><g systemLanguage="en"><style>b{}</style></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><g systemLanguage="en"><style>b{}</style></g></svg>',
        ];

        yield 'Keeps style elements in an element with required features' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><g requiredFeatures="x"><style>b{}</style></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><g requiredFeatures="x"><style>b{}</style></g></svg>',
        ];

        yield 'Keeps style elements in an element with required extensions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><g requiredExtensions="x"><style>b{}</style></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><g requiredExtensions="x"><style>b{}</style></g></svg>',
        ];

        yield 'Merges style elements in a root element with an id and conditions' => [
            '<svg xmlns="http://www.w3.org/2000/svg" id="r" systemLanguage="en"><style>a{}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" id="r" systemLanguage="en"><style>a{}b{}</style></svg>',
        ];

        yield 'Keeps style sheets with imports' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>@IMPORT url(b.css);c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>@IMPORT url(b.css);c{}</style></svg>',
        ];

        yield 'Keeps style sheets with a character set' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@charset "utf-8";a{}</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@charset "utf-8";a{}</style><style>b{}</style></svg>',
        ];

        yield 'Keeps style sheets with a namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>@namespace svg url(http://www.w3.org/2000/svg);b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>@namespace svg url(http://www.w3.org/2000/svg);b{}</style></svg>',
        ];

        yield 'Keeps style sheets with an escaped at-rule' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>@\69mport "b.css";c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>@\69mport "b.css";c{}</style></svg>',
        ];

        yield 'Keeps style sheets with an unclosed block' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red</style><style>b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red</style><style>b{}</style></svg>',
        ];

        yield 'Keeps style sheets with a trailing selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}b</style><style>c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}b</style><style>c{}</style></svg>',
        ];

        yield 'Keeps style sheets with a trailing statement' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>@layer b;</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>@layer b;</style></svg>',
        ];

        yield 'Keeps style sheets with mismatched brackets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c(}</style><style>d{})</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c(}</style><style>d{})</style></svg>',
        ];

        yield 'Keeps style sheets with a malformed URL' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>b{fill:url(c d)}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>b{fill:url(c d)}</style></svg>',
        ];

        yield 'Keeps style sheets with an unclosed comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}/* b</style><style>c{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}/* b</style><style>c{}</style></svg>',
        ];

        yield 'Keeps style sheets with an unclosed string' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>b{content:"c}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{}</style><style>b{content:"c}</style></svg>',
        ];

        yield 'Keeps a leading space character other than CSS whitespace' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>a{}</style><style>\u{a0}b{}</style></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>a{}\u{a0}b{}</style></svg>",
        ];
    }
}
