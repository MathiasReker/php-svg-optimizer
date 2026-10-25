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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveDefaultXmlSpace;
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
#[CoversClass(RemoveDefaultXmlSpace::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveDefaultXmlSpaceTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expected): void
    {
        $pipeline = new Pipeline([new RemoveDefaultXmlSpace()]);

        $actual = $pipeline->run($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Removes xml:space="default" from the root' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="default"><text>a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text></svg>',
        ];

        yield 'Removes xml:space="default" from nested elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><text xml:space="default">a<tspan xml:space="default">b</tspan></text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><text>a<tspan>b</tspan></text></g></svg>',
        ];

        yield 'Removes xml:space="default" with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space=" default ">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text></svg>',
        ];

        yield 'Removes xml:space="default" inside a default ancestor' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="default"><text xml:space="default">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text></svg>',
        ];

        yield 'Keeps xml:space="default" inside a preserve ancestor' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><text xml:space="default">a  b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><text xml:space="default">a b</text></svg>',
        ];

        yield 'Keeps xml:space="default" inside a distant preserve ancestor' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><g><g><text><tspan xml:space="default">a</tspan></text></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><g><g><text><tspan xml:space="default">a</tspan></text></g></g></svg>',
        ];

        yield 'Keeps xml:space="default" inside a preserve ancestor with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space=" preserve "><tspan xml:space="default">a</tspan></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space=" preserve "><tspan xml:space="default">a</tspan></text></svg>',
        ];

        yield 'Keeps xml:space="default" inside an ancestor with another value' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="other"><text xml:space="default">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="other"><text xml:space="default">a</text></svg>',
        ];

        yield 'Keeps the overriding default but removes the redundant one below it' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><g xml:space="default"><text xml:space="default">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><g xml:space="default"><text>a</text></g></svg>',
        ];

        yield 'Removes default below a removed default ancestor' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xml:space="default"><g xml:space="default"><text xml:space="default">a</text></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><text>a</text></g></g></svg>',
        ];

        yield 'Removes defaults in sibling subtrees independently' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xml:space="preserve"><text xml:space="default">a</text></g><g><text xml:space="default">b</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xml:space="preserve"><text xml:space="default">a</text></g><g><text>b</text></g></svg>',
        ];

        yield 'Keeps xml:space="preserve"' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">a  b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">a  b</text></svg>',
        ];

        yield 'Keeps other xml:space values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="Default">a</text><text xml:space="">b</text><text xml:space="defaults">c</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="Default">a</text><text xml:space="">b</text><text xml:space="defaults">c</text></svg>',
        ];

        yield 'Keeps unprefixed and other namespaced space attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><text space="default" c:space="default">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:c="http://www.customnamespace.com"><text space="default" c:space="default">a</text></svg>',
        ];

        yield 'Keeps xml:space="default" when the element sets white-space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="default" style="WHITE-SPACE: pre">a</text><text xml:space="default" white-space="pre">b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="default" style="WHITE-SPACE: pre">a</text><text xml:space="default" white-space="pre">b</text></svg>',
        ];

        yield 'Keeps xml:space="default" when an ancestor sets white-space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:red;white-space:pre"><text><tspan xml:space="default">a</tspan></text></g><text white-space="pre"><tspan xml:space="default">b</tspan></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:red;white-space:pre"><text><tspan xml:space="default">a</tspan></text></g><text white-space="pre"><tspan xml:space="default">b</tspan></text></svg>',
        ];

        yield 'Removes xml:space="default" when other elements set white-space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="white-space:pre">a</text><text xml:space="default" style="fill:red">b<tspan style="white-space:pre">c</tspan></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text style="white-space:pre">a</text><text style="fill:red">b<tspan style="white-space:pre">c</tspan></text></svg>',
        ];

        yield 'Keeps everything when a style element sets white-space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>text{White-Space:pre}</style><text xml:space="default">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>text{White-Space:pre}</style><text xml:space="default">a</text></svg>',
        ];

        yield 'Keeps everything when a style element imports a style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@IMPORT url(a.css);</style><text xml:space="default">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>@IMPORT url(a.css);</style><text xml:space="default">a</text></svg>',
        ];

        yield 'Removes xml:space="default" when style elements do not set white-space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>text{fill:red}</style><text xml:space="default">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>text{fill:red}</style><text>a</text></svg>',
        ];

        yield 'Keeps everything when an external style sheet is referenced' => [
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><text xml:space="default">a</text></svg>',
            '<?xml-stylesheet href="a.css" type="text/css"?><svg xmlns="http://www.w3.org/2000/svg"><text xml:space="default">a</text></svg>',
        ];

        yield 'Removes xml:space="default" when other processing instructions are present' => [
            '<?other data?><svg xmlns="http://www.w3.org/2000/svg"><text xml:space="default">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text></svg>',
        ];

        yield 'Removes xml:space="default" in documents without a namespace' => [
            '<svg><text xml:space="default">a</text></svg>',
            '<svg><text>a</text></svg>',
        ];

        yield 'Keeps an uppercase value, which is not the default keyword' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve"><tspan xml:space="DEFAULT">a</tspan></text><text xml:space="DEFAULT">b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve"><tspan xml:space="DEFAULT">a</tspan></text><text xml:space="DEFAULT">b</text></svg>',
        ];

        yield 'Keeps a default nested deeply below a preserve' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><g><g><text><tspan xml:space="default">a</tspan></text></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><g><g><text><tspan xml:space="default">a</tspan></text></g></g></svg>',
        ];

        yield 'Removes a default below a default that is below a preserve, after keeping the first' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><g xml:space="default"><text xml:space="default">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve"><g xml:space="default"><text>a</text></g></svg>',
        ];

        yield 'Removes xml:space="default" in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:text xml:space="default">a</svg:text></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:text>a</svg:text></svg:svg>',
        ];

        yield 'Keeps xml:space="default" when a white-space presentation attribute is on an ancestor' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g white-space="pre"><text xml:space="default">a</text></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g white-space="pre"><text xml:space="default">a</text></g></svg>',
        ];

        yield 'Keeps documents without xml:space untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a</text></svg>',
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
        $domDocument->loadXML('<svg xmlns="http://www.w3.org/2000/svg" ' . implode(' ', array_map(static fn (int $i): string => 'xmlns:n' . $i . '="urn:' . $i . '"', range(1, $count))) . '><g xml:space="default"><text xml:space="default">a</text></g></svg>');

        $start = hrtime(true);
        (new RemoveDefaultXmlSpace())->apply($domDocument);

        return (hrtime(true) - $start) / 1e9;
    }
}
