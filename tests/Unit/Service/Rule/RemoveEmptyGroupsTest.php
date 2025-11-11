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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveEmptyGroups;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveEmptyGroups::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class RemoveEmptyGroupsTest extends TestCase
{
    /**
     * @throws SvgValidationException
     */
    #[DataProvider('provideOptimizeCases')]
    public function testOptimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveEmptyGroups());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes completely empty group' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g></g>
                    <rect width="10" height="10" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" fill="red"/></svg>
                XML,
        ];

        yield 'Keeps group with attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g id="keep"/>
                    <rect width="10" height="10" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g id="keep"/><rect width="10" height="10" fill="blue"/></svg>
                XML,
        ];

        yield 'Keeps group with child elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <circle cx="5" cy="5" r="5" fill="green"/>
                    </g>
                    <g></g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><circle cx="5" cy="5" r="5" fill="green"/></g></svg>
                XML,
        ];

        yield 'Removes multiple consecutive empty groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g></g>
                    <g></g>
                    <g><rect width="10" height="10"/></g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><rect width="10" height="10"/></g></svg>
                XML,
        ];

        yield 'Removes nested empty groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <g></g>
                        <g id="inner"><g></g></g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><g id="inner"/></g></svg>
                XML,
        ];

        yield 'Handles empty SVG gracefully' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Ignores non-group elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="10" height="10"/>
                    <circle cx="5" cy="5" r="5"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/><circle cx="5" cy="5" r="5"/></svg>
                XML,
        ];

        yield 'Keeps group with comments only' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g><!-- comment --></g>
                    <g></g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><!-- comment --></g></svg>
                XML,
        ];

        yield 'Keeps group with whitespace-only text' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>   </g>
                    <g></g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Removes group with only nested empty groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <g></g>
                        <g></g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"/>
                XML,
        ];

        yield 'Keeps mixed content group (text + element)' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>text<rect width="5" height="5"/></g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g>text<rect width="5" height="5"/></g></svg>
                XML,
        ];

        yield 'Removes empty groups deeply nested with other groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <g>
                            <g></g>
                        </g>
                        <g><circle cx="2" cy="2" r="2"/></g>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><g><circle cx="2" cy="2" r="2"/></g></g></svg>
                XML,
        ];

        yield 'Keeps group with mixed comments and whitespace' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g> <!-- comment --> </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><!-- comment --></g></svg>
                XML,
        ];
    }
}
