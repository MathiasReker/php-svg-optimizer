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
use MathiasReker\PhpSvgOptimizer\Pass\ExecutionOrder;
use MathiasReker\PhpSvgOptimizer\Service\Optimizer\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortDefsChildren;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DefinitionElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\SvgElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SortDefsChildren::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(DefinitionElementTrait::class)]
#[CoversTrait(DocumentScriptTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(SvgTag::class)]
final class SortDefsChildrenTest extends TestCase
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
        self::assertSame($expected, $this->optimizeContent($content));
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
        self::assertSame($expected, $this->optimizeContent($this->optimizeContent($content)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
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
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    private function optimizeContent(string $content): string
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addPass(new SortDefsChildren());

        return $svgOptimizer->optimize()->getContent();
    }
}
