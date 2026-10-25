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
use MathiasReker\PhpSvgOptimizer\Service\Rule\ScopeSvgStyles;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ScopeSvgStyles::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class ScopeSvgStylesTest extends TestCase
{
    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, callable $assertion): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new ScopeSvgStyles());

        $actual = $svgOptimizer->allowRisky()->optimize()->getContent();

        $assertion($actual);
    }

    /**
     * @return iterable<array{string, callable}>
     */
    public static function provideOptimizeCases(): iterable
    {
        $unscopable = [
            'rules nested in a media block' => '<svg><style>.a{stroke:blue}@media screen{.a{fill:red}}</style><rect class="a" id="r"/></svg>',
            'rules nested in a supports block of another style sheet' => '<svg><style>.a{stroke:blue}</style><svg><style>@supports (fill:red){#r{fill:red}}</style><rect class="a" id="r"/></svg></svg>',
            'an imported style sheet' => '<svg><style>@import url(a.css);.a{stroke:blue}</style><rect class="a"/></svg>',
            'a style element with an element child, whose text browsers do not apply' => '<svg><style><x>.a{fill:red}</x></style><rect class="a"/></svg>',
            'a style sheet of another type' => '<svg><style type="text/xsl">.a{fill:red}</style><rect class="a"/></svg>',
        ];

        foreach ($unscopable as $name => $svg) {
            yield 'Leaves a document with ' . $name . ' untouched' => [
                $svg,
                static function (string $output) use ($svg): void {
                    self::assertSame($svg, $output);
                },
            ];
        }

        yield 'Scopes simple class selector' => [
            '<svg><style>.cls-0{fill:#fff}</style><rect class="cls-0"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.cls-0-[a-f0-9\-]+{fill:#fff}/', $output);
                self::assertMatchesRegularExpression('/class="cls-0-[a-f0-9\-]+"/', $output);
            },
        ];

        yield 'Trims the whitespace around a style sheet' => [
            '<svg><style> .a{fill:red} </style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<style>\.a-[a-f0-9]{10}\{fill:red}<\/style>/', $output);
            },
        ];

        yield 'Scopes ID selector' => [
            '<svg><style>#id-1{fill:red}</style><rect id="id-1"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/#id-1-[a-f0-9\-]+{fill:red}/', $output);
                self::assertMatchesRegularExpression('/id="id-1-[a-f0-9\-]+"/', $output);
            },
        ];

        yield 'Scopes nested class selectors' => [
            '<svg><style>.a .b{fill:black}</style><g class="a"><rect class="b"/></g></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+ \.b-[a-f0-9\-]+{fill:black}/', $output);
                self::assertMatchesRegularExpression('/class="a-[a-f0-9\-]+"/', $output);
                self::assertMatchesRegularExpression('/class="b-[a-f0-9\-]+"/', $output);
            },
        ];

        yield 'Scopes combinator selector' => [
            '<svg><style>.a + .b{fill:blue}</style><rect class="a"/><rect class="b"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+ \+ \.b-[a-f0-9\-]+{fill:blue}/', $output);
            },
        ];

        yield 'Scopes comma separated selectors' => [
            '<svg><style>.a,.b{fill:green}</style><rect class="a"/><rect class="b"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+, \.b-[a-f0-9\-]+{fill:green}/', $output);
            },
        ];

        yield 'Updates url(#id) references' => [
            '<svg><defs><linearGradient id="grad"/></defs><style>#grad{}</style><rect fill="url(#grad)"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/id="grad-[a-f0-9\-]+"/', $output);
                self::assertMatchesRegularExpression('/url\(#grad-[a-f0-9\-]+\)/', $output);
            },
        ];

        yield 'Removes unsafe tag selector' => [
            '<svg><style>path{fill:red}</style><path/></svg>',
            static function (string $output): void {
                self::assertSame('<svg><path/></svg>', $output);
            },
        ];

        yield 'Removes universal selector' => [
            '<svg><style>*{fill:red}</style><rect/></svg>',
            static function (string $output): void {
                self::assertSame('<svg><rect/></svg>', $output);
            },
        ];

        yield 'Removes a style sheet left with whitespace only' => [
            "<svg><style>rect{fill:red}\n*{stroke:red}</style><rect/></svg>",
            static function (string $output): void {
                self::assertSame('<svg><rect/></svg>', $output);
            },
        ];

        yield 'Removes every style sheet that only had unsafe selectors' => [
            '<svg><style>rect{fill:red}</style><style>*{stroke:red}</style><style>.a{fill:blue}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/^<svg><style>\.a-[0-9a-f]{10}\{fill:blue}<\/style><rect class="a-[0-9a-f]{10}"\/><\/svg>$/', $output);
            },
        ];

        yield 'No style tag present' => [
            '<svg><rect width="10"/></svg>',
            static function (string $output): void {
                self::assertSame('<svg><rect width="10"/></svg>', $output);
            },
        ];

        yield 'Keeps classes and content when there is no style tag or id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect class="a" fill="url(#g)" width="10"/></svg>',
            static function (string $output): void {
                self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"><rect class="a" fill="url(#g)" width="10"/></svg>', $output);
            },
        ];

        yield 'Keeps classes when the style tag has no class selectors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><rect class="a" width="10"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<rect class="a" width="10"/>', $output);
            },
        ];

        yield 'Handles empty style tag' => [
            '<svg><style></style><rect/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<rect/>', $output);
            },
        ];

        yield 'Removes unsafe selectors from a selector list' => [
            '<svg><style>g, .a, *{fill:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/^<svg><style>\.a-[0-9a-f]{10}\{fill:red}<\/style><rect class="a-[0-9a-f]{10}"\/><\/svg>$/', $output);
            },
        ];

        yield 'Removes a rule whose selector list has only unsafe selectors' => [
            '<svg><style>g, rect{fill:red}.a{fill:blue}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/^<svg><style>\.a-[0-9a-f]{10}\{fill:blue}<\/style><rect class="a-[0-9a-f]{10}"\/><\/svg>$/', $output);
            },
        ];

        yield 'Removes unsafe selector but keeps valid one' => [
            '<svg><style>path{fill:red}.a{fill:blue}</style><rect class="a"/><path/></svg>',
            static function (string $output): void {
                self::assertStringNotContainsString('path{fill:red}', $output);
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+{fill:blue}/', $output);
            },
        ];

        yield 'Scopes ID even if not used in style' => [
            '<svg><rect id="unused"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/id="unused-[a-f0-9\-]+"/', $output);
            },
        ];

        yield 'Updates url(#id) inside stroke attribute' => [
            '<svg><defs><pattern id="p"/></defs><style>#p{}</style><rect stroke="url(#p)"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/stroke="url\(#p-[a-f0-9\-]+\)"/', $output);
            },
        ];

        yield 'Updates url(#id) even without CSS reference' => [
            '<svg><defs><linearGradient id="grad"/></defs><rect fill="url(#grad)"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/id="grad-[a-f0-9\-]+"/', $output);
                self::assertMatchesRegularExpression('/url\(#grad-[a-f0-9\-]+\)/', $output);
            },
        ];

        yield 'Scopes child combinator selector' => [
            '<svg><style>.a > .b{fill:black}</style><g class="a"><rect class="b"/></g></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+ (>|&gt;) \.b-[a-f0-9\-]+{fill:black}/', $output);
            },
        ];

        yield 'Scopes complex selector with id and class' => [
            '<svg><style>#x .a + .b{fill:red}</style><g id="x"><rect class="a"/><rect class="b"/></g></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/#x-[a-f0-9\-]+ \.a-[a-f0-9\-]+ \+ \.b-[a-f0-9\-]+{fill:red}/', $output);
            },
        ];

        yield 'Scopes multiple rule blocks inside one style tag' => [
            '<svg><style>.a{fill:red}.b{fill:blue}</style><rect class="a"/><rect class="b"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+{fill:red}/', $output);
                self::assertMatchesRegularExpression('/\.b-[a-f0-9\-]+{fill:blue}/', $output);
            },
        ];

        yield 'Does not modify hex color values' => [
            '<svg><style>.a{fill:#fff}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('fill:#fff', $output);
            },
        ];

        yield 'Does not modify external url() references' => [
            '<svg><style>.a{fill:url(http://example.com/image.png)}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('url(http://example.com/image.png)', $output);
            },
        ];

        yield 'Handles pseudo-class selectors' => [
            '<svg><style>.a:hover{fill:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+:hover{fill:red}/', $output);
            },
        ];

        yield 'Handles attribute selectors' => [
            '<svg><style>[class*="a"]{fill:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\[class\*="a"]{fill:red}/', $output);
            },
        ];

        yield 'Does not affect inline styles' => [
            '<svg><rect style="fill:red"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('style="fill:red"', $output);
            },
        ];

        yield 'Handles empty class attribute' => [
            '<svg><style>.a{fill:red}</style><rect class=""/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('class=""', $output);
            },
        ];

        yield 'Scopes multiple classes on same element' => [
            '<svg><style>.a{fill:red}.b{stroke:blue}</style><rect class="a b"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/class="a-[a-f0-9\-]+ b-[a-f0-9\-]+"/', $output);
            },
        ];

        yield 'Does not duplicate class when already scoped once in CSS' => [
            '<svg><style>.a{fill:red}.a{stroke:blue}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertSame(1, preg_match_all('/\.a-[a-f0-9\-]+{fill:red}/', $output));
                self::assertSame(1, preg_match_all('/\.a-[a-f0-9\-]+{stroke:blue}/', $output));
            },
        ];

        yield 'Scopes same class used in multiple selectors' => [
            '<svg><style>.a,.a:hover{fill:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+, \.a-[a-f0-9\-]+:hover{fill:red}/', $output);
            },
        ];

        yield 'Scopes pseudo class selector' => [
            '<svg><style>.a:hover{fill:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+:hover{fill:red}/', $output);
            },
        ];

        yield 'Scopes attribute selector combined with class' => [
            '<svg><style>.a[data-x="1"]{fill:red}</style><rect class="a" data-x="1"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+\[data-x="1"\]{fill:red}/', $output);
            },
        ];

        yield 'Does not break when style contains whitespace and newlines' => [
            "<svg><style>\n  .a  {  fill:red; }\n</style><rect class=\"a\"/></svg>",
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-[a-f0-9\-]+\s*{\s*fill:red;?\s*}/', $output);
            },
        ];

        yield 'Removes rule if selector is pure tag but keeps mixed selector' => [
            '<svg><style>path{fill:red}path.a{stroke:blue}</style><path class="a"/></svg>',
            static function (string $output): void {
                self::assertStringNotContainsString('path{fill:red}', $output);
                self::assertMatchesRegularExpression('/path\.a-[a-f0-9\-]+{stroke:blue}/', $output);
            },
        ];

        yield 'Scopes id in selector even if element not present' => [
            '<svg><style>#missing{fill:red}</style><rect/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/#missing-[a-f0-9\-]+{fill:red}/', $output);
            },
        ];

        yield 'Updates multiple url references in same attribute' => [
            <<<'EOD'
                <svg>
                                <defs>
                                    <linearGradient id="a"/>
                                    <linearGradient id="b"/>
                                </defs>
                                <rect fill="url(#a) url(#b)"/>
                            </svg>
                EOD,
            static function (string $output): void {
                self::assertMatchesRegularExpression('/url\(#a-[a-f0-9\-]+\)/', $output);
                self::assertMatchesRegularExpression('/url\(#b-[a-f0-9\-]+\)/', $output);
            },
        ];

        yield 'Does not modify unrelated attributes containing url text' => [
            '<svg><rect data-test="url(#fake)"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('data-test="url(#fake)"', $output);
            },
        ];

        yield 'Replaces class attribute exactly once per class' => [
            '<svg><style>.a{fill:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('fill:red', $output);
                self::assertStringNotContainsString('class="a"', $output);
                self::assertStringContainsString('class="a-', $output);
            },
        ];

        yield 'Replaces multiple classes correctly' => [
            '<svg><style>.a{fill:red}.b{fill:blue}</style><rect class="a b"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('class="a-', $output);
                self::assertStringContainsString(' b-', $output);
                self::assertStringNotContainsString('class="a b"', $output);
            },
        ];

        yield 'Scopes class in descendant selector' => [
            '<svg><style>.a .b{fill:black}</style><g class="a"><rect class="b"/></g></svg>',
            static function (string $output): void {
                self::assertStringContainsString('.a-', $output);
                self::assertStringContainsString('.b-', $output);
                self::assertStringContainsString('fill:black', $output);
            },
        ];

        yield 'Scopes id and updates element attribute' => [
            '<svg><style>#x{fill:red}</style><rect id="x"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('#x-', $output);
                self::assertStringContainsString('id="x-', $output);
                self::assertStringNotContainsString('id="x"', $output);
            },
        ];

        yield 'Updates url reference in fill attribute' => [
            '<svg><defs><linearGradient id="g"/></defs><rect fill="url(#g)"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('id="g-', $output);
                self::assertStringContainsString('url(#g-', $output);
                self::assertStringNotContainsString('url(#g)"', $output);
            },
        ];

        yield 'Does not modify external url references' => [
            '<svg><style>.a{fill:url(http://example.com/test.png)}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('url(http://example.com/test.png)', $output);
            },
        ];

        yield 'Removes pure tag selector rule completely' => [
            '<svg><style>path{fill:red}</style><path/></svg>',
            static function (string $output): void {
                self::assertStringNotContainsString('path{fill:red}', $output);
                self::assertStringNotContainsString('<style>path', $output);
            },
        ];

        yield 'Removes universal selector rule completely' => [
            '<svg><style>*{fill:red}</style><rect/></svg>',
            static function (string $output): void {
                self::assertStringNotContainsString('*{fill:red}', $output);
            },
        ];

        yield 'Keeps safe rule when unsafe rule also exists' => [
            '<svg><style>path{fill:red}.safe{fill:blue}</style><rect class="safe"/></svg>',
            static function (string $output): void {
                self::assertStringNotContainsString('path{fill:red}', $output);
                self::assertStringContainsString('.safe-', $output);
                self::assertStringContainsString('fill:blue', $output);
            },
        ];

        yield 'Handles empty style block gracefully' => [
            '<svg><style>   </style><rect/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<rect/>', $output);
            },
        ];

        yield 'Scopes same class in multiple rule blocks' => [
            '<svg><style>.a{fill:red}.a{stroke:blue}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('fill:red', $output);
                self::assertStringContainsString('stroke:blue', $output);
                self::assertStringContainsString('.a-', $output);
                self::assertStringNotContainsString('.a{', $output);
            },
        ];

        yield 'Does not modify svg without style but with id' => [
            '<svg><rect id="test"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('id="test-', $output);
                self::assertStringNotContainsString('id="test"', $output);
            },
        ];

        yield 'Preserves non matching attributes' => [
            '<svg><rect data-id="a" data-class="b"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('data-id="a"', $output);
                self::assertStringContainsString('data-class="b"', $output);
            },
        ];

        yield 'Skips scoping element with empty id attribute but still scopes real id' => [
            '<svg><rect id=""/><rect id="real"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('id=""', $output);
                self::assertMatchesRegularExpression('/id="real-[a-f0-9\-]+"/', $output);
            },
        ];

        yield 'Scopes a style block that follows an empty one' => [
            '<svg><style></style><style>.a{fill:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<style>\.a-[0-9a-f]{10}\{fill:red}<\/style><rect class="a-[0-9a-f]{10}"\/>/', $output);
            },
        ];

        yield 'Removes a tag selector rule preceded by whitespace' => [
            '<svg><style>.a{fill:blue} rect{fill:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<style>\.a-[0-9a-f]{10}\{fill:blue}<\/style>/', $output);
            },
        ];

        yield 'Trims each selector of a selector list' => [
            '<svg><style>.a , .b{fill:red}</style><rect class="a b"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<style>\.a-([0-9a-f]{10}), \.b-\1\{fill:red}<\/style>/', $output);
            },
        ];

        yield 'Keeps class attributes untouched when no class is scoped' => [
            '<svg><style>#r{fill:red}</style><rect id="r" class=" a  b "/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('class=" a  b "', $output);
            },
        ];

        yield 'Keeps dots and hashes inside attribute selectors' => [
            '<svg><style>[href="a.b"]{fill:red}[fill="#fff"]{stroke:red}</style><a href="a.b"><rect fill="#fff"/></a></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<style>[href="a.b"]{fill:red}[fill="#fff"]{stroke:red}</style>', $output);
            },
        ];

        yield 'Leaves an escaped class selector and its class unscoped' => [
            '<svg><style>.a\:b{fill:red}</style><rect class="a:b"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<style>.a\:b{fill:red}</style><rect class="a:b"/>', $output);
            },
        ];

        yield 'Leaves a non-ASCII class selector and its class unscoped' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.café{fill:red}#i{fill:blue}</style><rect class="café" id="i"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('.café{fill:red}', $output);
                self::assertStringContainsString('<rect class="café" id="i-', $output);
            },
        ];

        yield 'Updates url() references padded with whitespace' => [
            '<svg><linearGradient id="g"/><rect fill="url( #g )"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<linearGradient id="g-([0-9a-f]{10})"\/><rect fill="url\(#g-\1\)"\/>/', $output);
            },
        ];

        yield 'Updates an href padded with whitespace' => [
            '<svg><rect id="a"/><use href=" #a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<rect id="a-([0-9a-f]{10})"\/><use href="#a-\1"\/>/', $output);
            },
        ];

        yield 'Updates the ids an href animation switches to' => [
            '<svg><rect id="a"/><rect id="b"/><use href="#a"><set attributeName="href" to="#b"/><animate attributeName="xlink:href" values="#a; #b;#c" from="#b"/></use></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<set attributeName="href" to="#b-([0-9a-f]{10})"\/><animate attributeName="xlink:href" values="#a-\1; #b-\1;#c" from="#b-\1"\/>/', $output);
            },
        ];

        yield 'Keeps colors that a non-link animation switches to' => [
            '<svg><rect id="fff"/><set attributeName="fill" to="#fff"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<set attributeName="fill" to="#fff"/>', $output);
            },
        ];

        yield 'Keeps ampersands in reference attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a"/><a href="x.svg?a=1&amp;b=2"><use href="#a"/></a><g aria-labelledby="a &amp;b"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<a href="x.svg?a=1&amp;b=2">', $output);
                self::assertMatchesRegularExpression('/aria-labelledby="a-[0-9a-f]{10} &amp;b"/', $output);
            },
        ];

        yield 'Scopes a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:style>.a{fill:red}</svg:style><svg:rect class="a"/></svg:svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<svg:style>\.a-([0-9a-f]{10})\{fill:red}<\/svg:style><svg:rect class="a-\1"\/>/', $output);
            },
        ];

        yield 'Updates a reference through a custom xlink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><rect id="a"/><use l:href="#a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<rect id="a-([0-9a-f]{10})"\/><use l:href="#a-\1"\/>/', $output);
            },
        ];

        yield 'Keeps every selector of a :where() list' => [
            '<svg><style>:where(text, rect, circle){fill:red}:is(.a, rect){stroke:red}</style><rect class="a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<style>:where\(text, rect, circle\)\{fill:red}:is\(\.a-([0-9a-f]{10}), rect\)\{stroke:red}<\/style><rect class="a-\1"\/>/', $output);
            },
        ];

        yield 'Scopes a class inside a :not() list' => [
            '<svg><style>rect:not(.a, .b){fill:red}</style><rect class="b"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/rect:not\(\.a-([0-9a-f]{10}), \.b-\1\)\{fill:red}<\/style><rect class="b-\1"\/>/', $output);
            },
        ];

        $nested = [
            'a layer block' => '<svg><style>@layer x{.a{fill:red}}</style><rect class="a" id="r"/></svg>',
            'a container block' => '<svg><style>@container (min-width:1px){.a{fill:red}}</style><rect class="a" id="r"/></svg>',
            'nested rules' => '<svg><style>.a{fill:red;&amp;:hover{fill:blue}}</style><rect class="a" id="r"/></svg>',
            'an escaped id selector' => '<svg><style>#\31 23{fill:red}</style><rect id="123"/></svg>',
            'an escaped url() reference in an attribute' => '<svg><mask id="m"/><rect style="mask:url(#\6d)"/></svg>',
        ];

        foreach ($nested as $name => $svg) {
            yield 'Leaves a document with ' . $name . ' untouched' => [
                $svg,
                static function (string $output) use ($svg): void {
                    self::assertSame($svg, $output);
                },
            ];
        }

        yield 'Updates every kind of reference to a renamed id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><rect id="a"/><g clip-path="url( &quot;#a&quot; )" mask="url(\'#a\')"/><use l:href="#a"/><animate begin="a.click+1s" end="a.end" attributeName="x"/><g aria-labelledby="x a  a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<rect id="a-([0-9a-f]{10})"\/><g clip-path="url\(&quot;#a-\1&quot;\)" mask="url\(\'#a-\1\'\)"\/><use l:href="#a-\1"\/><animate begin="a-\1.click\+1s" end="a-\1.end" attributeName="x"\/><g aria-labelledby="x a-\1 a-\1"\/>/', $output);
            },
        ];

        yield 'Renames every element with a duplicated id' => [
            '<svg><rect id="a"/><rect id="a"/><use href="#a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<rect id="a-([0-9a-f]{10})"\/><rect id="a-\1"\/><use href="#a-\1"\/>/', $output);
            },
        ];

        yield 'Scopes an id written with a character reference' => [
            '<svg><style>#a{fill:red}</style><rect id="&#97;"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<style>#a-([0-9a-f]{10})\{fill:red}<\/style><rect id="a-\1"\/>/', $output);
            },
        ];

        yield 'Scopes classes of several plain and CDATA style elements' => [
            '<svg><style>.a{fill:red}</style><style><![CDATA[.b{stroke:red}]]></style><rect class="a b"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.a-([0-9a-f]{10})\{fill:red}.*\.b-\1\{stroke:red}.*<rect class="a-\1 b-\1"\/>/s', $output);
            },
        ];

        yield 'Scopes an important declaration and a mixed-case class' => [
            '<svg><style>.MyClass{fill:red!important}</style><rect class="MyClass"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<style>\.MyClass-([0-9a-f]{10})\{fill:red!important}<\/style><rect class="MyClass-\1"\/>/', $output);
            },
        ];

        yield 'Scopes an element whose default namespace is reset' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><x id="a"/></g><use href="#a"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/<x id="a-([0-9a-f]{10})"\/><\/g><use href="#a-\1"\/>/', $output);
            },
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            static function (string $output): void {
                self::assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', $output);
            },
        ];

        yield 'Leaves external fragment links unchanged' => [
            '<svg><rect id="a"/><a href="other.svg#a"/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<a href="other.svg#a"/>', $output);
            },
        ];
    }

    #[Test]
    public function scopeHashIsTheStartOfTheMd5OfTheSvgMarkup(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><rect id="a"/></svg>');

        $documentElement = $domDocument->documentElement;
        self::assertInstanceOf(\DOMElement::class, $documentElement);

        $hash = mb_substr(sha1((string) $domDocument->saveXML($documentElement)), 0, 10);

        $scopeSvgStyles = new ScopeSvgStyles();
        $scopeSvgStyles->optimize($domDocument);

        $rect = $domDocument->getElementsByTagName('rect')->item(0);
        self::assertInstanceOf(\DOMElement::class, $rect);
        self::assertSame('a-' . $hash, $rect->getAttribute('id'));
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideScopingTwiceGivesTheSameResultCases')]
    #[Test]
    public function scopingTwiceGivesTheSameResult(string $content): void
    {
        $once = $this->scope($content);

        self::assertNotSame($content, $once);
        self::assertSame($once, $this->scope($once));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideScopingTwiceGivesTheSameResultCases(): iterable
    {
        yield 'Classes and ids in style rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}#b{fill:blue}.c #b{stroke:red}</style><rect class="a c" id="b"/></svg>',
        ];

        yield 'Ids referenced by url()' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"/><rect fill="url(#g)"/></svg>',
        ];

        yield 'Attribute selectors and href animations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[href="a.b"] .c{fill:red}</style><rect id="a" class="c"/><use href=" #a"><set attributeName="href" to="#a"/></use></svg>',
        ];

        yield 'Several svg elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><svg><style>.a{fill:red}</style><rect class="a" id="r"/></svg><svg><rect id="r2"/></svg></svg>',
        ];
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[Test]
    public function keepsNamesThatAreAlreadyScoped(): void
    {
        $content = '<svg xmlns="http://www.w3.org/2000/svg"><style>.a-0123456789{fill:red}.b{fill:blue}</style><rect class="a-0123456789 b" id="c-abcdef0123-1" fill="url(#c-abcdef0123-1)"/><rect id="d"/></svg>';

        $actual = $this->scope($content);

        self::assertStringContainsString('.a-0123456789{fill:red}', $actual);
        self::assertStringContainsString('id="c-abcdef0123-1"', $actual);
        self::assertStringContainsString('url(#c-abcdef0123-1)', $actual);
        self::assertMatchesRegularExpression('/\.b-[0-9a-f]{10}\{fill:blue}/', $actual);
        self::assertMatchesRegularExpression('/class="a-0123456789 b-[0-9a-f]{10}"/', $actual);
        self::assertMatchesRegularExpression('/id="d-[0-9a-f]{10}"/', $actual);
    }

    /**
     * @param list<string> $expectedPatterns
     *
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideUpdatesReferencesToRenamedIdsCases')]
    #[Test]
    public function updatesReferencesToRenamedIds(string $content, array $expectedPatterns): void
    {
        $actual = $this->scope($content);

        foreach ($expectedPatterns as $expectedPattern) {
            self::assertMatchesRegularExpression($expectedPattern, $actual);
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideUpdatesReferencesToRenamedIdsCases(): iterable
    {
        yield 'href and xlink:href fragments' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path id="p"/><use href="#p"/><use xlink:href="#p"/><use href="#other"/><a href="https://x.test/#p"/></svg>',
            [
                '/<path id="(p-[0-9a-f]{10})"\/><use href="#\1"\/><use xlink:href="#\1"\/>/',
                '/<use href="#other"\/>/',
                '/<a href="https:\/\/x\.test\/#p"\/>/',
            ],
        ];

        yield 'Animation timing references' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="b"/><animate begin="b.click; 2s; b.end+1s" end="b.repeat(1)" attributeName="x"/></svg>',
            ['/<rect id="(b-[0-9a-f]{10})"\/><animate begin="\1\.click; 2s; \1\.end\+1s" end="\1\.repeat\(1\)"/'],
        ];

        yield 'Animation timing references to ids that are not renamed' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="b"/><animate begin="b.click; x.end" attributeName="x"/></svg>',
            ['/<rect id="(b-[0-9a-f]{10})"\/><animate begin="\1\.click; x\.end"/'],
        ];

        yield 'ARIA id lists, but not ARIA text' => [
            '<svg xmlns="http://www.w3.org/2000/svg" aria-labelledby="t d other" aria-label="t d"><title id="t">a</title><desc id="d">b</desc><rect/></svg>',
            [
                '/aria-labelledby="t-([0-9a-f]{10}) d-\1 other"/',
                '/aria-label="t d"/',
            ],
        ];

        yield 'ARIA id lists padded with whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><title id="t">a</title><rect aria-labelledby=" t "/></svg>',
            ['/<title id="(t-[0-9a-f]{10})">a<\/title><rect aria-labelledby="\1"\/>/'],
        ];

        yield 'Animated links, but not animated attributes that only end with href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a"/><use><set attributeName="data-href" to="#a"/><set attributeName="x:href" to="#a"/></use></svg>',
            ['/<rect id="(a-[0-9a-f]{10})"\/><use><set attributeName="data-href" to="#a"\/><set attributeName="x:href" to="#\1"\/>/'],
        ];

        yield 'url() references in style elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path.lg{fill:url(#g)}#a{stroke:url(\'#g\')}#b{fill:url("#g")}#c{fill:url(#other)}</style><linearGradient id="g"/><rect/></svg>',
            [
                '/fill:url\(#(g-[0-9a-f]{10})\)}#a-[0-9a-f]{10}{stroke:url\(\'#\1\'\)}#b-[0-9a-f]{10}{fill:url\("#\1"\)}#c-[0-9a-f]{10}{fill:url\(#other\)}<\/style><linearGradient id="\1"\/>/',
            ],
        ];

        yield 'Quoted url() references in attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"/><rect fill="url(\'#g\')" stroke="url(&quot;#g&quot;)" filter="url(#g.x)"/></svg>',
            ['/<linearGradient id="(g-[0-9a-f]{10})"\/><rect fill="url\(\'#\1\'\)" stroke="url\(&quot;#\1&quot;\)" filter="url\(#g\.x\)"\/>/'],
        ];

        yield 'Style elements with characters that need escaping' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:url(#g)}text::after{content:"&amp;&lt;"}</style><linearGradient id="g"/><rect class="a"/></svg>',
            ['/<style>\.a-([0-9a-f]{10})\{fill:url\(#g-\1\)}text::after\{content:"&amp;&lt;"}<\/style>/'],
        ];

        yield 'An id containing a dot' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a.b"/><use href="#a.b" fill="url(#a.b)"/></svg>',
            ['/<rect id="(a\.b-[0-9a-f]{10})"\/><use href="#\1" fill="url\(#\1\)"\/>/'],
        ];
    }

    /**
     * @param list<string> $expectedPatterns
     *
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideKeepsFragmentTargetsCases')]
    #[Test]
    public function keepsFragmentTargets(string $content, array $expectedPatterns): void
    {
        $actual = $this->scope($content);

        foreach ($expectedPatterns as $expectedPattern) {
            self::assertMatchesRegularExpression($expectedPattern, $actual);
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideKeepsFragmentTargetsCases(): iterable
    {
        yield 'Symbol ids and the selectors and references naming them' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#s path{fill:red}#p{fill:blue}</style><symbol id="s"><path id="p"/></symbol><use href="#s"/></svg>',
            [
                '/<style>#s path\{fill:red}#p-[0-9a-f]{10}\{fill:blue}<\/style>/',
                '/<symbol id="s"><path id="p-[0-9a-f]{10}"\/><\/symbol><use href="#s"\/>/',
            ],
        ];

        yield 'View ids' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><view id="v" viewBox="0 0 1 1"/><rect id="r"/></svg>',
            ['/<view id="v" viewBox="0 0 1 1"\/><rect id="r-[0-9a-f]{10}"\/>/'],
        ];

        yield 'Every id when a style sheet shows elements by target, while classes are scoped' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>.i{display:none}.i:target{display:inline}</style><g id="one" class="i"/><rect id="r"/></svg>',
            [
                '/\.i-([0-9a-f]{10})\{display:none}\.i-\1:target\{display:inline}/',
                '/<g id="one" class="i-[0-9a-f]{10}"\/><rect id="r"\/>/',
            ],
        ];

        yield 'Groups in defs, while the gradients they use are renamed consistently' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="icon"><rect fill="url(#g)"/></g><linearGradient id="g"/></defs><use href="#icon"/></svg>',
            ['/<g id="icon"><rect fill="url\(#(g-[0-9a-f]{10})\)"\/><\/g><linearGradient id="\1"\/><\/defs><use href="#icon"\/>/'],
        ];

        yield 'Top-level ids of a document that renders nothing' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>#a{fill:red}</style><linearGradient id="a"/></svg>',
            ['/<style>#a\{fill:red}<\/style><linearGradient id="a"\/>/'],
        ];

        yield 'Nested svg elements, while their content is renamed' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><svg id="n"><rect id="r"/></svg></svg>',
            ['/<svg id="n"><rect id="r-[0-9a-f]{10}(?:-\d+)?"\/><\/svg>/'],
        ];
    }

    #[Test]
    public function ruleIsMarkedAsRisky(): void
    {
        self::assertTrue(ScopeSvgStyles::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(ScopeSvgStyles::shouldCheckSize());
    }

    #[Test]
    public function optimizeDoesNothingWhenNoSvgElementIsFound(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<root><child/></root>');

        $before = $domDocument->saveXML();

        $scopeSvgStyles = new ScopeSvgStyles();
        $scopeSvgStyles->optimize($domDocument);

        self::assertSame($before, $domDocument->saveXML());
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    private function scope(string $content): string
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new ScopeSvgStyles());

        return $svgOptimizer->allowRisky()->optimize()->getContent();
    }

    #[Test]
    public function handlesManyNamespaceDeclarationsInLinearTime(): void
    {
        $small = $this->secondsToOptimize(4_000);
        $large = $this->secondsToOptimize(16_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><style>.a{fill:red}</style><rect class="a" id="r" fill="url(#g)"/><use href="#r"/><svg><rect id="q"/></svg></svg>');

        $start = hrtime(true);
        (new ScopeSvgStyles())->optimize($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
