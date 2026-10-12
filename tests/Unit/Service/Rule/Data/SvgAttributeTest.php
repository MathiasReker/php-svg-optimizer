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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgAttribute::class)]
final class SvgAttributeTest extends TestCase
{
    #[Test]
    public function colorsReturnsCorrectAttributes(): void
    {
        $expected = [
            'fill',
            'stroke',
            'color',
            'stop-color',
            'flood-color',
            'lighting-color',
            'solid-color',
            'background-color',
            'border-color',
        ];

        self::assertSame($expected, SvgAttribute::colors());
    }

    #[Test]
    public function dangerousReturnsCorrectAttributes(): void
    {
        $expected = [
            'fill',
            'stroke',
            'filter',
            'clip-path',
            'mask',
            'marker-start',
            'marker-mid',
            'marker-end',
            'begin',
            'pattern',
            'end',
            'from',
            'to',
            'by',
            'values',
            'style',
            'cursor',
            'background',
            'border',
            'color-profile',
            'marker',
            'overlay',
        ];

        self::assertSame($expected, SvgAttribute::dangerous());
    }

    #[Test]
    public function dangerousExactReturnsCorrectAttributes(): void
    {
        $expected = [
            'xlink:href',
            'href',
        ];

        self::assertSame($expected, SvgAttribute::dangerousExact());
    }

    #[Test]
    public function valuesReturnsAllAttributeValues(): void
    {
        $values = SvgAttribute::values();

        self::assertCount(\count(SvgAttribute::cases()), $values);
        self::assertContains('fill', $values);
        self::assertContains('id', $values);
        self::assertContains('viewBox', $values);
    }

    #[Test]
    public function eventHandlersReturnsEveryOnAttribute(): void
    {
        $eventHandlers = SvgAttribute::eventHandlers();
        $expected = array_values(array_filter(
            SvgAttribute::values(),
            static fn (string $value): bool => str_starts_with($value, 'on'),
        ));

        self::assertSame($expected, $eventHandlers);
        self::assertContains('onclick', $eventHandlers);
        self::assertContains('onload', $eventHandlers);
        self::assertContains('onwheel', $eventHandlers);
        self::assertNotContains('opacity', $eventHandlers);
        self::assertNotContains('orientation', $eventHandlers);
    }

    #[Test]
    public function eventHandlersIsCached(): void
    {
        self::assertSame(SvgAttribute::eventHandlers(), SvgAttribute::eventHandlers());
        self::assertSame(
            array_map(static fn (SvgAttribute $attribute): string => $attribute->value, SvgAttribute::eventHandlerCases()),
            SvgAttribute::eventHandlers(),
        );
    }

    #[Test]
    public function htmlOnlyReturnsAttributesWithoutMeaningInSvg(): void
    {
        $htmlOnly = SvgAttribute::htmlOnly();

        foreach (['src', 'srcset', 'action', 'background', 'poster', 'value', 'name', 'xml:base'] as $attribute) {
            self::assertContains($attribute, $htmlOnly);
        }

        foreach (['width', 'href', 'fill', 'viewBox', 'onclick', 'autofocus', 'tabindex', 'target'] as $attribute) {
            self::assertNotContains($attribute, $htmlOnly);
        }

        self::assertSame(
            array_map(static fn (SvgAttribute $attribute): string => $attribute->value, SvgAttribute::htmlOnlyCases()),
            $htmlOnly,
        );
        self::assertSame($htmlOnly, array_values(array_unique($htmlOnly)));
    }

    #[Test]
    public function referrerPolicyUsesTheSpecificationSpelling(): void
    {
        self::assertContains('referrerpolicy', SvgAttribute::values());
        self::assertNotContains('referrerPolicy', SvgAttribute::values());
    }

    #[Test]
    public function dangerousIncludesEverySmilValueAttribute(): void
    {
        foreach (['from', 'to', 'by', 'values'] as $attribute) {
            self::assertContains($attribute, SvgAttribute::dangerous());
        }
    }
}
