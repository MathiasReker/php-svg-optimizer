<?php

/**
 * This file is part of the php-svg-optimizer package.
 * (c) Mathias Reker <github@reker.dk>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Rules;

use MathiasReker\PhpSvgOptimizer\Models\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Services\Providers\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Services\Providers\StringProvider;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveDeprecatedAttributes;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RemoveDeprecatedAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
final class RemoveDeprecatedAttributesTest extends TestCase
{
    public static function svgXlinkProvider(): \Iterator
    {
        yield 'Removes baseProfile attribute' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" baseProfile="tiny">
                    <rect width="100" height="100" fill="blue"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="blue"/></svg>
                XML
        ];

        yield 'Removes zoomAndPan attribute' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" zoomAndPan="disable">
                    <rect width="100" height="100" fill="red"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="red"/></svg>
                XML
        ];

        yield 'Removes requiredFeatures attribute' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" requiredFeatures="http://www.w3.org/TR/SVG11/feature#BasicStructure">
                    <rect width="100" height="100" fill="green"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="green"/></svg>
                XML
        ];

        yield 'Removes version attribute' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1">
                    <rect width="100" height="100" fill="yellow"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="yellow"/></svg>
                XML
        ];

        yield 'Replaces xlink:href with href' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="#icon" />
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML
        ];

        yield 'Replaces xlink:title with title' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <image xlink:title="Image Title" href="image.png"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><image href="image.png" title="Image Title"/></svg>
                XML
        ];

        yield 'Removes xmlns:xlink namespace' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <rect width="100" height="100" fill="pink"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="pink"/></svg>
                XML
        ];

        yield 'Handles combination of attributes and xlink' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" baseProfile="full" version="1.1">
                    <use xlink:href="#icon" zoomAndPan="disable" />
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML
        ];

        yield 'Does not remove non-deprecated attributes' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect fill="purple"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="purple"/></svg>
                XML
        ];

        yield 'Handles attributes with namespaces correctly' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="image.svg"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><use href="image.svg"/></svg>
                XML
        ];

        yield 'Handles no xlink attributes present' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <circle cx="100" cy="100" r="50" fill="orange"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><circle cx="100" cy="100" r="50" fill="orange"/></svg>
                XML
        ];

        yield 'Removes xlink attributes in mixed content' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="300" height="300">
                    <circle cx="100" cy="100" r="50" fill="green"/>
                    <use xlink:href="#someIcon" />
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><circle cx="100" cy="100" r="50" fill="green"/><use href="#someIcon"/></svg>
                XML
        ];

        yield 'Does not replace if new attribute value is the same' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <use xlink:href="#icon" href="#icon"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg"><use href="#icon"/></svg>
                XML
        ];
    }

    #[DataProvider('svgXlinkProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new RemoveDeprecatedAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
