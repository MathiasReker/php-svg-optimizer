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
use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\Trait\BaseEnumTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgAttribute::class)]
#[CoversTrait(BaseEnumTrait::class)]
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
    public function inheritedReturnsTheInheritedPresentationAttributes(): void
    {
        $inherited = SvgAttribute::inherited();

        self::assertCount(41, $inherited);
        self::assertSame('clip-rule', $inherited[0]);
        self::assertSame('writing-mode', $inherited[40]);
        self::assertContains('color', $inherited);
        self::assertContains('fill', $inherited);
        self::assertNotContains('transform', $inherited);
        self::assertNotContains('opacity', $inherited);
        self::assertSame($inherited, SvgAttribute::inherited());
        self::assertCount(41, SvgAttribute::inheritedCases());
        self::assertNotSame([], SvgAttribute::colorCases());
        self::assertNotSame([], SvgAttribute::dangerousExactCases());
        self::assertNotSame([], SvgAttribute::htmlOnlyCases());
        self::assertNotSame([], SvgAttribute::dangerousCases());
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
    public function htmlOnlyReturnsAttributesWithoutMeaningInSvg(): void
    {
        $htmlOnly = SvgAttribute::htmlOnly();

        foreach (['accept', 'src', 'srcset', 'action', 'background', 'poster', 'value', 'name', 'xml:base'] as $attribute) {
            self::assertContains($attribute, $htmlOnly);
        }

        foreach (['width', 'href', 'fill', 'viewBox', 'onclick', 'autofocus', 'tabindex', 'target'] as $attribute) {
            self::assertNotContains($attribute, $htmlOnly);
        }

        self::assertSame(
            array_map(static fn (SvgAttribute $svgAttribute): string => $svgAttribute->value, SvgAttribute::htmlOnlyCases()),
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
