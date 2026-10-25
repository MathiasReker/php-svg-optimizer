<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Svg;

use MathiasReker\PhpSvgOptimizer\Svg\Attribute;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Attribute::class)]
final class AttributeTest extends TestCase
{
    #[Test]
    public function colorValuedNamesListTheAttributesThatTakeAColor(): void
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

        self::assertSame($expected, Attribute::colorValuedNames());
    }

    #[Test]
    public function inheritedNamesListTheInheritedPresentationAttributes(): void
    {
        $inherited = Attribute::inheritedNames();

        self::assertCount(41, $inherited);
        self::assertSame('clip-rule', $inherited[0]);
        self::assertSame('writing-mode', $inherited[40]);
        self::assertContains('color', $inherited);
        self::assertContains('fill', $inherited);
        self::assertNotContains('transform', $inherited);
        self::assertNotContains('opacity', $inherited);
        self::assertSame($inherited, Attribute::inheritedNames());
    }

    #[Test]
    public function urlValuedNamesListTheAttributesThatMayCarryAUrl(): void
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

        self::assertSame($expected, Attribute::urlValuedNames());
    }

    #[Test]
    public function linkNamesListTheAttributesWhoseValueIsAUrl(): void
    {
        $expected = [
            'xlink:href',
            'href',
        ];

        self::assertSame($expected, Attribute::linkNames());
    }

    #[Test]
    public function valuesListEveryAttributeName(): void
    {
        $values = Attribute::values();

        self::assertCount(\count(Attribute::cases()), $values);
        self::assertContains('fill', $values);
        self::assertContains('id', $values);
        self::assertContains('viewBox', $values);
    }

    #[Test]
    public function htmlOnlyNamesListTheAttributesWithoutMeaningInSvg(): void
    {
        $htmlOnly = Attribute::htmlOnlyNames();

        foreach (['accept', 'src', 'srcset', 'action', 'background', 'poster', 'value', 'name', 'xml:base'] as $attribute) {
            self::assertContains($attribute, $htmlOnly);
        }

        foreach (['width', 'href', 'fill', 'viewBox', 'onclick', 'autofocus', 'tabindex', 'target'] as $attribute) {
            self::assertNotContains($attribute, $htmlOnly);
        }

        self::assertSame($htmlOnly, array_values(array_unique($htmlOnly)));
    }

    #[Test]
    public function referrerPolicyUsesTheSpecificationSpelling(): void
    {
        self::assertContains('referrerpolicy', Attribute::values());
        self::assertNotContains('referrerPolicy', Attribute::values());
    }

    #[Test]
    public function urlValuedNamesIncludeEverySmilValueAttribute(): void
    {
        foreach (['from', 'to', 'by', 'values'] as $attribute) {
            self::assertContains($attribute, Attribute::urlValuedNames());
        }
    }
}
