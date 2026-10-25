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
use MathiasReker\PhpSvgOptimizer\Pass\RemoveUseBombs;
use MathiasReker\PhpSvgOptimizer\Pass\Trait\Dom\ElementNameTrait;
use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
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
 *
 * @no-named-arguments
 */
#[CoversClass(RemoveUseBombs::class)]
#[CoversClass(Attribute::class)]
#[CoversClass(Element::class)]
#[CoversClass(Parser::class)]
#[CoversClass(Pipeline::class)]
#[CoversClass(SvgValidator::class)]
#[CoversTrait(ElementNameTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(Serializer::class)]
final class RemoveUseBombsTest extends TestCase
{
    /**
     * @throws InvalidSvgException
     */
    #[DataProvider('provideApplyCases')]
    #[Test]
    public function apply(string $input, string $expectedSvg): void
    {
        $pipeline = new Pipeline([new RemoveUseBombs()]);

        self::assertSame($expectedSvg, $pipeline->run($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideApplyCases(): iterable
    {
        yield 'Removes a use element that references its own ancestor' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"><use href="#a"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g id="a"/></svg>',
        ];

        yield 'Removes use elements that reference each other' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="a"><use href="#b"/></g><g id="b"><use href="#a"/></g></defs><use href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="a"><use href="#b"/></g><g id="b"/></defs><use href="#a"/></svg>',
        ];

        yield 'Keeps use elements that reference other elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><rect id="r"/><g id="g"><use href="#r"/><use xlink:href="#r"/></g></defs><use href="#g"/><use href="other.svg#r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><rect id="r"/><g id="g"><use href="#r"/><use xlink:href="#r"/></g></defs><use href="#g"/><use href="other.svg#r"/></svg>',
        ];

        yield 'Leaves scripts, unsafe URLs, foreign content, style sheets and markup to the other rules' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><a href="javascript:x"><rect onclick="x()"/></a><foreignObject/><style>@import url(a.css);</style><!-- <b> --></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><?pi x?><script>alert(1)</script><a href="javascript:x"><rect onclick="x()"/></a><foreignObject/><style>@import url(a.css);</style><!-- <b> --></svg>',
        ];
    }
}
