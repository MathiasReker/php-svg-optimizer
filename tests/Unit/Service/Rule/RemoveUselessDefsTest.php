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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUselessDefs;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUselessDefs::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveUselessDefsTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveUselessDefs());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps a definition containing a style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><style>rect{fill:red}</style></g><g><path d="M0 0"/></g></defs><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><style>rect{fill:red}</style></g></defs><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps a reference-only element containing a style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol><style>rect{fill:red}</style></symbol><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol><style>rect{fill:red}</style></symbol><rect width="5" height="5"/></svg>',
        ];

        yield 'Removes definitions without an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient><stop offset="0"/></linearGradient><path d="M0 0"/></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Removes reference-only elements without an id outside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><radialGradient/><clipPath><rect/></clipPath><filter/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps definitions with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/><g><path id="p"/></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g"/><g><path id="p"/></g></defs></svg>',
        ];

        yield 'Keeps definitions with an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient xml:id="g"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient xml:id="g"/></defs></svg>',
        ];

        yield 'Keeps style inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>rect{fill:red}</style></defs><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>rect{fill:red}</style></defs><rect/></svg>',
        ];

        yield 'Keeps rendered elements outside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><path d="M0 0"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><path d="M0 0"/></g></svg>',
        ];

        yield 'Keeps empty defs with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs id="d"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs id="d"/></svg>',
        ];

        yield 'Keeps fonts inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><font horiz-adv-x="1"/><font-face font-family="x"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><font horiz-adv-x="1"/><font-face font-family="x"/></defs></svg>',
        ];

        yield 'Keeps defs with remaining text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs>text<path/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs>text</defs></svg>',
        ];

        yield 'Removes nested definitions without an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><linearGradient><stop/></linearGradient><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect/></g></svg>',
        ];

        yield 'Keeps reference-only elements with an id descendant' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol><g id="a"/></symbol></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><symbol><g id="a"/></symbol></svg>',
        ];

        yield 'Keeps an animation in defs that targets another element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" width="5" height="5"/><defs><set href="#r" attributeName="fill" to="red"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" width="5" height="5"/><defs><set href="#r" attributeName="fill" to="red"/></defs></svg>',
        ];

        yield 'Keeps an animateTransform in defs that targets another element through xlink:href' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r" width="5" height="5"/><defs><animateTransform xlink:href="#r" attributeName="transform" type="rotate" values="0;90" dur="1s"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><rect id="r" width="5" height="5"/><defs><animateTransform xlink:href="#r" attributeName="transform" type="rotate" values="0;90" dur="1s"/></defs></svg>',
        ];

        yield 'Keeps a discard in defs that targets another element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" width="5" height="5"/><defs><discard href="#r" begin="1s"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" width="5" height="5"/><defs><discard href="#r" begin="1s"/></defs></svg>',
        ];

        yield 'Keeps a group in defs that contains an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" width="5" height="5"/><defs><g><animate href="#r" attributeName="x" to="5" dur="1s"/></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" width="5" height="5"/><defs><g><animate href="#r" attributeName="x" to="5" dur="1s"/></g></defs></svg>',
        ];

        yield 'Keeps definitions when a style sheet depends on child positions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><g><defs><path d="M0 0"/></defs><rect/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:first-child{fill:red}</style><g><defs><path d="M0 0"/></defs><rect/></g></svg>',
        ];

        yield 'Keeps definitions when a style sheet uses a sibling combinator' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>linearGradient + rect{fill:red}</style><linearGradient/><rect/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>linearGradient + rect{fill:red}</style><linearGradient/><rect/></svg>',
        ];

        yield 'Keeps elements of other namespaces named like definitions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><metadata><x:mask xmlns:x="urn:x"/><filter xmlns="urn:y"/></metadata><mask/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><metadata><x:mask xmlns:x="urn:x"/><filter xmlns="urn:y"/></metadata></svg>',
        ];

        yield 'Removes definitions without an id in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:defs><svg:linearGradient/></svg:defs><svg:pattern id="p"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:pattern id="p"/></svg:svg>',
        ];

        yield 'Removes definitions in nested SVG content of a foreignObject' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg><marker/></svg></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><svg/></foreignObject></svg>',
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps a definition whose id is written with a character reference' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="&#97;"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="a"/></defs></svg>',
        ];

        yield 'Keeps every style in defs and removes the rest' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>rect{fill:red}</style><path d="M0 0"/><style>circle{fill:red}</style></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>rect{fill:red}</style><style>circle{fill:red}</style></defs></svg>',
        ];

        yield 'Keeps mixed-case elements that are not definitions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><LinearGradient/><clippath/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><LinearGradient/><clippath/></svg>',
        ];

        yield 'Removes a later definition without an id after keeping an earlier one with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"/><linearGradient/><radialGradient/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"/></svg>',
        ];

        yield 'Keeps a deeply nested descendant with an xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><g><g><rect xml:id="a"/></g></g></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><g><g><rect xml:id="a"/></g></g></g></defs></svg>',
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(RemoveUselessDefs::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveUselessDefs::shouldCheckSize());
    }
}
