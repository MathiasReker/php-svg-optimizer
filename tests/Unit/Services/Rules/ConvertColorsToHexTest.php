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
use MathiasReker\PhpSvgOptimizer\Services\Rules\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertColorsToHex::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class ConvertColorsToHexTest extends TestCase
{
    public static function svgContentProvider(): \Iterator
    {
        yield 'Converts RGB colors to Hex' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(255, 0, 0)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 255, 0)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f00"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Keeps existing Hex colors unchanged' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="#f00"/>
                    <circle cx="50" cy="50" r="40" stroke="#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f00"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Converts RGB colors with additional attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(255, 0, 0)" id="rect1"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 255, 0)" class="circle"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f00" id="rect1"/><circle cx="50" cy="50" r="40" stroke="#0f0" class="circle"/></svg>
                XML,
        ];

        yield 'Handles Hex colors with short notation' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="#F00"/>
                    <circle cx="50" cy="50" r="40" stroke="#0F0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f00"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Ignores colors in non-standard attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" data-color="rgb(255, 255, 255)" />
                    <circle cx="50" cy="50" r="40" data-stroke="rgb(0, 0, 255)" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" data-color="rgb(255, 255, 255)"/><circle cx="50" cy="50" r="40" data-stroke="rgb(0, 0, 255)"/></svg>
                XML,
        ];

        yield 'Converts rgba colors to Hex with opacity' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgba(255, 0, 0, 0.5)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgba(0, 255, 0, 0.3)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="rgba(255, 0, 0, 0.5)"/><circle cx="50" cy="50" r="40" stroke="rgba(0, 255, 0, 0.3)"/></svg>
                XML,
        ];

        yield 'Ignore color names' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="red"/>
                    <circle cx="50" cy="50" r="40" stroke="lime"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/><circle cx="50" cy="50" r="40" stroke="lime"/></svg>
                XML,
        ];

        yield 'Ignores rgba colors (alpha unsupported)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgba(255, 0, 0, 0.5)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgba(0, 255, 0, 0.3)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="rgba(255, 0, 0, 0.5)"/><circle cx="50" cy="50" r="40" stroke="rgba(0, 255, 0, 0.3)"/></svg>
                XML,
        ];

        yield 'Converts multiple elements with mixed colors' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="50" height="50" fill="rgb(128, 128, 128)"/>
                    <circle cx="25" cy="25" r="20" stroke="rgb(0, 128, 0)"/>
                    <ellipse cx="50" cy="50" rx="20" ry="10" fill="rgb(255, 255, 255)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="50" height="50" fill="#808080"/><circle cx="25" cy="25" r="20" stroke="#008000"/><ellipse cx="50" cy="50" rx="20" ry="10" fill="#fff"/></svg>
                XML,
        ];

        yield 'Ignores invalid color values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(300, 255, -50)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 300, 0)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="rgb(300, 255, -50)"/><circle cx="50" cy="50" r="40" stroke="rgb(0, 300, 0)"/></svg>
                XML,
        ];

        yield 'Handles colors with extra spaces and mixed formats' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill=" rgb(  255 , 255 ,  255  ) "/>
                    <circle cx="50" cy="50" r="40" stroke=" rgb(0,0,0) "/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#fff"/><circle cx="50" cy="50" r="40" stroke="#000"/></svg>
                XML,
        ];

        yield 'Ignore unsupported color formats (HSL)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="hsl(120, 100%, 50%)"/>
                    <circle cx="50" cy="50" r="40" stroke="hsl(0, 100%, 50%)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="hsl(120, 100%, 50%)"/><circle cx="50" cy="50" r="40" stroke="hsl(0, 100%, 50%)"/></svg>
                XML,
        ];

        yield 'Converts nested svg elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <svg x="50" y="50" width="100" height="100">
                        <rect width="100" height="100" fill="rgb(0, 0, 255)"/>
                    </svg>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><svg x="50" y="50" width="100" height="100"><rect width="100" height="100" fill="#00f"/></svg></svg>
                XML,
        ];

        yield 'Converts RGB to shorthand Hex format (#rgb)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(255, 0, 0)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 255, 0)"/>
                    <line x1="0" y1="0" x2="100" y2="100" stroke="rgb(0, 0, 255)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f00"/><circle cx="50" cy="50" r="40" stroke="#0f0"/><line x1="0" y1="0" x2="100" y2="100" stroke="#00f"/></svg>
                XML,
        ];

        yield 'Ignores colors that cannot be shortened' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(128, 128, 128)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 128, 0)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#808080"/><circle cx="50" cy="50" r="40" stroke="#008000"/></svg>
                XML,
        ];

        yield 'Converts RGB with leading/trailing spaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="   rgb( 255, 0, 0 )   "/>
                    <circle cx="50" cy="50" r="40" stroke="   rgb(0, 255, 0)   "/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f00"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Handles missing color values in RGB' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(255, 0)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, , 0)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="rgb(255, 0)"/><circle cx="50" cy="50" r="40" stroke="rgb(0, , 0)"/></svg>
                XML,
        ];

        yield 'Ignores RGB colors with invalid values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(-1, 256, 300)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(256, 256, 256)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="rgb(-1, 256, 300)"/><circle cx="50" cy="50" r="40" stroke="rgb(256, 256, 256)"/></svg>
                XML,
        ];

        yield 'Properly handles inline SVGs with nested elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <rect width="50" height="50" fill="rgb(128, 128, 128)"/>
                        <circle cx="25" cy="25" r="20" stroke="rgb(0, 128, 0)"/>
                    </g>
                    <g>
                        <ellipse cx="75" cy="75" rx="20" ry="10" fill="rgb(255, 255, 255)"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect width="50" height="50" fill="#808080"/><circle cx="25" cy="25" r="20" stroke="#008000"/></g><g><ellipse cx="75" cy="75" rx="20" ry="10" fill="#fff"/></g></svg>
                XML,
        ];

        yield 'Handles multiple color attributes on same element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(255, 0, 0)" stroke="rgb(0, 255, 0)"/>
                    <circle cx="50" cy="50" r="40" fill="rgb(0, 0, 255)" stroke="rgb(255, 255, 0)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f00" stroke="#0f0"/><circle cx="50" cy="50" r="40" fill="#00f" stroke="#ff0"/></svg>
                XML,
        ];

        yield 'Handles non-standard attributes with RGB values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" data-color="rgb(255, 0, 0)" />
                    <circle cx="50" cy="50" r="40" data-stroke="rgb(0, 255, 0)" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" data-color="rgb(255, 0, 0)"/><circle cx="50" cy="50" r="40" data-stroke="rgb(0, 255, 0)"/></svg>
                XML,
        ];

        yield 'Handles colors with extra whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill=" rgb(255, 0, 0) "/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 255, 0)  "/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f00"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Ignore unsupported color formats (HSL and others)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="hsl(120, 100%, 50%)"/>
                    <circle cx="50" cy="50" r="40" stroke="hsla(240, 100%, 50%, 0.5)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="hsl(120, 100%, 50%)"/><circle cx="50" cy="50" r="40" stroke="hsla(240, 100%, 50%, 0.5)"/></svg>
                XML,
        ];
    }

    #[DataProvider('svgContentProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new ConvertColorsToHex());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
