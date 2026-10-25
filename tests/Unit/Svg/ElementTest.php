<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Svg;

use MathiasReker\PhpSvgOptimizer\Svg\Element;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Element::class)]
final class ElementTest extends TestCase
{
    #[Test]
    public function linkingNamesListTheElementsThatLinkToAResource(): void
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

        self::assertSame($expected, Element::linkingNames());
    }

    #[Test]
    public function animationNamesListTheAnimationElements(): void
    {
        self::assertSame(['animate', 'set', 'animateColor', 'animateMotion', 'animateTransform'], Element::animationNames());
    }

    #[Test]
    public function imageLoadingNamesListTheElementsThatLoadAnImage(): void
    {
        self::assertSame(['image', 'feImage'], Element::imageLoadingNames());
    }

    #[Test]
    public function valuesListEveryElementName(): void
    {
        $values = Element::values();

        self::assertCount(\count(Element::cases()), $values);
        self::assertContains('svg', $values);
        self::assertContains('path', $values);
        self::assertContains('g', $values);
    }

    #[Test]
    public function valuesAreElementNamesThatDifferBeyondCase(): void
    {
        $values = Element::values();

        self::assertCount(\count($values), array_unique(array_map(mb_strtolower(...), $values)));
        self::assertContains('altGlyphDef', $values);
        self::assertContains('glyphRef', $values);
        self::assertNotContains('#text', $values);
    }
}
