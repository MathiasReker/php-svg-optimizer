<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Data;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgTag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgTag::class)]
final class SvgTagTest extends TestCase
{
    #[Test]
    public function dangerousReturnsCorrectTags(): void
    {
        $expected = [
            'script',
            'foreignObject',
            'iframe',
            'object',
            'embed',
            'link',
        ];

        self::assertSame($expected, SvgTag::dangerous());
    }

    #[Test]
    public function conditionalDangerousReturnsCorrectTags(): void
    {
        $expected = [
            'a',
            'use',
            'tref',
            'image',
            'linearGradient',
            'radialGradient',
            'pattern',
        ];

        self::assertSame($expected, SvgTag::conditionalDangerous());
    }

    #[Test]
    public function animationReturnsTheAnimationTags(): void
    {
        self::assertSame(['animate', 'set', 'animatecolor', 'animatemotion', 'animatetransform'], SvgTag::animation());
    }

    #[Test]
    public function dataUriReturnsTheTagsThatMayEmbedADataUri(): void
    {
        self::assertSame(['image', 'feImage'], SvgTag::dataUri());
    }

    #[Test]
    public function valuesReturnsAllTagValues(): void
    {
        $values = SvgTag::values();

        self::assertCount(\count(SvgTag::cases()), $values);
        self::assertContains('svg', $values);
        self::assertContains('path', $values);
        self::assertContains('g', $values);
    }

    #[Test]
    public function caseListsMatchTheirValueLists(): void
    {
        $values = static fn (array $cases): array => array_map(static fn (SvgTag $svgTag): string => $svgTag->value, $cases);

        self::assertSame(SvgTag::dangerous(), $values(SvgTag::dangerousCases()));
        self::assertSame(SvgTag::conditionalDangerous(), $values(SvgTag::conditionalDangerousCases()));
        self::assertSame(SvgTag::animation(), $values(SvgTag::animationCases()));
        self::assertSame(SvgTag::dataUri(), $values(SvgTag::dataUriCases()));
    }
}
