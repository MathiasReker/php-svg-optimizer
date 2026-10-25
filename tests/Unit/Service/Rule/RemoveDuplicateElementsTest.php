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
final class RemoveDuplicateElementsTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveDuplicateElements());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps duplicate stops that contain animations' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient><stop offset="0"><animate href="#r" attributeName="x" by="1" additive="sum"/></stop><stop offset="0"><animate href="#r" attributeName="x" by="1" additive="sum"/></stop></linearGradient><rect id="r"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient><stop offset="0"><animate href="#r" attributeName="x" by="1" additive="sum"/></stop><stop offset="0"><animate href="#r" attributeName="x" by="1" additive="sum"/></stop></linearGradient><rect id="r"/></svg>',
        ];

        yield 'Keeps duplicate style sheets in defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>a{}</style><style>a{}</style></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><style>a{}</style><style>a{}</style></defs></svg>',
        ];

        yield 'Keeps duplicates when a structural pseudo-class may match' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:nth-child(4){fill:red}</style><title>a</title><title>a</title><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect:nth-child(4){fill:red}</style><title>a</title><title>a</title><rect width="5" height="5"/></svg>',
        ];

        yield 'Removes duplicates when the style sheet does not depend on siblings' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><title>a</title><title>a</title><rect width="5" height="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>rect{fill:red}</style><title>a</title><rect width="5" height="5"/></svg>',
        ];

        yield 'Keeps exact duplicate rendered elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="10" width="80" height="80" fill="red"/>
                    <rect x="10" y="10" width="80" height="80" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><rect x="10" y="10" width="80" height="80" fill="red"/><rect x="10" y="10" width="80" height="80" fill="red"/></svg>
                XML,
        ];

        yield 'Removes exact duplicate elements inside defs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <rect x="10" y="10" width="80" height="80" fill="red"/>
                        <rect x="10" y="10" width="80" height="80" fill="red"/>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><rect x="10" y="10" width="80" height="80" fill="red"/></defs></svg>
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

        yield 'Keeps duplicate rendered text elements with same parent' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <text x="10" y="10">Hello</text>
                    <text x="10" y="10">Hello</text>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><text x="10" y="10">Hello</text><text x="10" y="10">Hello</text></svg>
                XML,
        ];

        yield 'Removes duplicate text elements inside defs' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <text x="10" y="10">Hello</text>
                        <text x="10" y="10">Hello</text>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><text x="10" y="10">Hello</text></defs></svg>
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
                <svg xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="10" height="10" fill="black"/><circle cx="5" cy="5" r="5" fill="black"/><rect x="0" y="0" width="10" height="10" fill="black"/></svg>
                XML,
        ];

        yield 'Removes elements with same attributes with equal parents' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <g>
                            <rect width="10" height="10" fill="red"/>
                        </g>
                        <g>
                            <rect width="10" height="10" fill="red"/>
                        </g>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><g><rect width="10" height="10" fill="red"/></g></defs></svg>
                XML,
        ];

        yield 'Keeps duplicate rendered groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="10" height="10" fill="red"/></g><g><rect width="10" height="10" fill="red"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><rect width="10" height="10" fill="red"/></g><g><rect width="10" height="10" fill="red"/></g></svg>',
        ];

        yield 'Keeps duplicate children of a group inside defs that may be referenced' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="x"><path d="M0 0h1"/><path d="M0 0h1"/></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g id="x"><path d="M0 0h1"/><path d="M0 0h1"/></g></defs></svg>',
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
                    <defs>
                        <g id="a"/>
                        <g id="a"/>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><g id="a"/></defs></svg>
                XML,
        ];

        yield 'Ignores attribute order differences' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <rect width="50" height="50" fill="red" stroke="none"/>
                        <rect stroke="none" height="50" fill="red" width="50"/>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><rect width="50" height="50" fill="red" stroke="none"/></defs></svg>
                XML,
        ];

        yield 'Ignores whitespace differences in attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <circle cx="5" cy="5" r="5" fill=" blue "/>
                        <circle cx="5" cy="5" r="5" fill="blue"/>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><circle cx="5" cy="5" r="5" fill=" blue "/></defs></svg>
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
                    <defs>
                        <path d="M0 0 L10 10"/>
                        <path d="M0 0 L10 10"/>
                        <path d="M10 10 L20 20"/>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><path d="M0 0 L10 10"/><path d="M10 10 L20 20"/></defs></svg>
                XML,
        ];

        yield 'Ignores comments between duplicates' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <rect width="10" height="10" fill="red"/>
                        <!-- comment -->
                        <rect width="10" height="10" fill="red"/>
                    </defs>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg"><defs><rect width="10" height="10" fill="red"/><!-- comment --></defs></svg>
                XML,
        ];

        yield 'Removes three adjacent duplicates inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect width="1"/><rect width="1"/><rect width="1"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><rect width="1"/></defs></svg>',
        ];

        yield 'Keeps defs children that differ in nested content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><rect width="1"/></g><g><rect width="2"/></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g><rect width="1"/></g><g><rect width="2"/></g></defs></svg>',
        ];

        yield 'Keeps a later duplicate that is painted over a different element' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" fill="red"/><rect width="10" height="10" fill="blue"/><rect width="10" height="10" fill="red"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" fill="red"/><rect width="10" height="10" fill="blue"/><rect width="10" height="10" fill="red"/></svg>',
        ];

        yield 'Keeps adjacent duplicate translucent shapes' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" opacity=".5"/><rect width="10" height="10" opacity=".5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10" opacity=".5"/><rect width="10" height="10" opacity=".5"/></svg>',
        ];

        yield 'Keeps duplicate tspans whose text is rendered twice' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>a</tspan><tspan>a</tspan></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text><tspan>a</tspan><tspan>a</tspan></text></svg>',
        ];

        yield 'Keeps duplicate scripts inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><script>a()</script><script>a()</script></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><script>a()</script><script>a()</script></defs></svg>',
        ];

        yield 'Does not trim attribute values of elements that are kept' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text aria-label=" a ">a</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text aria-label=" a ">a</text></svg>',
        ];

        yield 'Removes adjacent duplicate elements inside defs' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1"/> <!-- c --> <path id="p" d="M0 0h1"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><path id="p" d="M0 0h1"/><!-- c --></defs></svg>',
        ];

        yield 'Removes adjacent duplicate gradient stops' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"><stop offset="0" stop-color="red"/><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"><stop offset="0" stop-color="red"/><stop offset="1" stop-color="blue"/></linearGradient></svg>',
        ];

        yield 'Keeps adjacent elements with different text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a</title><title>b</title></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a</title><title>b</title></svg>',
        ];

        yield 'Removes adjacent elements whose text differs only in surrounding whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a</title><title> a </title></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a</title></svg>',
        ];

        yield 'Keeps adjacent elements with different tags' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><desc>a</desc><metadata>a</metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><desc>a</desc><metadata>a</metadata></svg>',
        ];

        yield 'Keeps non-adjacent duplicate gradient stops' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"><stop offset=".5" stop-color="red"/><stop offset="1" stop-color="blue"/><stop offset=".5" stop-color="red"/></linearGradient></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><linearGradient id="g"><stop offset=".5" stop-color="red"/><stop offset="1" stop-color="blue"/><stop offset=".5" stop-color="red"/></linearGradient></svg>',
        ];

        yield 'Removes adjacent duplicate titles and descriptions' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a</title><title>a</title><desc>b</desc><desc>b</desc></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a</title><desc>b</desc></svg>',
        ];

        yield 'Keeps duplicate additive animations in defs, whose effects add up' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" x="0"/><defs><animate href="#r" attributeName="x" by="10" dur="1s" additive="sum"/><animate href="#r" attributeName="x" by="10" dur="1s" additive="sum"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r" x="0"/><defs><animate href="#r" attributeName="x" by="10" dur="1s" additive="sum"/><animate href="#r" attributeName="x" by="10" dur="1s" additive="sum"/></defs></svg>',
        ];

        yield 'Keeps duplicate groups in defs that contain an animation' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><defs><g><animateMotion href="#r" path="M0 0H9" additive="sum"/></g><g><animateMotion href="#r" path="M0 0H9" additive="sum"/></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><defs><g><animateMotion href="#r" path="M0 0H9" additive="sum"/></g><g><animateMotion href="#r" path="M0 0H9" additive="sum"/></g></defs></svg>',
        ];

        yield 'Keeps duplicate groups in defs with a deeply nested animation after other descendants' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><defs><g><g><desc>a</desc></g><g><g><set href="#r" attributeName="x" to="1"/></g></g></g><g><g><desc>a</desc></g><g><g><set href="#r" attributeName="x" to="1"/></g></g></g></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="r"/><defs><g><g><desc>a</desc></g><g><g><set href="#r" attributeName="x" to="1"/></g></g></g><g><g><desc>a</desc></g><g><g><set href="#r" attributeName="x" to="1"/></g></g></g></defs></svg>',
        ];

        yield 'Keeps adjacent elements in different namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><metadata><a:x xmlns:a="urn:1"/></metadata><metadata><a:x xmlns:a="urn:2"/></metadata></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><metadata><a:x xmlns:a="urn:1"/></metadata><metadata><a:x xmlns:a="urn:2"/></metadata></svg>',
        ];

        yield 'Removes adjacent duplicates in a prefixed SVG document' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:title>a</svg:title><svg:title>a</svg:title></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:title>a</svg:title></svg:svg>',
        ];

        yield 'Leaves an empty document unchanged' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'Keeps adjacent elements whose names differ in case' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g/><G/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><defs><g/><G/></defs></svg>',
        ];

        yield 'Keeps adjacent duplicates in defs when a style sheet uses :nth-child()' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path:nth-child(2){fill:red}</style><defs><path d="M0 0"/><path d="M0 0"/></defs></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><style>path:nth-child(2){fill:red}</style><defs><path d="M0 0"/><path d="M0 0"/></defs></svg>',
        ];

        yield 'Removes duplicates with unicode text after keeping a different earlier pair' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a</title><title>b</title><title>ü</title><title>ü</title></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><title>a</title><title>b</title><title>ü</title></svg>',
        ];

        yield 'Removes adjacent duplicates whose text is in a CDATA section' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><desc><![CDATA[a<b]]></desc><desc><![CDATA[a<b]]></desc></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><desc>a&lt;b</desc></svg>',
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(RemoveDuplicateElements::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveDuplicateElements::shouldCheckSize());
    }
}
