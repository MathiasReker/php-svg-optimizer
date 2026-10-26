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
use MathiasReker\PhpSvgOptimizer\Pass\SortDefinitions;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AnimationTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ScriptingTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Id\IdTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Element;
use MathiasReker\PhpSvgOptimizer\Tests\Double\LinearTimeTrait;
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
#[CoversClass(SortDefinitions::class)]
#[CoversTrait(AnimationTrait::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(IdTrait::class)]
#[CoversTrait(ScriptingTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(Element::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Parser::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Serializer::class)]
#[UsesClass(SvgValidator::class)]
final class SortDefinitionsTest extends TestCase
{
    use LinearTimeTrait;

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideSortsDefinitionsCases')]
    #[Test]
    public function sortsDefinitions(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($input));
    }

    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideSortsDefinitionsCases')]
    #[Test]
    public function sortsDefinitionsIdempotently(string $input, string $expected): void
    {
        self::assertSame($expected, $this->optimizeContent($this->optimizeContent($input)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideSortsDefinitionsCases(): iterable
    {
        yield 'Groups children by frequency, then by name, keeping the order within a group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p1"/><linearGradient id="g1"/><clipPath id="c1"/><linearGradient id="g2"/><path id="p2"/><linearGradient id="g3"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g1"/><linearGradient id="g2"/><linearGradient id="g3"/><path id="p1"/><path id="p2"/><clipPath id="c1"/></defs></svg>',
        ];

        yield 'Orders groups of equal frequency by name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p"/><mask id="m"/><clipPath id="c"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="c"/><mask id="m"/><path id="p"/></defs></svg>',
        ];

        yield 'Keeps the original order within a group' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="b"/><mask id="m"/><path id="a"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="b"/><path id="a"/><mask id="m"/></defs></svg>',
        ];

        yield 'Keeps sorted children unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><path id="b"/><mask id="m"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><path id="b"/><mask id="m"/></defs></svg>',
        ];

        yield 'Sorts children separated by whitespace' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\">\n<defs>\n  <path id=\"p1\"/>\n  <mask id=\"m\"/>\n  <path id=\"p2\"/>\n</defs>\n</svg>",
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p1"/><path id="p2"/><mask id="m"/></defs></svg>',
        ];

        yield 'Sorts children of nested and deeply nested defs independently' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><defs><path id="p1"/><mask id="m1"/><path id="p2"/></defs></g></g><defs><mask id="m2"/><defs><path id="p3"/><mask id="m3"/><path id="p4"/></defs><mask id="m4"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><defs><path id="p1"/><path id="p2"/><mask id="m1"/></defs></g></g><defs><mask id="m2"/><mask id="m4"/><defs><path id="p3"/><path id="p4"/><mask id="m3"/></defs></defs></svg>',
        ];

        yield 'Sorts a later defs when the first one is skipped' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p1"/><font id="f"/><path id="p2"/></defs><defs><path id="p3"/><mask id="m"/><path id="p4"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p1"/><font id="f"/><path id="p2"/></defs><defs><path id="p3"/><path id="p4"/><mask id="m"/></defs></svg>',
        ];

        yield 'Skips defs with fonts, color profiles or discards' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><font-face id="f"/><path id="b"/></defs><defs><path id="c"/><color-profile id="cp"/><path id="d"/></defs><defs><path id="e"/><discard id="x" href="#r"/><path id="f2"/></defs><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><font-face id="f"/><path id="b"/></defs><defs><path id="c"/><color-profile id="cp"/><path id="d"/></defs><defs><path id="e"/><discard id="x" href="#r"/><path id="f2"/></defs><rect id="r"/></svg>',
        ];

        yield 'Skips defs with nested animations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"><set attributeName="d" to="M0 0"/></path><mask id="m"/><path id="b"/></defs><defs><path id="c"/><g id="g"><animateMotion path="M0 0h1"/></g><path id="d"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"><set attributeName="d" to="M0 0"/></path><mask id="m"/><path id="b"/></defs><defs><path id="c"/><g id="g"><animateMotion path="M0 0h1"/></g><path id="d"/></defs></svg>',
        ];

        yield 'Skips defs with foreign descendants' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><path id="a"/><g id="g"><x:b/></g><path id="b"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x"><defs><path id="a"/><g id="g"><x:b/></g><path id="b"/></defs></svg>',
        ];

        yield 'Skips defs inside foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg></foreignObject></svg>',
        ];

        yield 'Skips defs with text, comments or processing instructions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/>x<mask id="m1"/><path id="b"/></defs><defs><path id="c"/><!--c--><mask id="m2"/><path id="d"/></defs><defs><path id="e"/><?pi a?><mask id="m3"/><path id="f"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/>x<mask id="m1"/><path id="b"/></defs><defs><path id="c"/><!--c--><mask id="m2"/><path id="d"/></defs><defs><path id="e"/><?pi a?><mask id="m3"/><path id="f"/></defs></svg>',
        ];

        yield 'Keeps everything when the document has a style element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
        ];

        yield 'Keeps everything when the document has a script' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><script>f()</script><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>f()</script><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
        ];

        yield 'Keeps everything when the document has an event attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="f()"><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
        ];

        yield 'Keeps everything when two elements share an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><mask id="m"/><path id="a"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><mask id="m"/><path id="a"/></defs></svg>',
        ];

        yield 'Keeps everything when an id equals an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><mask id="m"/><path xml:id="a"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="a"/><mask id="m"/><path xml:id="a"/></defs></svg>',
        ];

        yield 'Sorts children with empty ids' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id=""/><mask id=""/><path id=""/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id=""/><path id=""/><mask id=""/></defs></svg>',
        ];

        yield 'Sorts children in a prefixed document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:path id="a"/><svg:mask id="m"/><svg:path id="b"/></svg:defs></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:path id="a"/><svg:path id="b"/><svg:mask id="m"/></svg:defs></svg:svg>',
        ];

        yield 'Sorts children in a document without a namespace' => [
            '<svg><defs><path id="a"/><mask id="m"/><path id="b"/></defs></svg>',
            '<svg><defs><path id="a"/><path id="b"/><mask id="m"/></defs></svg>',
        ];

        yield 'Keeps an empty document untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];
    }

    /**
     * @throws InvalidSvgException
     */
    private function optimizeContent(string $content): string
    {
        $pipeline = new Pipeline([new SortDefinitions()]);

        return $pipeline->run($content);
    }

    #[Test]
    public function checksManyIdsInLinearTime(): void
    {
        self::assertLinearTime(
            static function (int $size): \DOMDocument {
                $domDocument = new \DOMDocument();
                $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p"/><rect id="r"/></defs>' . implode('', array_map(static fn (int $i): string => '<g id="g' . $i . '"/>', range(1, $size))) . '</svg>');

                return $domDocument;
            },
            static function (\DOMDocument $domDocument): void {
                (new SortDefinitions())->apply($domDocument);
            },
            8_000,
        );
    }
}
