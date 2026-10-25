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
use MathiasReker\PhpSvgOptimizer\Service\Provider\AbstractProvider;
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\ConvertEmptyTagsToSelfClosing;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConvertEmptyTagsToSelfClosing::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
final class ConvertEmptyTagsToSelfClosingTest extends TestCase
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
        $svgOptimizer->addRule(new ConvertEmptyTagsToSelfClosing());

        $actual = $svgOptimizer->optimize()->getContent();
        self::assertSame($expected, $actual);

        $repeated = new SvgOptimizer(new StringProvider($actual));
        $repeated->addRule(new ConvertEmptyTagsToSelfClosing());
        self::assertSame($actual, $repeated->optimize()->getContent());
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'leaves an empty document alone' => [
            '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"/>',
        ];

        yield 'handles mixed-case elements and elements reset to no namespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><Rect></Rect><g xmlns=""></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><Rect/><g xmlns=""/></svg>',
        ];

        yield 'keeps elements with a non-breaking space' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&#xA0;</text></svg>',
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><text>\u{A0}</text></svg>",
        ];

        yield 'handles deep nesting' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g></g></g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g><g><g/></g></g></g></svg>',
        ];

        yield 'keeps the markup inside text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&lt;a&gt;&lt;/a&gt;<![CDATA[<b></b> <i />]]></text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&lt;a&gt;&lt;/a&gt;&lt;b&gt;&lt;/b&gt; &lt;i /&gt;</text></svg>',
        ];

        yield 'keeps the markup inside comments' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><!--<b></b>\n<i />--><g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><!--<b></b>\n<i />--><g/></svg>',
        ];

        yield 'keeps the markup inside processing instructions' => [
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><?foo <b></b>\n<i />?><g></g></svg>",
            "<svg xmlns=\"http://www.w3.org/2000/svg\"><?foo <b></b>\n<i />?><g/></svg>",
        ];

        yield 'handles prefixed svg elements' => [
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g></svg:g></svg:svg>',
            '<svg:svg xmlns:svg="http://www.w3.org/2000/svg"><svg:g/></svg:svg>',
        ];

        yield 'keeps an attribute value with a greater-than sign' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g aria-label="a&gt;b"></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g aria-label="a&gt;b"/></svg>',
        ];

        yield 'handles foreign content' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"></div></foreignObject></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml"></div></foreignObject></svg>',
        ];

        yield 'keeps multibyte text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>&#xE6;&#x65E5;</text><g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>æ日</text><g/></svg>',
        ];

        yield 'Keeps a whitespace-only tspan, whose space renders between the surrounding text' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<tspan> </tspan>b</text></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text>a<tspan> </tspan>b</text></svg>',
        ];

        yield 'Keeps preserved whitespace' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">  </text><g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><text xml:space="preserve">  </text><g/></svg>',
        ];

        yield 'Keeps a self-closing child named like its parent' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><a/><svg/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a/><svg/></svg>',
        ];

        yield 'Keeps a self-closing g before the closing tag of its parent g' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g/></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g/></g></svg>',
        ];

        yield 'Converts an empty element whose parent has the same name' => [
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g></g></g></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><g><g/></g></svg>',
        ];

        yield 'Convert Empty Rect Tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100"></rect>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>
                XML,
        ];

        yield 'Convert Empty Path Tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <path d="M10 10 H 90 V 90 H 10 Z"></path>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><path d="M10 10 H 90 V 90 H 10 Z"/></svg>
                XML,
        ];

        yield 'No Empty Tags' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100" fill="blue"/>
                    <circle cx="50" cy="50" r="40"/>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="blue"/><circle cx="50" cy="50" r="40"/></svg>
                XML,
        ];

        yield 'Non-Empty Tag' => [
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
                    <rect width="100" height="100">Content</rect>
                </svg>
                XML,
            <<<'XML'
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100">Content</rect></svg>
                XML,
        ];
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(ConvertEmptyTagsToSelfClosing::shouldCheckSize());
    }
}
