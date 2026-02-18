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
use MathiasReker\PhpSvgOptimizer\Service\Rule\RemoveNonStandardAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Validator\SvgValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RemoveNonStandardAttributes::class)]
#[CoversClass(SvgOptimizer::class)]
#[CoversClass(StringProvider::class)]
#[CoversClass(XmlFormatter::class)]
#[CoversClass(DomDocumentWrapper::class)]
#[CoversClass(SvgValidator::class)]
final class RemoveNonStandardAttributesTest extends TestCase
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
        $svgOptimizer->addRule(new RemoveNonStandardAttributes());

        $actual = $svgOptimizer
            ->allowRisky()
            ->optimize()
            ->getContent();

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Keeps standard width and height' => [
            '<svg width="100" height="50"><rect width="10" height="10"/></svg>',
            '<svg width="100" height="50"><rect width="10" height="10"/></svg>',
        ];

        yield 'Removes single unknown attribute' => [
            '<svg foo="bar"></svg>',
            '<svg/>',
        ];

        yield 'Removes multiple unknown attributes' => [
            '<svg foo="1" bar="2" baz="3"></svg>',
            '<svg/>',
        ];

        yield 'Keeps allowed and removes unknown on same element' => [
            '<svg width="100" foo="bar" height="50"></svg>',
            '<svg width="100" height="50"/>',
        ];

        yield 'Removes unknown attribute on child element' => [
            '<svg><rect width="10" custom="x"/></svg>',
            '<svg><rect width="10"/></svg>',
        ];

        yield 'Removes unknown attributes on deeply nested elements' => [
            '<svg><g><g><rect width="10" evil="true"/></g></g></svg>',
            '<svg><g><g><rect width="10"/></g></g></svg>',
        ];

        yield 'Keeps xml, xlink, data-* and allowed on* attributes' => [
            '<svg xml:lang="en" xlink:href="url" data-test="1" onload="alert(1)" onfake="oops"></svg>',
            '<svg xml:lang="en" xlink:href="url" data-test="1" onload="alert(1)"/>',
        ];

        yield 'Keeps data-* but removes similar-looking attribute' => [
            '<svg data-test="1" dataTest="2"></svg>',
            '<svg data-test="1"/>',
        ];

        yield 'Keeps on* event attributes if allowed in enum' => [
            '<svg onclick="doSomething()" onmouseover="hover()" onfake="fail()"></svg>',
            '<svg onclick="doSomething()" onmouseover="hover()"/>',
        ];

        yield 'Removes attributes with javascript-like values if name is unknown' => [
            '<svg foo="javascript:alert(1)" width="10"></svg>',
            '<svg width="10"/>',
        ];

        yield 'Removes namespaced attribute not explicitly allowed' => [
            '<svg ev:event="something" custom:attr="x"></svg>',
            '<svg ev:event="something"/>',
        ];

        yield 'Keeps attributes on defs and symbols' => [
            '<svg><defs><symbol id="icon" viewBox="0 0 10 10"/></defs></svg>',
            '<svg><defs><symbol id="icon" viewBox="0 0 10 10"/></defs></svg>',
        ];

        yield 'Handles empty svg element' => [
            '<svg></svg>',
            '<svg/>',
        ];

        yield 'Handles svg with whitespace only' => [
            '<svg>   </svg>',
            '<svg></svg>',
        ];

        yield 'Removes unknown attributes but keeps structure' => [
            '<svg foo="1"><g bar="2"><rect baz="3"/></g></svg>',
            '<svg><g><rect/></g></svg>',
        ];

        yield 'Keeps attributes with mixed case if standard' => [
            '<svg viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"><rect/></svg>',
            '<svg viewBox="0 0 100 100" preserveAspectRatio="xMidYMid meet"><rect/></svg>',
        ];
    }

    #[Test]
    public function ruleIsMarkedAsRisky(): void
    {
        self::assertFalse(RemoveNonStandardAttributes::isRisky());
    }

    #[Test]
    public function shouldCheckSizeReturnsFalse(): void
    {
        self::assertFalse(RemoveNonStandardAttributes::shouldCheckSize());
    }
}
