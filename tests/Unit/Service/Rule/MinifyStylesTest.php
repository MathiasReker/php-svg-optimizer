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
use MathiasReker\PhpSvgOptimizer\Service\Rule\MinifyStyles;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MinifyStyles::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class MinifyStylesTest extends TestCase
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
        $svgOptimizer->addRule(new MinifyStyles());

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
        $svgOptimizer->addRule(new MinifyStyles());

        self::assertSame($expected, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Collapses whitespace around selectors, combinators and declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style> a  b , c &gt; d ~ e + f { fill : red ; stroke : blue ; } </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a b,c&gt;d~e + f{fill:red;stroke:blue}</style></svg>',
        ];

        yield 'Collapses tabs and line breaks' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>\n\ta\n\tb\r\n{\n\tfill:\tred;\n}\n</style></svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a b{fill:red}</style></svg>',
        ];

        yield 'Keeps minified style sheets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}b c{fill:blue}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}b c{fill:blue}</style></svg>',
        ];

        yield 'Removes comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}/* x */b{fill:/* y */blue}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:red}b{fill:blue}</style></svg>',
        ];

        yield 'Keeps license comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/*! (c) x */ a{fill:red} /*! y */ b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/*! (c) x */ a{fill:red}/*! y */ b{}</style></svg>',
        ];

        yield 'Keeps an empty comment where a comment separates tokens' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a/* x *//* y */b{fill:red}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a/**/b{fill:red}</style></svg>',
        ];

        yield 'Keeps a space where whitespace follows a comment between tokens' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a/* x */ b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a b{}</style></svg>',
        ];

        yield 'Keeps a space where a comment follows whitespace between tokens' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a /* x */b{}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a b{}</style></svg>',
        ];

        yield 'Keeps the content of strings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { content : "/* x */  ;  }" ; b : \'c  , d\' }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{content:"/* x */  ;  }";b:\'c  , d\'}</style></svg>',
        ];

        yield 'Keeps the content of strings with escaped quotes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { content : "a\" ; b" ; b : \'c\\\' } d\' }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{content:"a\" ; b";b:\'c\\\' } d\'}</style></svg>',
        ];

        yield 'Keeps unquoted URLs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { fill : url( #x ) ; b : URL(a;/*b*/c) }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:url( #x );b:URL(a;/*b*/c)}</style></svg>',
        ];

        yield 'Keeps quoted URLs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { fill : url(  "a  b" ) ; b : url(\'c ; d\') }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{fill:url(  "a  b" );b:url(\'c ; d\')}</style></svg>',
        ];

        yield 'Minifies functions named like url' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : -url( c d ) ; e : url ( f ) ; g : url }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:-url(c d);e:url (f);g:url}</style></svg>',
        ];

        yield 'Keeps style sheets with malformed URLs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : url( c d ) }</style><style>a { b : URL("c" d) }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : url( c d ) }</style><style>a { b : URL("c" d) }</style></svg>',
        ];

        yield 'Keeps style sheets with an unclosed string' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { content : "x }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { content : "x }</style></svg>',
        ];

        yield 'Keeps style sheets with an unclosed comment' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { } /* x</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { } /* x</style></svg>',
        ];

        yield 'Keeps style sheets with an unclosed block' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c</style></svg>',
        ];

        yield 'Keeps style sheets with mismatched brackets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c( } )</style><style>a [ b { ] }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c( } )</style><style>a [ b { ] }</style></svg>',
        ];

        yield 'Keeps style sheets with an unopened block' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { } }</style></svg>',
        ];

        yield 'Keeps style sheets with an escaped line break' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>a\\\n { }</style></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>a\\\n { }</style></svg>",
        ];

        yield 'Minifies media queries' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media screen and (min-width : 1px) { a:hover , b :focus { fill : red } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media screen and (min-width:1px){a:hover,b :focus{fill:red}}</style></svg>',
        ];

        yield 'Minifies nested conditional rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports (display : grid) { @media ( min-width : 1px ) { a { b : c } } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports (display:grid){@media (min-width:1px){a{b:c}}}</style></svg>',
        ];

        yield 'Minifies container queries' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@container (min-width : 1px) and (width &gt; 2px) { a { } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@container (min-width:1px) and (width&gt;2px){a{}}</style></svg>',
        ];

        yield 'Minifies nested and listed query groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports ((display : grid)) { } @media (a : b),(c : d) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports ((display:grid)){}@media (a:b),(c:d){}</style></svg>',
        ];

        yield 'Keeps colons in the selectors of other at-rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@scope (.a :hover) { b { c : d } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@scope (.a :hover){b{c:d}}</style></svg>',
        ];

        yield 'Keeps colons in functions of conditional at-rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports selector(a :hover) and selector((b :focus)) { } @media screen and(min-width : 1px) { } @media(min-width : 1px) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports selector(a :hover) and selector((b :focus)){}@media screen and(min-width : 1px){}@media(min-width : 1px){}</style></svg>',
        ];

        yield 'Keeps colons after a detached at sign' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@ media (a : b) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@ media (a : b){}</style></svg>',
        ];

        yield 'Starts a new statement after an at-rule statement' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import "a.css" ; @media (a : b) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import "a.css";@media (a:b){}</style></svg>',
        ];

        yield 'Continues the selector after a semicolon outside blocks' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a ; @media (a : b) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a;@media (a : b){}</style></svg>',
        ];

        yield 'Continues the at-rule after a semicolon before a nested at-rule in a query group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media (a ; @media (b : c)) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media (a;@media (b:c)){}</style></svg>',
        ];

        yield 'Starts a new statement after a declaration, a block start and a block end' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c ; @media (d : e) { } } @media print { @media (f : g) { } } h { } @media (i : j) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c;@media (d:e){}}@media print{@media (f:g){}}h{}@media (i:j){}</style></svg>',
        ];

        yield 'Keeps colons of nested selectors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c ; d :hover { e : f } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c;d :hover{e:f}}</style></svg>',
        ];

        yield 'Minifies colons of declarations with nested brackets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c( {} ) } d { e : f( g ; h ) }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c({})}d{e:f(g;h)}</style></svg>',
        ];

        yield 'Keeps colons in selectors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a :hover { } b:not( c :focus ) { } d[ e : f ] { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a :hover{}b:not(c :focus){}d[e : f]{}</style></svg>',
        ];

        yield 'Keeps colons in query groups after a selector' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a @media (b : c) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a @media (b : c){}</style></svg>',
        ];

        yield 'Keeps colons in query groups after an at sign without a name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@"x" ; @media (a : b) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@"x";@media (a : b){}</style></svg>',
        ];

        yield 'Keeps colons in query groups after a selector starting with a name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.media (a : b) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.media (a : b){}</style></svg>',
        ];

        yield 'Minifies uppercase conditional at-rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@MEDIA (a : b) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@MEDIA (a:b){}</style></svg>',
        ];

        yield 'Starts a new statement inside a style rule' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { @media (b : c) { } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{@media (b:c){}}</style></svg>',
        ];

        yield 'Continues the at-rule after a semicolon in a query group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media (a;b) and (c : d) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media (a;b) and (c:d){}</style></svg>',
        ];

        yield 'Keeps the space after a colon of a nested selector that follows a parenthesis' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b(): c { d : e } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b(): c{d:e}}</style></svg>',
        ];

        yield 'Keeps colons of nested selectors with functions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b :not( c ) { d : e } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b :not(c){d:e}}</style></svg>',
        ];

        yield 'Minifies functions with names ending like url' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>a { b : éurl( c ) ; d : ★url( e ) ; f : \u{a0}url( g ) }</style></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>a{b:éurl(c);d:★url(e);f:\u{a0}url(g)}</style></svg>",
        ];

        yield 'Keeps escaped delimiters in identifiers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a\}b , .c\;d , .e\:f { g : h }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a\}b,.c\;d,.e\:f{g:h}</style></svg>',
        ];

        yield 'Keeps escaped quotes and line break escapes in strings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a::after { content : "x\A y \"z\"" ; b : \'\A\' }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a::after{content:"x\A y \"z\"";b:\'\A\'}</style></svg>',
        ];

        yield 'Keeps style sheets with an unclosed single quoted string' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { content : \'x }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { content : \'x }</style></svg>',
        ];

        yield 'Keeps style sheets with a line break in a string' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>a { content : \"x\ny\" }</style></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>a { content : \"x\ny\" }</style></svg>",
        ];

        yield 'Removes comments between every token' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>/*1*/a/*2*/{/*3*/b/*4*/:/*5*/c/*6*/;/*7*/}/*8*/</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c}</style></svg>',
        ];

        yield 'Keeps unquoted URLs with escapes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : url( a\)b\ c.png ) }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:url( a\)b\ c.png )}</style></svg>',
        ];

        yield 'Keeps data URIs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : url(data:x;y}/*z*/) ; c : url( "data:image/svg+xml;utf8,&lt;svg xmlns=\'http://www.w3.org/2000/svg\'/&gt; ;}" ) }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:url(data:x;y}/*z*/);c:url( "data:image/svg+xml;utf8,&lt;svg xmlns=\'http://www.w3.org/2000/svg\'/&gt; ;}" )}</style></svg>',
        ];

        yield 'Minifies font faces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@font-face { font-family : "A  B" ; src : url(a.woff2) format( "woff2" ) , local( A ) ; unicode-range : U+0025-00FF , u+4?? }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@font-face{font-family:"A  B";src:url(a.woff2) format("woff2"),local(A);unicode-range:U+0025-00FF,u+4??}</style></svg>',
        ];

        yield 'Minifies keyframes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@keyframes spin { from { transform : rotate( 0deg ) } 50% { opacity : .5 } to { transform : rotate( 360deg ) } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@keyframes spin{from{transform:rotate(0deg)}50%{opacity:.5}to{transform:rotate(360deg)}}</style></svg>',
        ];

        yield 'Minifies layers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@layer base , theme ; @layer base { a { b : c } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@layer base,theme;@layer base{a{b:c}}</style></svg>',
        ];

        yield 'Minifies registered properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@property --x { syntax : "&lt;length&gt;" ; inherits : false ; initial-value : 0px }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@property --x{syntax:"&lt;length&gt;";inherits:false;initial-value:0px}</style></svg>',
        ];

        yield 'Minifies named container queries' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@container card (min-width : 400px) { a { b : c } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@container card (min-width:400px){a{b:c}}</style></svg>',
        ];

        yield 'Minifies negated supports queries' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports not (display : grid) { a { float : left } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@supports not (display:grid){a{float:left}}</style></svg>',
        ];

        yield 'Minifies nested style rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a { color : red ; &amp; :hover { color : blue } &amp;.b { c : d } &gt; .e { f : g } }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{color:red;&amp; :hover{color:blue}&amp;.b{c:d}&gt;.e{f:g}}</style></svg>',
        ];

        yield 'Keeps the spaces around operators in nested math functions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { width : clamp( 1px , calc( 2px + ( 3px - 4px ) * 2 ) , max( 5px , min( 6px , 7% ) ) ) ; margin : -1px -2px }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{width:clamp(1px,calc(2px + (3px - 4px) * 2),max(5px,min(6px,7%)));margin:-1px -2px}</style></svg>',
        ];

        yield 'Minifies custom properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { --x : { a ; b } ; --y : a  b ; fill : var( --y , red ) }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{--x :{a;b};--y:a b;fill:var(--y,red)}</style></svg>',
        ];

        yield 'Keeps the space of empty custom properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { --x : ; --y :; --z : }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{--x: ;--y:;--z: }</style></svg>',
        ];

        yield 'Minifies important declarations with comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c /* x */ ! /* y */ important ; d : e!IMPORTANT }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c!important;d:e!IMPORTANT}</style></svg>',
        ];

        yield 'Minifies attribute selectors with operators' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[ data-a = "x  y" i ] , [ a ~= "b" ] , [ a |= b ] , [ a ^= b ]</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[data-a = "x  y" i],[a~= "b"],[a |= b],[a ^= b]</style></svg>',
        ];

        yield 'Minifies selector lists in pseudo-classes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>:is( a , b ) :not( .c , .d ) , :where( e ) { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>:is(a,b) :not(.c,.d),:where(e){}</style></svg>',
        ];

        yield 'Minifies text and character data sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c }<![CDATA[ d > e { f : g } ]]> h { }<![CDATA[i { }]]></style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c}d&gt;e{f:g}h{}i{}</style></svg>',
        ];

        yield 'Minifies style elements with media and title' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print" title="x"> a { } </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style media="print" title="x">a{}</style></svg>',
        ];

        yield 'Removes the last semicolon of a block' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c;}d { e : f ; } g{h:i;;}j{k:l;;m:n}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c}d{e:f}g{h:i}j{k:l;m:n}</style></svg>',
        ];

        yield 'Keeps semicolons outside blocks and in brackets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import "a.css";;a{b:c(d;;)}@media (e;){}</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import "a.css";;a{b:c(d;;)}@media (e;){}</style></svg>',
        ];

        yield 'Minifies important declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : c !important ; d : e ! important }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:c!important;d:e!important}</style></svg>',
        ];

        yield 'Keeps the spaces around math operators' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { width : calc( 1px + 2px ) ; height : calc(100% - var( --x , 1px )) ; grid-area : 1 / 2 }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{width:calc(1px + 2px);height:calc(100% - var(--x,1px));grid-area:1 / 2}</style></svg>',
        ];

        yield 'Keeps the spaces between values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a { b : rgb( 1 , 2 , 3 ) red ; c : "d" "e" ; f : 0.50 1E3 }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a{b:rgb(1,2,3) red;c:"d" "e";f:0.50 1E3}</style></svg>',
        ];

        yield 'Keeps the spaces that would join delimiters' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media (width &gt; = 1px) { } [a ~ =b] { } a -- &gt; b { } c &lt; !d { } e ! --f { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@media (width&gt; = 1px){}[a~ =b]{}a -- &gt;b{}c &lt; !d{}e! --f{}</style></svg>',
        ];

        yield 'Minifies attribute selectors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a [ b = "c  d" ] , e[f] { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a [b = "c  d"],e[f]{}</style></svg>',
        ];

        yield 'Minifies Unicode style sheets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.é  ü { content : "ø  å" ; font-family : 日本 , 中文 }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.é ü{content:"ø  å";font-family:日本,中文}</style></svg>',
        ];

        yield 'Keeps escapes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a\:b  c { } .\31  d { } .\31 { } .\é  e { }</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a\:b c{}.\31  d{}.\31 {}.\é e{}</style></svg>',
        ];

        yield 'Keeps markup delimiters apart from identifiers' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>&lt;!-- a { b : c } --&gt;</style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>&lt;!-- a{b:c}--&gt;</style></svg>',
        ];

        yield 'Minifies style sheets in character data sections' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[ a > b { fill : red } ]]></style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>a&gt;b{fill:red}</style></svg>',
        ];

        yield 'Empties style sheets with comments only' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style> /* x */ </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style/></svg>',
        ];

        yield 'Keeps empty style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>   </style><style/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style/><style/></svg>',
        ];

        yield 'Minifies namespaced style elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:style> a { fill : red } </svg:style></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:style>a{fill:red}</svg:style></svg:svg>',
        ];

        yield 'Minifies style elements with the CSS type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css"> a { } </style><style type="TEXT/CSS"> b { } </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/css">a{}</style><style type="TEXT/CSS">b{}</style></svg>',
        ];

        yield 'Keeps style elements with a type padded with whitespace, which browsers do not apply' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type=" text/css"> a { } </style><style type="text/css "> b { } </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type=" text/css"> a { } </style><style type="text/css "> b { } </style></svg>',
        ];

        yield 'Keeps style elements with another type and minifies those with an empty type' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/less"> a { } </style><style type=""> b { } </style><style> c { } </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style type="text/less"> a { } </style><style type="">b{}</style><style>c{}</style></svg>',
        ];

        yield 'Keeps style elements with child nodes other than text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style> a { } <x:y xmlns:x="urn:x"/></style><style> b { } <!-- c --></style><style> d { } </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style> a { } <x:y xmlns:x="urn:x"/></style><style> b { } <!-- c --></style><style>d{}</style></svg>',
        ];

        yield 'Keeps style elements in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><style xmlns="http://www.w3.org/1999/xhtml"> a { } </style><svg><style> b { } </style></svg></foreignObject><x:style xmlns:x="urn:x"> c { } </x:style><style> d { } </style></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><style xmlns="http://www.w3.org/1999/xhtml"> a { } </style><svg><style> b { } </style></svg></foreignObject><x:style xmlns:x="urn:x"> c { } </x:style><style>d{}</style></svg>',
        ];
    }

    #[Test]
    public function ruleIsMarkedAsNotRisky(): void
    {
        self::assertFalse(MinifyStyles::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(MinifyStyles::shouldCheckSize());
    }
}
