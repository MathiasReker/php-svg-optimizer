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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveInvisibleCharacters;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Css\StyleSheetTrait;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementTrait;
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
#[CoversClass(RemoveInvisibleCharacters::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(StyleSheetTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveInvisibleCharactersTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveInvisibleCharacters()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);

        $repeated = new Pipeline([new RemoveInvisibleCharacters()]);
        self::assertSame($actual, $repeated->run($actual));
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Keeps a text node that would become empty when a style sheet selects empty elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:empty{fill:red}</style><rect>&#x200B;</rect><g>a&#x200B;</g></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><style>rect:empty{fill:red}</style><rect>\u{200b}</rect><g>a</g></svg>",
        ];

        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'keeps non-breaking spaces outside rendered text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g>&#xA0;</g></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><g>\u{A0}</g></svg>",
        ];

        yield 'removes invisible characters from character data outside rendered text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><![CDATA[&#x200B;]]>&#x200B;</g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g>&amp;#x200B;</g></svg>',
        ];

        yield 'removes invisible characters in a mixed-case element, which is not a text element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><TEXT>a&#xAD;b</TEXT></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><TEXT>ab</TEXT></svg>',
        ];

        yield 'removes invisible characters deep inside groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g>&#x2029;</g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g/></g></g></g></svg>',
        ];

        yield 'keeps joiners in rendered text, which shape emoji and scripts' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&#x1F469;&#x200D;&#x1F4BB; &#x645;&#x6CC;&#x200C;&#x62E;</text></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\u{1F469}\u{200D}\u{1F4BB} \u{645}\u{6CC}\u{200C}\u{62E}</text></svg>",
        ];

        yield 'keeps invisible characters in a tspan and a text path' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>a&#xAD;b</tspan><textPath href="#p">c&#x200B;d</textPath></text></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text><tspan>a\u{AD}b</tspan><textPath href=\"#p\">c\u{200B}d</textPath></text></svg>",
        ];

        yield 'keeps invisible characters in titles, descriptions and style sheets' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a&#x200D;b</title><desc>c&#xAD;d</desc><style>text::after{content:"&#x200B;"}</style></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><title>a\u{200D}b</title><desc>c\u{AD}d</desc><style>text::after{content:\"\u{200B}\"}</style></svg>",
        ];

        yield 'keeps invisible characters in foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><p xmlns="http://www.w3.org/1999/xhtml">a&#xAD;b</p></foreignObject><x:y xmlns:x="urn:x">c&#x200B;d</x:y></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><foreignObject><p xmlns=\"http://www.w3.org/1999/xhtml\">a\u{AD}b</p></foreignObject><x:y xmlns:x=\"urn:x\">c\u{200B}d</x:y></svg>",
        ];

        yield 'keeps invisible characters in attribute values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect aria-label="a&#x200D;b" width="5" height="5"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect aria-label=\"a\u{200D}b\" width=\"5\" height=\"5\"/></svg>",
        ];

        yield 'keeps line breaks in attribute values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0&#10;10 10"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><polyline points="0 0&#10;10 10"/></svg>',
        ];

        yield 'removes invisible characters between elements of a prefixed svg' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg">&#x200B;<svg:g>&#xAD;<svg:rect width="5" height="5"/></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g><svg:rect width="5" height="5"/></svg:g></svg:svg>',
        ];

        yield 'removes invisible characters around other text outside rendered text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g>a&#x2028;b&#x2029;c</g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g>abc</g></svg>',
        ];

        yield 'gives the same result with an XML declaration' => [
            '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg"><g>&#x200B;</g><text>a&#x200D;b</text></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><g/><text>a\u{200D}b</text></svg>",
        ];

        yield 'Remove Invisible Soft Hyphen' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">&#xAD;</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Remove Zero Width Space' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">&#x200B;</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Remove Zero Width Non-Joiner' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">&#x200C;</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Remove Zero Width Joiner' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">&#x200D;</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Remove Line Separator' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">&#x2028;</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Remove Paragraph Separator' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">&#x2029;</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Remove Invisible Characters with Newlines, Tabs, and Soft Hyphen' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">&#xAD;&#x0D;&#x0A;&#x09;</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'Remove Mixed Invisible Characters' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">&#xAD;&#x200B;&#x09;&#x0A;&#x2029;</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
        ];

        yield 'No Invisible Characters' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">Valid Content</svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">Valid Content</svg>
                XML,
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
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><g>&#x200B;</g></svg>');

        $start = hrtime(true);
        (new RemoveInvisibleCharacters())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
