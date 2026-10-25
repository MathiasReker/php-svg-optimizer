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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveEmptyAttributes;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\DeclarationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\SelectorTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
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
#[CoversClass(RemoveEmptyAttributes::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(DeclarationTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(SelectorTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveEmptyAttributesTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveEmptyAttributes()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);

        $repeated = new Pipeline([new RemoveEmptyAttributes()]);
        self::assertSame($actual, $repeated->run($actual));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Keeps empty crossorigin and autofocus' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><image crossorigin="" href="a.png"/><a autofocus="" class=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><image crossorigin="" href="a.png"/><a autofocus=""/></svg>',
        ];

        yield 'Removes empty attributes sharing a local name in different namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect a:class="" class="" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a"><rect width="5" height="5"/></svg>',
        ];

        yield 'Removes empty attributes after foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" class=""/></foreignObject><rect class="" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml" class=""></div></foreignObject><rect width="5" height="5"/></svg>',
        ];

        yield 'Leaves the document untouched with an external style sheet' => [
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect class=""/></svg>',
            '<?xml-stylesheet href="a.css"?><svg xmlns="http://www.w3.org/2000/svg"><rect class=""/></svg>',
        ];

        yield 'Leaves the document untouched with a style sheet loaded by an XHTML link' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml"><h:link rel="stylesheet" href="data:text/css,%5Bclass%5D%7Bfill:red%7D"/><rect class=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:h="http://www.w3.org/1999/xhtml"><h:link rel="stylesheet" href="data:text/css,%5Bclass%5D%7Bfill:red%7D"/><rect class=""/></svg>',
        ];

        yield 'Leaves the document untouched with an imported style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><rect class=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@import url(a.css);</style><rect class=""/></svg>',
        ];

        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps an attribute holding a non-breaking space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect class="&#xA0;" width="5" height="5"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect class=\"\u{A0}\" width=\"5\" height=\"5\"/></svg>",
        ];

        yield 'removes an attribute padded with a tab, a newline and a carriage return' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="&#9;&#10;&#13;" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5"/></svg>',
        ];

        yield 'removes empty mixed-case attributes, which are not the SVG attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a HREF="" Class=""><rect width="5" height="5"/></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a><rect width="5" height="5"/></a></svg>',
        ];

        yield 'keeps empty attributes inside an element reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect class="" width="5" height="5"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect class="" width="5" height="5"/></g></svg>',
        ];

        yield 'keeps an empty attribute selected in a style sheet inside character data' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style><![CDATA[ rect[class] { fill: red } ]]></style><rect class="" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style> rect[class] { fill: red } </style><rect class="" width="5" height="5"/></svg>',
        ];

        yield 'keeps an empty href, which links to the document itself' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a href=""><rect width="5" height="5"/></a><a xlink:href=""><rect width="5" height="5"/></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a href=""><rect width="5" height="5"/></a><a xlink:href=""><rect width="5" height="5"/></a></svg>',
        ];

        yield 'keeps an empty download, which downloads the link target' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="image.png" download=""><rect width="5" height="5"/></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="image.png" download=""><rect width="5" height="5"/></a></svg>',
        ];

        yield 'keeps an empty language, which marks the language as unknown' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:lang="en"><text xml:lang="">t</text><text lang="">u</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:lang="en"><text xml:lang="">t</text><text lang="">u</text></svg>',
        ];

        yield 'keeps the empty attributes of animation elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" fill="red"><animate attributeName="fill" from="" to="blue" dur="1s"/><set attributeName="x" begin="" to="1"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" fill="red"><animate attributeName="fill" from="" to="blue" dur="1s"/><set attributeName="x" begin="" to="1"/></rect></svg>',
        ];

        yield 'removes empty attributes of prefixed svg elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect class="" fill=" " width="5" height="5"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect width="5" height="5"/></svg:svg>',
        ];

        yield 'removes an empty namespaced attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect x:label="" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><rect width="5" height="5"/></svg>',
        ];

        yield 'keeps empty attributes of elements in another namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><input xmlns="http://www.w3.org/1999/xhtml" value="" disabled=""/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><input xmlns="http://www.w3.org/1999/xhtml" value="" disabled=""/></foreignObject></svg>',
        ];

        yield 'removes an empty attribute after a kept one on the same element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect systemLanguage="" class="" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect systemLanguage="" width="5" height="5"/></svg>',
        ];

        yield 'removes whitespace-only attributes with tabs and newlines' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect class="&#9;&#10; " width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps empty conditional processing attributes, which keep the element from rendering' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" systemLanguage=""/><rect width="5" height="5" requiredExtensions=" "/><rect width="5" height="5" requiredFeatures="" fill=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="5" height="5" systemLanguage=""/><rect width="5" height="5" requiredExtensions=" "/><rect width="5" height="5" requiredFeatures=""/></svg>',
        ];

        yield 'Keeps empty attributes on elements outside the SVG namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10" class=""><div xmlns="http://www.w3.org/1999/xhtml" hidden="">a<input type="checkbox" checked=""/></div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><div xmlns="http://www.w3.org/1999/xhtml" hidden="">a<input type="checkbox" checked=""/></div></foreignObject></svg>',
        ];

        yield 'Removes an empty namespaced attribute without touching a same-named plain attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a title="keep" xlink:title=""/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a title="keep"/></svg>',
        ];

        yield 'Keeps a non-empty namespaced attribute when a same-named plain attribute is empty' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a title=" " xlink:title="keep"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:title="keep"/></svg>',
        ];

        yield 'Removes empty attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Removes empty attribute with whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <circle fill="green" r="30" cx="50" cy="50" stroke-width="   " />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle fill="green" r="30" cx="50" cy="50"/></svg>
                XML,
        ];

        yield 'No empty attributes to remove' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Keeps an empty attribute that a style sheet selects' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[fill]{stroke:blue}</style><rect fill="" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[fill]{stroke:blue}</style><rect fill="" width="5" height="5"/></svg>',
        ];

        yield 'Keeps an empty namespaced attribute that a style sheet selects' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>[xlink|href=""]{stroke:blue}</style><a xlink:href=""><rect width="5" height="5"/></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><style>[xlink|href=""]{stroke:blue}</style><a xlink:href=""><rect width="5" height="5"/></a></svg>',
        ];

        yield 'Removes an empty attribute when a style sheet selects another attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[fill-rule]{stroke:blue}</style><rect fill="" width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[fill-rule]{stroke:blue}</style><rect width="5" height="5"/></svg>',
        ];

        yield 'Handles multiple empty attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="" stroke-width="   " />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles nested elements with empty attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <circle fill="red" stroke-width="   " />
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle fill="red"/></g></svg>
                XML,
        ];

        yield 'Handles empty attributes in complex nested structure' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <circle fill="yellow" stroke-width="   " />
                        <rect fill="" width="50" height="50"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><circle fill="yellow"/><rect width="50" height="50"/></g></svg>
                XML,
        ];
    }

    #[Test]
    public function handlesManyEmptyAttributesAndAttributeSelectorsInLinearTime(): void
    {
        $small = $this->secondsToOptimize(1_000);
        $large = $this->secondsToOptimize(4_000);

        self::assertLessThan(10 * max($small, 0.05), $large);
    }

    private function secondsToOptimize(int $count): float
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><style>' . str_repeat('[class-x] ', 25 * $count) . '{fill:red}</style>' . str_repeat('<rect class=""/>', $count) . '</svg>');

        $start = hrtime(true);
        (new RemoveEmptyAttributes())->apply($domDocument);

        self::assertFalse($domDocument->documentElement?->lastElementChild?->hasAttributes());

        return (hrtime(true) - $start) / 1e9;
    }
}
