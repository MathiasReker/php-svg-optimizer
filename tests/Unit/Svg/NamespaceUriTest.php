<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Svg;

use MathiasReker\PhpSvgOptimizer\Svg\NamespaceUri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(NamespaceUri::class)]
final class NamespaceUriTest extends TestCase
{
    #[Test]
    public function prefixReturnsSvgForSvgNamespace(): void
    {
        self::assertSame('svg', NamespaceUri::Svg->prefix());
    }

    #[Test]
    public function prefixReturnsXlinkForXlinkNamespace(): void
    {
        self::assertSame('xlink', NamespaceUri::Xlink->prefix());
    }

    #[Test]
    public function prefixReturnsSodipodiForSodipodiNamespace(): void
    {
        self::assertSame('sodipodi', NamespaceUri::Sodipodi->prefix());
    }

    #[Test]
    public function prefixReturnsInkscapeForInkscapeNamespace(): void
    {
        self::assertSame('inkscape', NamespaceUri::Inkscape->prefix());
    }

    #[Test]
    public function prefixReturnsXmlForXmlNamespace(): void
    {
        self::assertSame('xml', NamespaceUri::Xml->prefix());
    }

    #[Test]
    public function prefixReturnsEvForXmlEventsNamespace(): void
    {
        self::assertSame('ev', NamespaceUri::XmlEvents->prefix());
    }
}
