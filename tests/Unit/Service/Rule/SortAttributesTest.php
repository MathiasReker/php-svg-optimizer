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
use MathiasReker\PhpSvgOptimizer\Service\Rule\SortAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SortAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(SvgValidator::class)]
#[CoversClass(DomDocumentWrapper::class)]
final class SortAttributesTest extends TestCase
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
        $svgOptimizer->addRule(new SortAttributes());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);

        $repeated = new SvgOptimizer(new StringProvider($actual));
        $repeated->addRule(new SortAttributes());
        self::assertSame($actual, $repeated->optimize()->getContent());
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'sorts mixed-case attribute names' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect b="1" A="2" a="3" B="4"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect A="2" B="4" a="3" b="1"/></svg>',
        ];

        yield 'keeps values padded with tabs, newlines and non-breaking spaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect z="&#9;a&#10;" y="&#xA0;"/></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><rect y=\"\u{A0}\" z=\"&#9;a&#10;\"/></svg>",
        ];

        yield 'sorts attributes of elements reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect z="1" a="2"/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g xmlns=""><rect a="2" z="1"/></g></svg>',
        ];

        yield 'leaves attributes in order alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" height="1" width="1" a="1" b="2"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect id="a" height="1" width="1" a="1" b="2"/></svg>',
        ];

        yield 'sorts attributes deep inside groups' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><rect z="1" a="2"/></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g><rect a="2" z="1"/></g></g></g></g></svg>',
        ];

        yield 'sorts attributes with a custom xlink prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"><use y="1" x:href="#a" id="u" xml:space="preserve"/><rect id="a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="http://www.w3.org/1999/xlink"><use id="u" x:href="#a" xml:space="preserve" y="1"/><rect id="a"/></svg>',
        ];

        yield 'sorts the attributes of prefixed svg elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><svg:use xlink:href="#a" width="1" b="2"/></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><svg:use width="1" b="2" xlink:href="#a"/></svg:svg>',
        ];

        yield 'sorts attributes with the same local name in different namespaces' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a" xmlns:b="urn:b"><rect b:z="1" a:z="2" z="3"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:a="urn:a" xmlns:b="urn:b"><rect a:z="2" b:z="1" z="3"/></svg>',
        ];

        yield 'keeps values with entities and multibyte characters' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><rect y="&amp;&lt;" aria-label="&#xE6;&#x65E5;" b="&quot;"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><rect aria-label="æ日" b="&quot;" y="&amp;&lt;"/></svg>',
        ];

        yield 'sorts the attributes of foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><input xmlns="http://www.w3.org/1999/xhtml" value="a" type="text"/></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><input xmlns="http://www.w3.org/1999/xhtml" type="text" value="a"/></foreignObject></svg>',
        ];

        yield 'sorts uppercase attribute names before lowercase ones' => [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1" fill="red"><path d="M0 0" Z="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" fill="red" viewBox="0 0 1 1"><path Z="1" d="M0 0"/></svg>',
        ];

        yield 'Sorts Attributes in Correct Order' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" fill="none" stroke="black" id="rect1">
                    <rect id="rect2" stroke="red" x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" id="rect1" fill="none" stroke="black"><rect id="rect2" width="30" height="30" stroke="red" x="10" y="10"/></svg>
                XML,
        ];

        yield 'Sorts Attributes with Custom Order' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="100" height="100" stroke="black" id="rect1">
                    <rect id="rect2" x="10" y="10" width="30" height="30" stroke="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" id="rect1" fill="none" stroke="black"><rect id="rect2" width="30" height="30" stroke="red" x="10" y="10"/></svg>
                XML,
        ];

        yield 'Does Not Change Order of Already Sorted Attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" id="rect1" width="100" height="100" fill="none" stroke="black">
                    <rect id="rect2" x="10" y="10" width="30" height="30" stroke="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" id="rect1" width="100" height="100" fill="none" stroke="black"><rect id="rect2" width="30" height="30" stroke="red" x="10" y="10"/></svg>
                XML,
        ];

        yield 'Sorts Multiple Attributes Alphabetically' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" cx="50" cy="50" r="20" fill="red" stroke="none"><circle /></svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" cx="50" cy="50" fill="red" r="20" stroke="none"><circle/></svg>
                XML,
        ];

        yield 'Sorts Attributes Inside Groups Correctly' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <g fill="none" stroke="black" id="group1">
                        <rect x="10" y="10" width="30" height="30" id="rect1"/>
                        <circle cx="50" cy="50" r="20" stroke="red"/>
                    </g>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><g id="group1" fill="none" stroke="black"><rect width="30" height="30" id="rect1" x="10" y="10"/><circle cx="50" cy="50" r="20" stroke="red"/></g></svg>
                XML,
        ];

        yield 'Handles Attributes with Different Prefixes Correctly' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100" height="100">
                    <rect x="10" y="10" width="30" height="30"/>
                    <circle cx="50" cy="50" r="20" fill="red"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100" height="100"><rect width="30" height="30" x="10" y="10"/><circle cx="50" cy="50" fill="red" r="20"/></svg>
                XML,
        ];

        yield 'Handles Empty Elements' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect/>
                    <circle/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect/><circle/></svg>
                XML,
        ];

        yield 'Handles Attributes Without Values' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect disabled="" x="10" y="10" width="30" height="30"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="30" height="30" disabled="" x="10" y="10"/></svg>
                XML,
        ];

        yield 'Handles Namespaced Attributes' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100" height="100">
                    <a xlink:href="#foo" href="#bar">
                        <rect x="10" y="10" width="30" height="30"/>
                    </a>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="100" height="100"><a href="#bar" xlink:href="#foo"><rect width="30" height="30" x="10" y="10"/></a></svg>
                XML,
        ];
    }

    #[Test]
    public function isRiskyReturnsFalse(): void
    {
        self::assertFalse(SortAttributes::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(SortAttributes::shouldCheckSize());
    }
}
