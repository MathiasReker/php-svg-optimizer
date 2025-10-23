<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule;

use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedMasks;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnusedMasks::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class RemoveUnusedMasksTest extends TestCase
{
    /**
     * @throws SvgValidationException
     */
    #[DataProvider('provideOptimizeCases')]
    public function testOptimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveUnusedMasks());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes unused mask element' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><rect width="100" height="100"/></mask>
                    </defs>
                    <rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Keeps mask if referenced' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><circle r="50"/></mask></defs>
                    <rect mask="url(#m1)" width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask></defs><rect mask="url(#m1)" width="100" height="100"/></svg>
                XML,
        ];

        yield 'Removes empty defs after deleting unused mask' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="unused"></mask></defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps multiple masks when all are used' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><circle r="50"/></mask>
                        <mask id="m2"><rect width="50" height="50"/></mask>
                    </defs>
                    <rect mask="url(#m1)" width="100" height="100"/>
                    <circle mask="url(#m2)" r="40"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1"><circle r="50"/></mask><mask id="m2"><rect width="50" height="50"/></mask></defs><rect mask="url(#m1)" width="100" height="100"/><circle mask="url(#m2)" r="40"/></svg>
                XML,
        ];

        yield 'Removes only unused mask when others are referenced' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="used"><rect width="50" height="50"/></mask>
                        <mask id="unused"><circle r="10"/></mask>
                    </defs>
                    <rect mask="url(#used)" width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="used"><rect width="50" height="50"/></mask></defs><rect mask="url(#used)" width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles multiple defs sections and removes only unused masks' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><rect width="100" height="100"/></mask></defs>
                    <defs><mask id="m2"><circle r="20"/></mask></defs>
                    <rect mask="url(#m2)" width="50" height="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m2"><circle r="20"/></mask></defs><rect mask="url(#m2)" width="50" height="50"/></svg>
                XML,
        ];

        yield 'Keeps mask referenced inside a group' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="groupMask"><rect width="10" height="10"/></mask></defs>
                    <g mask="url(#groupMask)">
                        <rect width="100" height="100"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="groupMask"><rect width="10" height="10"/></mask></defs><g mask="url(#groupMask)"><rect width="100" height="100"/></g></svg>
                XML,
        ];

        yield 'Removes unused masks with whitespace or comments around them' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <!-- comment -->
                        <mask id="unused"><rect/></mask>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Ignores non-mask elements inside defs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <clipPath id="clip1"><rect/></clipPath>
                        <mask id="m1"><rect/></mask>
                    </defs>
                    <rect clip-path="url(#clip1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="clip1"><rect/></clipPath></defs><rect clip-path="url(#clip1)"/></svg>
                XML,
        ];

        yield 'Handles nested mask references (mask references another mask)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1" mask="url(#m2)"><rect/></mask>
                        <mask id="m2"><circle/></mask>
                    </defs>
                    <rect mask="url(#m1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="m1" mask="url(#m2)"><rect/></mask><mask id="m2"><circle/></mask></defs><rect mask="url(#m1)"/></svg>
                XML,
        ];

        yield 'Handles invalid mask references gracefully (no removal crash)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask id="m1"><rect/></mask></defs>
                    <rect mask="url(#nonexistent)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect mask="url(#nonexistent)"/></svg>
                XML,
        ];

        yield 'Handles masks without id attribute safely' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs><mask><rect/></mask></defs>
                    <rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles multiple mask attributes in different namespaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs><mask id="m1"><rect/></mask></defs>
                    <use xlink:href="#shape" mask="url(#m1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><mask id="m1"><rect/></mask></defs><use xlink:href="#shape" mask="url(#m1)"/></svg>
                XML,
        ];

        yield 'Removes multiple unused masks and cleans up defs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="a"><rect/></mask>
                        <mask id="b"><rect/></mask>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps mask with similar id substring (no partial matches)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="mask1"><rect/></mask>
                        <mask id="mask10"><circle/></mask>
                    </defs>
                    <rect mask="url(#mask1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><mask id="mask1"><rect/></mask></defs><rect mask="url(#mask1)"/></svg>
                XML,
        ];

        yield 'Removes unused mask but keeps other defs content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="unused"><rect/></mask>
                        <clipPath id="clip1"><circle/></clipPath>
                    </defs>
                    <rect clip-path="url(#clip1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="clip1"><circle/></clipPath></defs><rect clip-path="url(#clip1)"/></svg>
                XML,
        ];

        yield 'Handles defs containing mixed mask and gradient' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <mask id="m1"><rect/></mask>
                        <linearGradient id="grad1"><stop offset="0%"/></linearGradient>
                    </defs>
                    <rect fill="url(#grad1)"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="grad1"><stop offset="0%"/></linearGradient></defs><rect fill="url(#grad1)"/></svg>
                XML,
        ];
    }
}
