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
use MathiasReker\PhpSvgOptimizer\Exception\SvgValidationException;
use MathiasReker\PhpSvgOptimizer\Exception\XmlProcessingException;
use MathiasReker\PhpSvgOptimizer\Model\SvgOptimizer;
use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use MathiasReker\PhpSvgOptimizer\Service\Processor\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDuplicateElements;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveDuplicateElements::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlFormatter::class)]
final class RemoveDuplicateElementsTest extends TestCase
{
    /**
     * @throws SvgValidationException
     * @throws XmlProcessingException
     * @throws RiskyRulesNotAllowedException
     */
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(string $content, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($content));
        $svgOptimizer->addRule(new RemoveDuplicateElements());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes exact duplicate elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="10" width="80" height="80" fill="red"/>
                    <rect x="10" y="10" width="80" height="80" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y="10" width="80" height="80" fill="red"/></svg>
                XML,
        ];

        yield 'Keeps unique elements with different attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="10" width="80" height="80" fill="red"/>
                    <rect x="10" y="10" width="80" height="80" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y="10" width="80" height="80" fill="red"/><rect x="10" y="10" width="80" height="80" fill="blue"/></svg>
                XML,
        ];

        yield 'Keeps groups with identical attributes but different text content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g transform="scale(.1)">
                        <text x="455" y="140">code coverage</text>
                    </g>
                    <g transform="scale(.1)">
                        <text x="1055" y="140">93%</text>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(.1)"><text x="455" y="140">code coverage</text></g><g transform="scale(.1)"><text x="1055" y="140">93%</text></g></svg>
                XML,
        ];

        yield 'Removes duplicate text elements with same parent' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text x="10" y="10">Hello</text>
                    <text x="10" y="10">Hello</text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text x="10" y="10">Hello</text></svg>
                XML,
        ];

        yield 'Keeps text elements with different content' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text x="10" y="10">Hello</text>
                    <text x="10" y="10">World</text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text x="10" y="10">Hello</text><text x="10" y="10">World</text></svg>
                XML,
        ];

        yield 'Keeps badge label and value groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g transform="scale(.1)">
                        <text x="455" y="140">code coverage</text>
                    </g>
                    <g transform="scale(.1)">
                        <text x="1055" y="140">93%</text>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g transform="scale(.1)"><text x="455" y="140">code coverage</text></g><g transform="scale(.1)"><text x="1055" y="140">93%</text></g></svg>
                XML,
        ];

        yield 'Ignores different tag names' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect x="0" y="0" width="10" height="10" fill="black"/>
                    <circle cx="5" cy="5" r="5" fill="black"/>
                    <rect x="0" y="0" width="10" height="10" fill="black"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="10" height="10" fill="black"/><circle cx="5" cy="5" r="5" fill="black"/></svg>
                XML,
        ];

        yield 'Removes elements with same attributes with equal parents' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <rect width="10" height="10" fill="red"/>
                    </g>
                    <g>
                        <rect width="10" height="10" fill="red"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><rect width="10" height="10" fill="red"/></g></svg>
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

        yield 'Handles nested identical groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <g id="a"/>
                        <g id="a"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><g id="a"/></g></svg>
                XML,
        ];

        yield 'Ignores attribute order differences' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="50" height="50" fill="red" stroke="none"/>
                    <rect stroke="none" height="50" fill="red" width="50"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="50" height="50" fill="red" stroke="none"/></svg>
                XML,
        ];

        yield 'Ignores whitespace differences in attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle cx="5" cy="5" r="5" fill=" blue "/>
                    <circle cx="5" cy="5" r="5" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="5" fill="blue"/></svg>
                XML,
        ];

        yield 'Ignores tags with different attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <circle cx="5" cy="5" r="6" fill="blue"/>
                    <circle cx="5" cy="5" r="5" fill="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="6" fill="blue"/><circle cx="5" cy="5" r="5" fill="blue"/></svg>
                XML,
        ];

        yield 'Keeps partially different attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="20" height="20" fill="red"/>
                    <rect width="20" height="20" fill="red" stroke="black"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="20" height="20" fill="red"/><rect width="20" height="20" fill="red" stroke="black"/></svg>
                XML,
        ];

        yield 'Removes duplicate nested paths within same group' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <path d="M0 0 L10 10"/>
                        <path d="M0 0 L10 10"/>
                        <path d="M10 10 L20 20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><g><path d="M0 0 L10 10"/><path d="M10 10 L20 20"/></g></svg>
                XML,
        ];

        yield 'Ignores comments between duplicates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect width="10" height="10" fill="red"/>
                    <!-- comment -->
                    <rect width="10" height="10" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" fill="red"/><!-- comment --></svg>
                XML,
        ];
    }
}
