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
use MathiasReker\PhpSvgOptimizer\Services\Providers\StringProvider;
use MathiasReker\PhpSvgOptimizer\Services\Rules\RemoveComments;
use MathiasReker\PhpSvgOptimizer\Services\Util\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Services\Validators\SvgValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RemoveComments::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveCommentsTest extends TestCase
{
    public static function svgCommentsProvider(): \Iterator
    {
        yield 'Removes Single Comment' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- This is a comment -->
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML
        ];

        yield 'Removes Multiple Comments' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- First comment -->
                    <rect x="10" y="10" width="30" height="30"/>
                    <!-- Second comment -->
                    <circle cx="50" cy="50" r="20"/>
                    <!-- Third comment -->
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML
        ];

        yield 'Handles No Comments' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30"/>
                    <circle cx="50" cy="50" r="20"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML
        ];

        yield 'Handles Empty Document' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"/>
                XML
        ];

        yield 'Removes Comments in <defs> Element' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <defs>
                        <!-- Gradient Definition -->
                        <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1" />
                            <stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <rect x="10" y="10" width="30" height="30" fill="url(#grad1)"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><defs><linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1"/><stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1"/></linearGradient></defs><rect x="10" y="10" width="30" height="30" fill="url(#grad1)"/></svg>
                XML
        ];

        yield 'Removes Comments in Nested Elements' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g>
                        <!-- Group Comment -->
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></g></svg>
                XML
        ];

        yield 'Removes Comments with Special Characters' => [
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- Special characters: !@#$%^&*() -->
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML
        ];

        yield 'Handles Comments with XML Special Characters' => [
            <<<XML_WRAP
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <!-- Comment with XML special characters: & < > -->
                    <rect x="10" y="10" width="30" height="30"/>
                </svg>
                XML_WRAP
            ,
            <<<XML
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/></svg>
                XML
        ];
    }

    #[DataProvider('svgCommentsProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new RemoveComments());

        $actual = $svgOptimizer->optimize()->getContent();
        Assert::assertSame($expected, $actual);
    }
}
