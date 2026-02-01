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
use MathiasReker\PhpSvgOptimizer\Service\Provider\StringProvider;
use MathiasReker\PhpSvgOptimizer\Service\Rule\FixAttributeNames;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FixAttributeNames::class)]
final class FixAttributeNamesTest extends TestCase
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
        $svgOptimizer->addRule(new FixAttributeNames());

        $actual = $svgOptimizer->allowRisky()->optimize()->getContent();

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield 'Normalizes clippath variations' => [
            '<svg><rect clipPath="rect"/></svg>',
            '<svg><rect clip-path="rect"/></svg>',
        ];

        yield 'Normalizes stroke-width and fill' => [
            '<svg><circle fill="blue" stRokE-width="1"/></svg>',
            '<svg><circle fill="blue" stroke-width="1"/></svg>',
        ];

        yield 'Ignores attributes not in SvgAttribute enum' => [
            '<svg><rect anotherAttr="1"/></svg>',
            '<svg><rect anotherAttr="1"/></svg>',
        ];

        yield 'Clip-path lowercase' => [
            '<svg><rect clipPath="b"/></svg>',
            '<svg><rect clip-path="b"/></svg>',
        ];

        yield 'Clip-path mixed case' => [
            '<svg><rect ClIpPaTh="b"/></svg>',
            '<svg><rect clip-path="b"/></svg>',
        ];

        yield 'Normalizes root svg attribute' => [
            '<svg ClIpPaTh="b"></svg>',
            '<svg clip-path="b"/>',
        ];

        yield 'Normalizes multiple root svg attributes' => [
            '<svg ClIpPaTh="b" stRokE-width="2"></svg>',
            '<svg clip-path="b" stroke-width="2"/>',
        ];

        yield 'Normalizes both root and child attributes' => [
            '<svg ClIpPaTh="a"><rect stRokE-width="1"/></svg>',
            '<svg clip-path="a"><rect stroke-width="1"/></svg>',
        ];

        yield 'Keeps already normalized attributes unchanged' => [
            '<svg clip-path="a"><rect stroke-width="1"/></svg>',
            '<svg clip-path="a"><rect stroke-width="1"/></svg>',
        ];

        yield 'Handles attributes without hyphen but matching enum' => [
            '<svg><rect strokewidth="3"/></svg>',
            '<svg><rect stroke-width="3"/></svg>',
        ];

        yield 'Preserves attribute values exactly' => [
            '<svg><rect clipPath="url(#myClip)"/></svg>',
            '<svg><rect clip-path="url(#myClip)"/></svg>',
        ];

        yield 'Multiple attributes on same element' => [
            '<svg><rect ClIpPaTh="a" fIll="red" stRokE-width="4"/></svg>',
            '<svg><rect clip-path="a" fill="red" stroke-width="4"/></svg>',
        ];

        yield 'Root svg with multiple children and mixed attributes' => [
            '<svg ClIpPaTh="a"><g><rect stRokE-width="1"/></g><circle FILL="red"/></svg>',
            '<svg clip-path="a"><g><rect stroke-width="1"/></g><circle fill="red"/></svg>',
        ];

        yield 'Deeply nested elements' => [
            '<svg><g><g><g><rect ClIpPaTh="x"/></g></g></g></svg>',
            '<svg><g><g><g><rect clip-path="x"/></g></g></g></svg>',
        ];

        yield 'Attributes differing only by case already normalized' => [
            '<svg><rect clip-path="a" fill="blue"/></svg>',
            '<svg><rect clip-path="a" fill="blue"/></svg>',
        ];

        yield 'Attributes with numbers preserved' => [
            '<svg><rect stRokE-width="0.5"/></svg>',
            '<svg><rect stroke-width="0.5"/></svg>',
        ];

        yield 'Attributes with percentage values preserved' => [
            '<svg><rect ClIpPaTh="10%"/></svg>',
            '<svg><rect clip-path="10%"/></svg>',
        ];

        yield 'Multiple unknown attributes mixed with known ones' => [
            '<svg><rect foo="1" ClIpPaTh="a" bar="2"/></svg>',
            '<svg><rect foo="1" bar="2" clip-path="a"/></svg>',
        ];

        yield 'Root svg with unknown attribute only' => [
            '<svg foo="bar"></svg>',
            '<svg foo="bar"/>',
        ];

        yield 'Root svg with known and unknown attributes' => [
            '<svg foo="bar" ClIpPaTh="x"></svg>',
            '<svg foo="bar" clip-path="x"/>',
        ];

        yield 'Handles attributes on self-closing child nodes' => [
            '<svg><rect ClIpPaTh="a"/></svg>',
            '<svg><rect clip-path="a"/></svg>',
        ];

        yield 'Handles attributes on non-graphical elements' => [
            '<svg><defs><clipPath id="c"><rect stRokE-width="1"/></clipPath></defs></svg>',
            '<svg><defs><clipPath id="c"><rect stroke-width="1"/></clipPath></defs></svg>',
        ];

        yield 'Does not duplicate attributes when normalizing' => [
            '<svg><rect clip-path="a" ClIpPaTh="b"/></svg>',
            '<svg><rect clip-path="b"/></svg>',
        ];

        yield 'Normalization is deterministic regardless of attribute order' => [
            '<svg><rect stRokE-width="1" ClIpPaTh="a"/></svg>',
            '<svg><rect stroke-width="1" clip-path="a"/></svg>',
        ];

        yield 'Whitespace around attributes is ignored by normalization' => [
            '<svg><rect   ClIpPaTh = "a"   /></svg>',
            '<svg><rect clip-path="a"/></svg>',
        ];

        yield 'Attributes on multiple sibling elements' => [
            '<svg><rect ClIpPaTh="a"/><rect ClIpPaTh="b"/></svg>',
            '<svg><rect clip-path="a"/><rect clip-path="b"/></svg>',
        ];

        yield 'Mixed correct and incorrect attributes on same element' => [
            '<svg><rect clip-path="a" stRokE-width="1"/></svg>',
            '<svg><rect clip-path="a" stroke-width="1"/></svg>',
        ];

        yield 'Handles empty attribute values' => [
            '<svg><rect ClIpPaTh=""/></svg>',
            '<svg><rect clip-path=""/></svg>',
        ];

        yield 'Handles boolean-like attributes as strings' => [
            '<svg><rect ClIpPaTh="true"/></svg>',
            '<svg><rect clip-path="true"/></svg>',
        ];

        yield 'Attributes on text elements' => [
            '<svg><text stRokE-width="1">Hello</text></svg>',
            '<svg><text stroke-width="1">Hello</text></svg>',
        ];

        yield 'Attributes on path elements' => [
            '<svg><path ClIpPaTh="a" d="M0 0"/></svg>',
            '<svg><path d="M0 0" clip-path="a"/></svg>',
        ];

        yield 'Attributes on use elements' => [
            '<svg><use href="#x" ClIpPaTh="a"/></svg>',
            '<svg><use href="#x" clip-path="a"/></svg>',
        ];

        yield 'Does not affect XML namespace attributes' => [
            '<svg xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#x"/></svg>',
            '<svg xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#x"/></svg>',
        ];

        yield 'Does not normalize namespaced attributes' => [
            '<svg><use xlink:href="#x" ClIpPaTh="a"/></svg>',
            '<svg><use xlink:href="#x" clip-path="a"/></svg>',
        ];

        yield 'Handles large number of attributes' => [
            '<svg><rect ClIpPaTh="a" stRokE-width="1" fIll="red" foo="1" bar="2"/></svg>',
            '<svg><rect foo="1" bar="2" clip-path="a" stroke-width="1" fill="red"/></svg>',
        ];

        yield 'Root svg attributes mixed with namespace declarations' => [
            '<svg xmlns="http://www.w3.org/2000/svg" ClIpPaTh="a"></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" clip-path="a"/>',
        ];

        yield 'Normalization does not create duplicate namespace attributes' => [
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"/>',
        ];
    }
}
