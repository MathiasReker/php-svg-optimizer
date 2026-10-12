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

        yield 'Keeps xml, xlink and data-* attributes but removes on* attributes' => [
            '<svg xml:lang="en" data-test="1" onload="alert(1)" onfake="oops"><use xlink:href="#a" onclick="x()"/></svg>',
            '<svg xml:lang="en" data-test="1"><use xlink:href="#a"/></svg>',
        ];

        yield 'Removes attributes the specification does not define for the element' => [
            '<svg xlink:href="#a" points="1 2" viewBox="0 0 1 1"><rect d="M0 0" href="#a" width="1"/><path points="1 2" d="M0 0"/><stop offset="1" x="1"/></svg>',
            '<svg viewBox="0 0 1 1"><rect width="1"/><path d="M0 0"/><stop offset="1"/></svg>',
        ];

        yield 'Keeps ev:event bound to the XML Events namespace where allowed' => [
            '<svg xmlns:ev="http://www.w3.org/2001/xml-events"><script ev:event="load"/><rect ev:event="load" width="1"/></svg>',
            '<svg xmlns:ev="http://www.w3.org/2001/xml-events"><script ev:event="load"/><rect width="1"/></svg>',
        ];

        yield 'Keeps attributes the specification defines for the element' => [
            '<svg><animate attributeName="x" min="1s" max="2s" dur="3s"/><textPath method="align" startOffset="1">a</textPath><linearGradient gradientUnits="userSpaceOnUse" x1="0"/></svg>',
            '<svg><animate attributeName="x" min="1s" max="2s" dur="3s"/><textPath method="align" startOffset="1">a</textPath><linearGradient gradientUnits="userSpaceOnUse" x1="0"/></svg>',
        ];

        yield 'Keeps shared presentation and aria attributes on every SVG element' => [
            '<svg fill="red" aria-label="x"><g stroke="blue" role="img"><circle r="1" opacity=".5"/></g></svg>',
            '<svg fill="red" aria-label="x"><g stroke="blue" role="img"><circle r="1" opacity=".5"/></g></svg>',
        ];

        yield 'Falls back to the global allowlist for unknown elements' => [
            '<svg><unknown width="1" d="M0 0" foo="bar" onclick="x()"/></svg>',
            '<svg><unknown width="1" d="M0 0"/></svg>',
        ];

        yield 'Removes HTML-only attributes from unknown elements' => [
            '<svg><unknown accept="x" action="/post" width="1" value="1"/></svg>',
            '<svg><unknown width="1"/></svg>',
        ];

        yield 'Keeps data-* but removes similar-looking attribute' => [
            '<svg data-test="1" dataTest="2"></svg>',
            '<svg data-test="1"/>',
        ];

        yield 'Removes on* event attributes' => [
            '<svg onclick="doSomething()" onmouseover="hover()" onfake="fail()" width="1"></svg>',
            '<svg width="1"/>',
        ];

        yield 'Removes name attribute' => [
            '<svg><rect id="a" name="getElementById"/></svg>',
            '<svg><rect id="a"/></svg>',
        ];

        yield 'Removes attributes with javascript-like values if name is unknown' => [
            '<svg foo="javascript:alert(1)" width="10"></svg>',
            '<svg width="10"/>',
        ];

        yield 'Removes namespaced attribute not explicitly allowed' => [
            '<svg><script ev:event="load" custom:attr="x"/></svg>',
            '<svg><script ev:event="load"/></svg>',
        ];

        yield 'Removes attribute bound to a foreign namespace even if its local name is allowed' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:evil"><a x:href="#a" x:onclick="1" href="#b"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:x="urn:evil"><a href="#b"/></svg>',
        ];

        yield 'Keeps known xlink attributes and removes unknown ones' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#a" xlink:title="t" xlink:foo="1"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="#a" xlink:title="t"/></svg>',
        ];

        yield 'Keeps xlink attributes bound via a non-standard prefix' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><use l:href="#a"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:l="http://www.w3.org/1999/xlink"><use l:href="#a"/></svg>',
        ];

        yield 'Removes unknown and dangerous xml attributes' => [
            '<svg xml:space="preserve" xml:base="http://evil.com/" xml:foo="1"></svg>',
            '<svg xml:space="preserve"/>',
        ];

        yield 'Removes HTML-only attributes' => [
            '<svg><image src="x.png" srcset="x.png 1x" width="10"/><g action="/post" background="x.png" value="1"/></svg>',
            '<svg><image width="10"/><g/></svg>',
        ];

        yield 'Removes data-* attribute with invalid name' => [
            '<svg data-="1" data-ok.v2="2"></svg>',
            '<svg data-ok.v2="2"/>',
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
