<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Data;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgAttribute;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgElementAttributes;
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgElementAttributes::class)]
final class SvgElementAttributesTest extends TestCase
{
    private const array EXPECTED_MISSING_FROM_ENUM = [
        'playbackorder',
        'timelinebegin',
    ];

    #[Test]
    public function everySvgTagIsAKnownElement(): void
    {
        foreach (SvgTag::values() as $tag) {
            self::assertTrue(SvgElementAttributes::isKnownElement($tag), $tag);
        }
    }

    #[Test]
    public function everyAttributeIsAnSvgAttributeValue(): void
    {
        $known = array_fill_keys(SvgAttribute::values(), true);
        $missingLookup = [];

        foreach (SvgElementAttributes::elements() as $element) {
            foreach (array_keys(SvgElementAttributes::forElement($element)) as $attribute) {
                if (!\array_key_exists($attribute, $known)) {
                    $missingLookup[$attribute] = true;
                }
            }
        }

        $missing = array_keys($missingLookup);
        sort($missing);

        self::assertSame(self::EXPECTED_MISSING_FROM_ENUM, $missing);
    }

    /**
     * @param list<string> $attributes
     */
    #[DataProvider('provideElementAllowsAttributesCases')]
    #[Test]
    public function elementAllowsAttributes(string $element, array $attributes): void
    {
        $allowed = SvgElementAttributes::forElement($element);

        foreach ($attributes as $attribute) {
            self::assertArrayHasKey($attribute, $allowed, $element . ' should allow ' . $attribute);
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideElementAllowsAttributesCases(): iterable
    {
        yield 'rect' => ['rect', ['x', 'y', 'width', 'height', 'rx', 'ry', 'fill', 'stroke', 'transform', 'id', 'class', 'style', 'onclick']];
        yield 'path' => ['path', ['d', 'pathLength']];
        yield 'stop' => ['stop', ['offset', 'stop-color']];
        yield 'linearGradient' => ['linearGradient', ['x1', 'gradientUnits', 'gradientTransform', 'spreadMethod', 'href', 'xlink:href']];
        yield 'feGaussianBlur' => ['feGaussianBlur', ['stdDeviation', 'in', 'result', 'x', 'edgeMode']];
        yield 'animate' => ['animate', ['attributeName', 'from', 'to', 'values', 'dur', 'begin', 'repeatCount', 'fill', 'href']];
        yield 'svg' => ['svg', ['viewBox', 'preserveAspectRatio', 'width', 'height', 'version']];
        yield 'use' => ['use', ['href', 'xlink:href', 'x', 'y', 'width', 'height']];
    }

    /**
     * @param list<string> $attributes
     */
    #[DataProvider('provideElementDisallowsAttributesCases')]
    #[Test]
    public function elementDisallowsAttributes(string $element, array $attributes): void
    {
        $allowed = SvgElementAttributes::forElement($element);

        foreach ($attributes as $attribute) {
            self::assertArrayNotHasKey($attribute, $allowed, $element . ' should not allow ' . $attribute);
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideElementDisallowsAttributesCases(): iterable
    {
        yield 'rect' => ['rect', ['href', 'points', 'd', 'viewBox', 'offset']];
        yield 'linearGradient' => ['linearGradient', ['transform']];
        yield 'animateMotion' => ['animateMotion', ['attributeName']];
    }

    #[Test]
    public function forElementReturnsTrueValuedLookup(): void
    {
        $allowed = SvgElementAttributes::forElement('circle');

        self::assertNotSame([], $allowed);
        self::assertSame([true], array_values(array_unique($allowed)));
        self::assertSame($allowed, SvgElementAttributes::forElement('circle'));
    }

    #[Test]
    public function textNodeHasNoAttributes(): void
    {
        self::assertTrue(SvgElementAttributes::isKnownElement('#text'));
        self::assertSame([], SvgElementAttributes::forElement('#text'));
    }

    #[Test]
    public function unknownElementReturnsEmptyLookup(): void
    {
        self::assertFalse(SvgElementAttributes::isKnownElement('unknown-element'));
        self::assertSame([], SvgElementAttributes::forElement('unknown-element'));
    }
}
