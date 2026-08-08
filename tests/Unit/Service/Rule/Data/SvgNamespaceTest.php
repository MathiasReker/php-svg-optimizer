<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Rule\Data;

use MathiasReker\PhpSvgOptimizer\Service\Rule\Data\SvgNamespace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(SvgNamespace::class)]
final class SvgNamespaceTest extends TestCase
{
    #[Test]
    public function valuesReturnsCorrectNamespaceUris(): void
    {
        $expected = [
            'http://www.w3.org/2000/svg',
            'http://www.w3.org/1999/xlink',
            'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd',
            'http://www.inkscape.org/namespaces/inkscape',
        ];

        self::assertSame($expected, SvgNamespace::values());
    }

    #[Test]
    public function prefixReturnsSvgForSvgNamespace(): void
    {
        self::assertSame('svg', SvgNamespace::Svg->prefix());
    }

    #[Test]
    public function prefixReturnsXlinkForXlinkNamespace(): void
    {
        self::assertSame('xlink', SvgNamespace::Xlink->prefix());
    }

    #[Test]
    public function prefixReturnsSodipodiForSodipodiNamespace(): void
    {
        self::assertSame('sodipodi', SvgNamespace::Sodipodi->prefix());
    }

    #[Test]
    public function prefixReturnsInkscapeForInkscapeNamespace(): void
    {
        self::assertSame('inkscape', SvgNamespace::Inkscape->prefix());
    }
}
