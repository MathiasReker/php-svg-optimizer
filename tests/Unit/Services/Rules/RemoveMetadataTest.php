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
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveMetadata;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveMetadata::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveMetadataTest extends TestCase
{
    public static function svgMetadataProvider(): \Iterator
    {
        yield 'Removes single <metadata> tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>This is some metadata</metadata>
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Removes multiple <metadata> tags' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>This is some metadata</metadata>
                    <metadata>This is more metadata</metadata>
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/></svg>
                XML,
        ];

        yield 'Removes nested elements inside <metadata>' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata><data>Some nested data</data></metadata>
                    <rect width="100" height="100" fill="green"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="green"/></svg>
                XML,
        ];

        yield 'No <metadata> tag present' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="yellow"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="yellow"/></svg>
                XML,
        ];

        yield 'No <metadata> tag, only <title> and <desc>' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <title>SVG Title</title>
                    <desc>SVG Description</desc>
                    <rect width="100" height="100" fill="orange"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><title>SVG Title</title><desc>SVG Description</desc><rect width="100" height="100" fill="orange"/></svg>
                XML,
        ];

        yield 'Removes <metadata> from different layers' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <defs>
                        <metadata>Metadata in defs</metadata>
                    </defs>
                    <g>
                        <metadata>Metadata in g</metadata>
                        <rect width="100" height="100" fill="pink"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><defs/><g><rect width="100" height="100" fill="pink"/></g></svg>
                XML,
        ];

        yield 'Keeps non-metadata content intact' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>This is metadata</metadata>
                    <title>SVG Title</title>
                    <desc>SVG Description</desc>
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><title>SVG Title</title><desc>SVG Description</desc><rect width="100" height="100" fill="blue"/></svg>
                XML,
        ];

        yield 'Handles complex SVG structure' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>Metadata 1</metadata>
                    <defs>
                        <metadata>Metadata 2</metadata>
                    </defs>
                    <g>
                        <metadata>Metadata 3</metadata>
                        <rect width="100" height="100" fill="purple"/>
                    </g>
                    <metadata>Metadata 4</metadata>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><defs/><g><rect width="100" height="100" fill="purple"/></g></svg>
                XML,
        ];

        yield 'Well-formed XML after removal' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <metadata>Some metadata</metadata>
                    <rect width="100" height="100" fill="black"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="black"/></svg>
                XML,
        ];
    }

    #[DataProvider('svgMetadataProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new RemoveMetadata());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
