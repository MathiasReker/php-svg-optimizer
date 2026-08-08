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
}
