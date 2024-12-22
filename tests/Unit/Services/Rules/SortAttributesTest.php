<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Rules;

use MathiasReker\PhpSvgOptimizer\Models\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Services\Providers\StringProvider;
use MathiasReker\PhpSvgOptimizer\Services\Rules\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SortAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class SortAttributesTest extends TestCase
{
    public static function svgAttributesProvider(): \Iterator
    {
        yield 'Sorts Attributes in Correct Order' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="none" stroke="black" id="rect1">
                    <rect id="rect2" stroke="red" x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" id="rect1" fill="none" stroke="black"><rect id="rect2" width="30" height="30" stroke="red" x="10" y="10"/></svg>
                XML,
        ];

        yield 'Sorts Attributes with Custom Order' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="100" height="100" stroke="black" id="rect1">
                    <rect id="rect2" x="10" y="10" width="30" height="30" stroke="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" id="rect1" fill="none" stroke="black"><rect id="rect2" width="30" height="30" stroke="red" x="10" y="10"/></svg>
                XML,
        ];

        yield 'Does Not Change Order of Already Sorted Attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" id="rect1" width="100" height="100" fill="none" stroke="black">
                    <rect id="rect2" x="10" y="10" width="30" height="30" stroke="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" id="rect1" width="100" height="100" fill="none" stroke="black"><rect id="rect2" width="30" height="30" stroke="red" x="10" y="10"/></svg>
                XML,
        ];

        yield 'Sorts Multiple Attributes Alphabetically' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" cx="50" cy="50" r="20" fill="red" stroke="none"><circle /></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" cx="50" cy="50" fill="red" r="20" stroke="none"><circle/></svg>
                XML,
        ];

        yield 'Sorts Attributes Inside Groups Correctly' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g fill="none" stroke="black" id="group1">
                        <rect x="10" y="10" width="30" height="30" id="rect1"/>
                        <circle cx="50" cy="50" r="20" stroke="red"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g id="group1" fill="none" stroke="black"><rect width="30" height="30" id="rect1" x="10" y="10"/><circle cx="50" cy="50" r="20" stroke="red"/></g></svg>
                XML,
        ];

        yield 'Handles Attributes with Different Prefixes Correctly' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30"/>
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100" height="100"><rect width="30" height="30" x="10" y="10"/><circle cx="50" cy="50" fill="red" r="20"/></svg>
                XML,
        ];
    }

    #[DataProvider('svgAttributesProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new SortAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
