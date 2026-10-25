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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveWidthHeightAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\AsciiCaseTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberFormatterTrait;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Trait\NumberListTrait;
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
#[CoversClass(RemoveWidthHeightAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversTrait(AsciiCaseTrait::class)]
#[CoversTrait(NumberFormatterTrait::class)]
#[CoversTrait(NumberListTrait::class)]
#[UsesClass(ExecutionOrder::class)]
final class RemoveWidthHeightAttributesTest extends TestCase
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
        $svgOptimizer->addPass(new RemoveWidthHeightAttributes());

        $actual = $svgOptimizer->allowRisky()->optimize()->getContent();

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes the size of a root with a view box written without separators' => [
            '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 400.5.5e3"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400.5.5e3"/>',
        ];

        yield 'Keeps the size of a root with a view box ending with a comma, which browsers reject' => [
            '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 400 400,"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 400 400,"/>',
        ];
        yield 'Keeps width and height with a viewBox number ending in a decimal point' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10. 10" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10. 10" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox of doubled commas' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0,,0,10,10" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0,,0,10,10" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox of five numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10 10" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10 10" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox of negative zero width' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 -0.0 10" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 -0.0 10" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox of zero height in exponent notation' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 0e5" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 0e5" width="10" height="10"/>',
        ];

        yield 'Removes width and height with a viewBox in exponent notation' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-1e1 0 +1e1 .5" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-1e1 0 +1e1 .5"/>',
        ];

        yield 'Keeps width and height without a viewBox' => [
            '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with an empty viewBox' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox of three numbers' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox that is not numeric' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 a 10" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 a 10" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox of zero width' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 10" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 10" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox of negative height' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 -1" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 -1" width="10" height="10"/>',
        ];

        yield 'Keeps width and height with a viewBox in another case' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 10 10" width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 10 10" width="10" height="10"/>',
        ];

        yield 'Removes width and height with a comma separated viewBox' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox=" 0,0, 10 ,10 " width="10" height="10"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox=" 0,0, 10 ,10 "/>',
        ];

        yield 'Removes width and height from root svg' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="100" height="200">
                    <rect width="100" height="200"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="100" height="200"/></svg>
                XML,
        ];

        yield 'Ignores when no width/height attributes exist' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Leaves nested svg width/height intact' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="300" height="300">
                    <svg width="100" height="100"><rect width="100" height="100"/></svg>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><svg width="100" height="100"><rect width="100" height="100"/></svg></svg>
                XML,
        ];

        yield 'Removes only width attribute if height missing' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="400">
                    <rect width="200" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="200" height="50"/></svg>
                XML,
        ];

        yield 'Removes only height attribute if width missing' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" height="400">
                    <rect width="200" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="200" height="50"/></svg>
                XML,
        ];

        yield 'Preserves viewBox attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50"/></svg>
                XML,
        ];

        yield 'Handles uppercase attribute names' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" WIDTH="500" HEIGHT="600">
                    <rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles mixed attribute case' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" Width="120" height="80">
                    <rect width="50" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="50" height="50"/></svg>
                XML,
        ];

        yield 'Handles extra whitespace and newlines in attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"  width = " 800 "
                     height = "600" >
                    <rect width="100" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="100" height="50"/></svg>
                XML,
        ];

        yield 'Ignores width/height on non-root elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="200" height="100">
                    <g width="300" height="400"><rect width="50" height="50"/></g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><g width="300" height="400"><rect width="50" height="50"/></g></svg>
                XML,
        ];

        yield 'Handles svg with namespace prefix' => [
            <<<'XML'
                <svg:svg xmlns:svg="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="100" height="100">
                    <svg:rect width="50" height="50"/>
                </svg:svg>
                XML,
            <<<'XML'
                <svg:svg xmlns:svg="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><svg:rect width="50" height="50"/></svg:svg>
                XML,
        ];

        yield 'Handles svg with percentage width/height' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="100%" height="100%">
                    <rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles svg with zero dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="0" height="0">
                    <circle r="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><circle r="50"/></svg>
                XML,
        ];

        yield 'Handles svg with fractional dimensions' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="10.5" height="20.75">
                    <circle r="5"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><circle r="5"/></svg>
                XML,
        ];

        yield 'Handles svg with px units' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="800px" height="600px">
                    <rect width="100" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="100" height="50"/></svg>
                XML,
        ];

        yield 'Handles svg with invalid numeric values gracefully' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="abc" height="123x">
                    <rect width="100" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="100" height="50"/></svg>
                XML,
        ];

        yield 'Leaves other root-level attributes untouched' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="400" height="300" id="icon" class="large">
                    <path d="M0,0L10,10"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" id="icon" class="large"><path d="M0,0L10,10"/></svg>
                XML,
        ];

        yield 'Keeps namespaced width and height attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" x:width="1" x:height="2" width="3" viewBox="0 0 3 3"/>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:x" x:width="1" x:height="2" viewBox="0 0 3 3"/>',
        ];

        yield 'Handles empty width/height attributes safely' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" width="" height="">
                    <rect width="100" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="100" height="50"/></svg>
                XML,
        ];
    }

    #[Test]
    public function optimizeLeavesADocumentWithoutRootAlone(): void
    {
        $domDocument = new \DOMDocument();

        (new RemoveWidthHeightAttributes())->apply($domDocument);

        self::assertNull($domDocument->documentElement);
    }
}
