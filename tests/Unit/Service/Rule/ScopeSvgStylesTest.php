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
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
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
#[CoversClass(XmlFormatter::class)]
final class ScopeSvgStylesTest extends TestCase
{
    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
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
        yield 'Scopes simple class selector' => [
            '<svg><style>.cls-0{fill:#fff}</style><rect class="cls-0"/></svg>',
            static function (string $output): void {
                self::assertMatchesRegularExpression('/\.cls-0-[a-f0-9\-]+{fill:#fff}/', $output);
                self::assertMatchesRegularExpression('/class="cls-0-[a-f0-9\-]+"/', $output);
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
                self::assertStringNotContainsString('path{fill:red}', $output);
            },
        ];

        yield 'Removes universal selector' => [
            '<svg><style>*{fill:red}</style><rect/></svg>',
            static function (string $output): void {
                self::assertStringNotContainsString('*{fill:red}', $output);
            },
        ];

        yield 'No style tag present' => [
            '<svg><rect width="10"/></svg>',
            static function (string $output): void {
                self::assertSame('<svg><rect width="10"/></svg>', $output);
            },
        ];

        yield 'Handles empty style tag' => [
            '<svg><style></style><rect/></svg>',
            static function (string $output): void {
                self::assertStringContainsString('<rect/>', $output);
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
}
