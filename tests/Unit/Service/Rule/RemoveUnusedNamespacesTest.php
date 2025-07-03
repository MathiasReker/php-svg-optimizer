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
use MathiasReker\PhpSvgOptimizer\Processing\DomDocumentWrapper;
use MathiasReker\PhpSvgOptimizer\Processing\XmlProcessor;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveUnusedNamespaces;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveUnusedNamespaces::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(XmlProcessor::class)]
final class RemoveUnusedNamespacesTest extends TestCase
{
    public static function svgNamespaceProvider(): \Iterator
    {
        yield 'Removes unused namespaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" width="100" height="100">
                    <rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Keeps used namespaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" xmlns:ns2="http://example.com/ns2" width="100" height="100">
                    <ns2:rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns2="http://example.com/ns2" width="100" height="100"><ns2:rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'No unused namespaces to remove' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" width="100" height="100">
                    <ns1:rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" width="100" height="100"><ns1:rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Removes unused namespace in complex structure' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" xmlns:ns2="http://example.com/ns2" width="100" height="100">
                    <g>
                        <ns1:rect width="50" height="50"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" width="100" height="100"><g><ns1:rect width="50" height="50"/></g></svg>
                XML,
        ];

        yield 'Retains multiple namespaces used in different elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" xmlns:ns2="http://example.com/ns2" width="100" height="100">
                    <ns1:rect width="50" height="50"/>
                    <ns2:circle cx="50" cy="50" r="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" xmlns:ns2="http://example.com/ns2" width="100" height="100"><ns1:rect width="50" height="50"/><ns2:circle cx="50" cy="50" r="30"/></svg>
                XML,
        ];

        yield 'Handles no namespaces at all' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Handles empty SVG tag with no namespaces' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"></svg>
                XML,
        ];

        yield 'Handles SVG with only one namespace and no elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:ns1="http://example.com/ns1" width="100" height="100">
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"></svg>
                XML,
        ];
    }

    /**
     * @throws SvgValidationException
     */
    #[DataProvider('svgNamespaceProvider')]
    public function testOptimize(string $svgContent, string $expected): void
    {
        $svgOptimizer = new SvgOptimizer(new StringProvider($svgContent));
        $svgOptimizer->addRule(new RemoveUnusedNamespaces());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }
}
