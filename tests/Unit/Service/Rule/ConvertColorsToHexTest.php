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
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertColorsToHex;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertColorsToHex::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgAttribute::class)]
final class ConvertColorsToHexTest extends TestCase
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
        $svgOptimizer->addPass(new ConvertColorsToHex());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Leaves the document untouched when a style sheet selects attribute values' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[fill="#FF0000"]{stroke:red}</style><rect fill="#FF0000"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>[fill="#FF0000"]{stroke:red}</style><rect fill="#FF0000"/></svg>',
        ];

        yield 'Converts RGB colors to Hex' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(255, 0, 0)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 255, 0)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
                XML,
        ];

        yield 'Writes hex colors as shorter named colors' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="#f00"/>
                    <circle cx="50" cy="50" r="40" stroke="#0f0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red" id="rect1"/><circle cx="50" cy="50" r="40" stroke="#0f0" class="circle"/></svg>
                XML,
        ];

        yield 'Lowercases short hex colors' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="#F00"/>
                    <circle cx="50" cy="50" r="40" stroke="#0F0"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="50" height="50" fill="gray"/><circle cx="25" cy="25" r="20" stroke="green"/><ellipse cx="50" cy="50" rx="20" ry="10" fill="#fff"/></svg>
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

        yield 'Converts every channel value up to 255, with or without leading zeros' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="rgb(255,250,249)"/><rect fill="rgb(200,199,100)"/><rect fill="rgb(099,09,010)"/><rect fill="rgb(256,0,0)"/><rect fill="rgb(0,260,0)"/><rect fill="rgb(0,0,300)"/><rect fill="rgb(0,0,0255)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#fffaf9"/><rect fill="#c8c764"/><rect fill="#63090a"/><rect fill="rgb(256,0,0)"/><rect fill="rgb(0,260,0)"/><rect fill="rgb(0,0,300)"/><rect fill="rgb(0,0,0255)"/></svg>',
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/><circle cx="50" cy="50" r="40" stroke="#0f0"/><line x1="0" y1="0" x2="100" y2="100" stroke="#00f"/></svg>
                XML,
        ];

        yield 'Writes RGB colors as shorter named colors' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="rgb(128, 128, 128)"/>
                    <circle cx="50" cy="50" r="40" stroke="rgb(0, 128, 0)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="gray"/><circle cx="50" cy="50" r="40" stroke="green"/></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect width="50" height="50" fill="gray"/><circle cx="25" cy="25" r="20" stroke="green"/></g><g><ellipse cx="75" cy="75" rx="20" ry="10" fill="#fff"/></g></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red" stroke="#0f0"/><circle cx="50" cy="50" r="40" fill="#00f" stroke="#ff0"/></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/><circle cx="50" cy="50" r="40" stroke="#0f0"/></svg>
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

        yield 'Handles style attribute with RGB values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" style="fill:rgb(255, 0, 0)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" style="fill:red"/></svg>
                XML,
        ];

        yield 'Handles style attribute with Hex color' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" style="fill:#FBB040"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" style="fill:#fbb040"/></svg>
                XML,
        ];

        yield 'Ensures hex color is lowercase' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="#FBB041"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#fbb041"/></svg>
                XML,
        ];

        yield 'Converts RGB to hex in style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect style="fill:rgb(255, 0, 0);" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:red;"/></svg>
                XML,
        ];

        yield 'Lowercases hex in style attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle style="stroke:#ABCDEF;" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle style="stroke:#abcdef;"/></svg>
                XML,
        ];

        yield 'Handles RGB shorthand conversion (canBeShortened)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <path style="fill:rgb(17, 34, 51);" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><path style="fill:#123;"/></svg>
                XML,
        ];

        yield 'Preserves already-lowercase hex' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <ellipse style="fill:#abcdef;" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><ellipse style="fill:#abcdef;"/></svg>
                XML,
        ];

        yield "Don't normalizes hex in text node" => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>#ABCDEF</text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text>#ABCDEF</text></svg>
                XML,
        ];

        yield "Don't convert RGB in text node" => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text>rgb(255,255,255)</text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text>rgb(255,255,255)</text></svg>
                XML,
        ];

        yield "Don't modify text nodes or non-color attributes" => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text style="fill:url(#svgid_1_);">color</text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text style="fill:url(#svgid_1_);">color</text></svg>
                XML,
        ];

        yield "Don't modify url() or non-color style values" => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="100" height="100" style="fill:url(#svgid_1_);" />
                    <circle cx="50" cy="50" r="40" style="stroke:url(#MyGradient);" />
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" style="fill:url(#svgid_1_);"/><circle cx="50" cy="50" r="40" style="stroke:url(#MyGradient);"/></svg>
                XML,
        ];

        yield 'Shortens hex with repeated digits' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#AABBCC"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#abc"/></svg>',
        ];

        yield 'Keeps hex without repeated digits in every channel' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#AABBCD"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#aabbcd"/></svg>',
        ];

        yield 'Keeps hex with a repeated digit across channels' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#ABBCCA"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#abbcca"/></svg>',
        ];

        yield 'Writes hex as a shorter named color' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#FF0000"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Writes long hex as a shorter named color' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#D2B48C"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="tan"/></svg>',
        ];

        yield 'Writes RGB as a shorter named color' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="rgb(210, 180, 140)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="tan"/></svg>',
        ];

        yield 'Writes gray hex as the first shorter name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#808080"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="gray"/></svg>',
        ];

        yield 'Shortens hex that has no shorter name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#FF00FF"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f0f"/></svg>',
        ];

        yield 'Writes longer named color as hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="fuchsia"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f0f"/></svg>',
        ];

        yield 'Writes magenta as hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="magenta"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f0f"/></svg>',
        ];

        yield 'Writes white as hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="white"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#fff"/></svg>',
        ];

        yield 'Writes named colors case-insensitively' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="WHITE"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#fff"/></svg>',
        ];

        yield 'Writes yellow as hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="yellow"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#ff0"/></svg>',
        ];

        yield 'Writes black as hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="black"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#000"/></svg>',
        ];

        yield 'Writes rebeccapurple as hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="rebeccapurple"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#639"/></svg>',
        ];

        yield 'Writes long named color as long hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="lightgoldenrodyellow"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#fafad2"/></svg>',
        ];

        yield 'Keeps named color as long as its hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="darkred"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="darkred"/></svg>',
        ];

        yield 'Keeps named color as long as its short hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="aqua"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="aqua"/></svg>',
        ];

        yield 'Keeps cyan as long as its short hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="cyan"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="cyan"/></svg>',
        ];

        yield 'Keeps uppercase named color that is not longer' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="Red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="Red"/></svg>',
        ];

        yield 'Keeps blue' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="blue"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="blue"/></svg>',
        ];

        yield 'Keeps grey' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="grey"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="grey"/></svg>',
        ];

        yield 'Writes hex without a shorter name as short hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#00FFFF"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#0ff"/></svg>',
        ];

        yield 'Writes hex as hex on a tie with a name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#0000FF"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#00f"/></svg>',
        ];

        yield 'Lowercases short hex with a tied name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#0FF"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#0ff"/></svg>',
        ];

        yield 'Writes short hex as a shorter named color' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#F00"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Shortens 8-digit hex with repeated digits' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#AABBCCDD"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#abcd"/></svg>',
        ];

        yield 'Shortens 8-digit hex of a named color without naming it' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#FF0000FF"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#f00f"/></svg>',
        ];

        yield 'Lowercases 8-digit hex without repeated digits' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#AABBCCDE"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#aabbccde"/></svg>',
        ];

        yield 'Lowercases 4-digit hex' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#ABCD"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#abcd"/></svg>',
        ];

        yield 'Keeps hex of invalid length' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#FF00000"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#FF00000"/></svg>',
        ];

        yield 'Keeps hex with invalid digits' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#GG0000"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#GG0000"/></svg>',
        ];

        yield 'Keeps unknown names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="redd"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="redd"/></svg>',
        ];

        yield 'Keeps currentColor' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="currentColor"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="currentColor"/></svg>',
        ];

        yield 'Keeps transparent' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="transparent"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="transparent"/></svg>',
        ];

        yield 'Keeps none' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="none"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="none"/></svg>',
        ];

        yield 'Keeps inherit' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="inherit"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="inherit"/></svg>',
        ];

        yield 'Keeps url references' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(#red)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(#red)"/></svg>',
        ];

        yield 'Keeps url references with a fallback' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(#a) white"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(#a) white"/></svg>',
        ];

        yield 'Trims named colors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill=" red "/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="red"/></svg>',
        ];

        yield 'Shortens every color attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="#FFFFFF" color="white"/><stop stop-color="#ff0000"/><feFlood flood-color="#D2B48C"/><feDiffuseLighting lighting-color="yellow"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="#fff" color="#fff"/><stop stop-color="red"/><feFlood flood-color="tan"/><feDiffuseLighting lighting-color="#ff0"/></svg>',
        ];

        yield 'Shortens colors in style attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:#FFFFFF;stroke: white;stop-color:#ff0000;opacity:1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:#fff;stroke:#fff;stop-color:red;opacity:1"/></svg>',
        ];

        yield 'Keeps named colors in other attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect data-color="white" class="white" id="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect data-color="white" class="white" id="red"/></svg>',
        ];

        yield 'Keeps other properties in style attributes untouched' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="font-weight: bold;fill: #FFFFFF ;stroke:red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="font-weight: bold;fill:#fff;stroke:red"/></svg>',
        ];

        yield 'Keeps names that only match a named color after non-ASCII case folding' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="dar&#x212A;violet" style="stroke:dar&#x212A;violet"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect fill=\"dar\u{212A}violet\" style=\"stroke:dar\u{212A}violet\"/></svg>",
        ];

        yield 'Keeps color attributes of elements in other namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="9" height="9"><font xmlns="http://www.w3.org/1999/xhtml" color="rgb(255,0,0)">x</font></foreignObject><x:rect xmlns:x="urn:x" fill="#FFFFFF"/><rect fill="#FFFFFF"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="9" height="9"><font xmlns="http://www.w3.org/1999/xhtml" color="rgb(255,0,0)">x</font></foreignObject><x:rect xmlns:x="urn:x" fill="#FFFFFF"/><rect fill="#fff"/></svg>',
        ];

        yield 'Keeps custom properties and vendor properties in style attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="--my-fill: white;-webkit-text-fill-color: white;fill: white"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect style="--my-fill: white;-webkit-text-fill-color: white;fill:#fff"/></svg>',
        ];

        yield 'Converts colors in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect fill="#FFFFFF" style="stroke:rgb(255,0,0)"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:rect fill="#fff" style="stroke:red"/></svg:svg>',
        ];

        yield 'Keeps colors in style elements and animations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:#FFFFFF}</style><rect><animate attributeName="fill" fill="freeze" from="#FFFFFF" to="rgb(255,0,0)" values="white;black"/><set attributeName="fill" to="white"/></rect></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:#FFFFFF}</style><rect><animate attributeName="fill" fill="freeze" from="#FFFFFF" to="rgb(255,0,0)" values="white;black"/><set attributeName="fill" to="white"/></rect></svg>',
        ];

        yield 'Keeps rgb colors with percentages, spaces or uppercase function names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="rgb(100%,0%,0%)" stroke="rgb(255 0 0)" color="RGB(255,0,0)" stop-color="rgb(255,0,0,0.5)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="rgb(100%,0%,0%)" stroke="rgb(255 0 0)" color="RGB(255,0,0)" stop-color="rgb(255,0,0,0.5)"/></svg>',
        ];

        yield 'Keeps ICC colors' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#FFFFFF icc-color(p, 1)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#FFFFFF icc-color(p, 1)"/></svg>',
        ];

        yield 'Converts colors written with character references' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="&#x23;FFFFFF"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="#fff"/></svg>',
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
        $svgOptimizer->addPass(new ConvertColorsToHex());

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
