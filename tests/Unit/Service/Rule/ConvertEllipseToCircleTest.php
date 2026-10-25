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
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEllipseToCircle;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AttributeNodeTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\DocumentScriptTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\ElementTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\RenameElementTrait;
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
#[CoversClass(ConvertEllipseToCircle::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(AttributeNodeTrait::class)]
#[CoversTrait(DocumentScriptTrait::class)]
#[CoversTrait(ElementTrait::class)]
#[CoversTrait(NumberFormatterTrait::class)]
#[CoversTrait(RenameElementTrait::class)]
#[CoversTrait(SvgElementTrait::class)]
#[UsesClass(ExecutionOrder::class)]
#[UsesClass(SvgTag::class)]
final class ConvertEllipseToCircleTest extends TestCase
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
        $svgOptimizer->addPass(new ConvertEllipseToCircle());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Leaves the document untouched with an event handler' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5" onclick="f()"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5" onclick="f()"/></svg>',
        ];

        yield 'Leaves the document untouched with a linked style sheet' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><ellipse rx="5" ry="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><link xmlns="http://www.w3.org/1999/xhtml" rel="stylesheet" href="a.css"/></foreignObject><ellipse rx="5" ry="5"/></svg>',
        ];

        yield 'Keeps ellipses targeted by an animation through href' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"/><animate href="#e" attributeName="rx" to="5"/><ellipse rx="3" ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"/><animate href="#e" attributeName="rx" to="5"/><circle r="3"/></svg>',
        ];

        yield 'Converts ellipse with equal radii' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse cx="5" cy="5" rx="3" ry="3" fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" fill="red" r="3"/></svg>',
        ];

        yield 'Converts equal radii written differently' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3.0"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="3"/></svg>',
        ];

        yield 'Keeps ellipse with different radii' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="4"/></svg>',
        ];

        yield 'Keeps ellipse with auto radius' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="auto"/><ellipse rx="3"/><ellipse ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="auto"/><ellipse rx="3"/><ellipse ry="3"/></svg>',
        ];

        yield 'Keeps ellipse with style attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3" style="rx:4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3" style="rx:4"/></svg>',
        ];

        yield 'Keeps ellipse with child elements' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3"><set attributeName="rx" to="5"/></ellipse></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="3" ry="3"><set attributeName="rx" to="5"/></ellipse></svg>',
        ];

        yield 'Keeps ellipses when a style element exists' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>ellipse{fill:red}</style><ellipse rx="3" ry="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>ellipse{fill:red}</style><ellipse rx="3" ry="3"/></svg>',
        ];

        yield 'Keeps the id and children text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse id="e" rx="3" ry="3"><!--c--></ellipse></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle id="e" r="3"><!--c--></circle></svg>',
        ];

        yield 'Converts equal radii with surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx=" 3" ry="3 "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r=" 3"/></svg>',
        ];

        yield 'Converts every matching ellipse' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="1" ry="1"/><ellipse rx="1" ry="2"/><g><ellipse rx="2" ry="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="1"/><ellipse rx="1" ry="2"/><g><circle r="2"/></g></svg>',
        ];

        yield 'Keeps ellipses in other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><x:ellipse xmlns:x="urn:x" rx="1" ry="1"/><ellipse rx="1" ry="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><x:ellipse xmlns:x="urn:x" rx="1" ry="1"/><circle r="1"/></svg>',
        ];

        yield 'Keeps ellipses targeted through xml:id' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse xml:id="a" rx="5" ry="5"/><set href="#a" attributeName="rx" to="9"/><ellipse rx="5" ry="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse xml:id="a" rx="5" ry="5"/><set href="#a" attributeName="rx" to="9"/><circle r="5"/></svg>',
        ];

        yield 'Keeps ellipses when a stylesheet instruction exists' => [
            '<?xml-stylesheet type="text/css" href="s.css"?><svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5"/></svg>',
            '<?xml-stylesheet type="text/css" href="s.css"?><svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5" ry="5"/></svg>',
        ];

        yield 'Converts ellipses in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:ellipse cx="1" rx="5" ry="5.0"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:circle cx="1" r="5"/></svg:svg>',
        ];

        yield 'Keeps ellipses with radii in units or percentages' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5px" ry="5px"/><ellipse rx="5%" ry="5%"/><ellipse rx="auto" ry="auto"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5px" ry="5px"/><ellipse rx="5%" ry="5%"/><ellipse rx="auto" ry="auto"/></svg>',
        ];

        yield 'Keeps ellipses with only one radius' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5"/><ellipse ry="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><ellipse rx="5"/><ellipse ry="5"/></svg>',
        ];

        yield 'Keeps ellipses targeted by an animation through a custom xlink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><ellipse id="e" rx="5" ry="5"/><set l:href="#e" attributeName="rx" to="9"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><ellipse id="e" rx="5" ry="5"/><set l:href="#e" attributeName="rx" to="9"/></svg>',
        ];
    }

    /**
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     * @throws \LogicException
     */
    #[DataProvider('provideOptimizeIsIdempotentCases')]
    #[Test]
    public function optimizeIsIdempotent(string $optimized): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($optimized));
        $svgOptimizer->addPass(new ConvertEllipseToCircle());

        self::assertSame($optimized, $svgOptimizer->optimize()->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideOptimizeIsIdempotentCases(): iterable
    {
        foreach (self::provideOptimizeCases() as $name => [, $expected]) {
            yield $name => [$expected];
        }
    }
}
