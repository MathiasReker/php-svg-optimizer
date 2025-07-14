<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Service\Formatter;

use MathiasReker\PhpSvgOptimizer\Service\Formatter\XmlFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(XmlFormatter::class)]
final class XmlFormatterTest extends TestCase
{
    public function testRemoveLineFeedsAndTabs(): void
    {
        $input = "<svg>\n\t<rect/>\r\n</svg>";
        $expected = '<svg><rect/></svg>';

        $actual = XmlFormatter::removeLineFeedsAndTabs($input);
        self::assertSame($expected, $actual);
    }

    public function testRemoveWhitespaceBetweenTags(): void
    {
        $input = '<svg>   <rect>   </rect>   <circle/>   </svg>';
        $expected = '<svg><rect></rect><circle/></svg>';

        $actual = XmlFormatter::removeWhitespaceBetweenTags($input);
        self::assertSame($expected, $actual);
    }

    public function testRemoveLineFeedsAndTabsDoesNotAffectSpaces(): void
    {
        $input = '<svg>   <rect>Text content</rect>   </svg>';
        $expected = $input;

        $actual = XmlFormatter::removeLineFeedsAndTabs($input);
        self::assertSame($expected, $actual);
    }

    public function testRemoveWhitespaceBetweenTagsPreservesInnerTextSpacing(): void
    {
        $input = '<svg><text> Some   spaced  text </text></svg>';
        $expected = '<svg><text> Some   spaced  text </text></svg>';

        $actual = XmlFormatter::removeWhitespaceBetweenTags($input);
        self::assertSame($expected, $actual);
    }
}
