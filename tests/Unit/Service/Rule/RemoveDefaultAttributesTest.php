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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveDefaultAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveDefaultAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class RemoveDefaultAttributesTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveDefaultAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Removes Default Stroke Attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30" stroke="none"/>
                    <circle cx="50" cy="50" r="20" stroke="none"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></svg>
                XML,
        ];

        yield 'Keeps every animated default attribute' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="none" stroke-width="1" d="M0 0h1"><set attributeName="stroke" to="red"/><set attributeName=" stroke-width " to="2"/></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><path stroke="none" stroke-width="1" d="M0 0h1"><set attributeName="stroke" to="red"/><set attributeName=" stroke-width " to="2"/></path></svg>',
        ];

        yield 'Keeps Non-Default Stroke Attribute' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30" stroke="black"/>
                    <circle cx="50" cy="50" r="20" stroke="green"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" stroke="black"/><circle cx="50" cy="50" r="20" stroke="green"/></svg>
                XML,
        ];

        yield 'Removes Default Stroke from Nested Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g stroke="none">
                        <rect x="10" y="10" width="30" height="30"/>
                        <circle cx="50" cy="50" r="20"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Non-Standard Default Attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30"/>
                    <circle cx="50" cy="50" r="20"/>
                    <customElement customAttr="none"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/><customElement customAttr="none"/></svg>
                XML,
        ];

        yield 'Keeps Attributes with Different Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30" stroke="red"/>
                    <circle cx="50" cy="50" r="20" stroke="blue"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="30" height="30" stroke="red"/><circle cx="50" cy="50" r="20" stroke="blue"/></svg>
                XML,
        ];

        yield 'Removes Default Attributes from Nested Groups' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g stroke="none">
                        <rect x="10" y="10" width="30" height="30" stroke="none"/>
                        <circle cx="50" cy="50" r="20" stroke="none"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g><rect x="10" y="10" width="30" height="30"/><circle cx="50" cy="50" r="20"/></g></svg>
                XML,
        ];

        yield 'Empty SVG' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'SVG with no default attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="black"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="black"/></svg>',
        ];

        yield 'SVG with all default attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>',
        ];

        yield 'Keeps default stroke when an ancestor sets a different stroke' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g stroke="red"><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps default stroke-width when a distant ancestor sets a different width' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="3"><g><path stroke-width="1" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke-width="3"><g><path stroke-width="1" d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default when the nearest ancestor declaration is also default' => [
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="red"><g stroke="none"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" stroke="red"><g stroke="none"><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps default stroke when an ancestor style sets the property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke:red"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="stroke:red"><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default stroke when an ancestor style only sets other properties' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:red;stroke-width:2"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="fill:red;stroke-width:2"><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default stroke when an ancestor style only sets a custom property' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="--stroke:red"><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g style="--stroke:red"><path d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default stroke below a root element with an id' => [
            '<svg xmlns="http://www.w3.org/2000/svg" id="Layer_1"><path stroke="none" d="M0 0h1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" id="Layer_1"><path d="M0 0h1"/></svg>',
        ];

        yield 'Keeps default attributes when a style element is present' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{stroke:red}</style><g><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>g{stroke:red}</style><g><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Keeps default stroke on an element that may be reused by use' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path id="p" stroke="none" d="M0 0h1"/><use xlink:href="#p" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path id="p" stroke="none" d="M0 0h1"/><use xlink:href="#p" stroke="red"/></svg>',
        ];

        yield 'Keeps default stroke inside an ancestor that may be reused by use' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="g"><path stroke="none" d="M0 0h1"/></g><use xlink:href="#g" stroke="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="g"><path stroke="none" d="M0 0h1"/></g><use xlink:href="#g" stroke="red"/></svg>',
        ];

        yield 'Keeps default stroke when an ancestor stroke is animated' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="stroke" to="red"/><path stroke="none" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="stroke" to="red"/><path stroke="none" d="M0 0h1"/></g></svg>',
        ];

        yield 'Removes default stroke-linecap while keeping stroke animated elsewhere' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="stroke" to="red"/><path stroke-linecap="butt" d="M0 0h1"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><set attributeName="stroke" to="red"/><path d="M0 0h1"/></g></svg>',
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(RemoveDefaultAttributes::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveDefaultAttributes::shouldCheckSize());
    }
}
