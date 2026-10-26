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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(NamespaceUri::class)]
final class NamespaceUriTest extends TestCase
{
    #[DataProvider('provideEachNamespaceHasItsConventionalPrefixCases')]
    #[Test]
    public function eachNamespaceHasItsConventionalPrefix(NamespaceUri $namespaceUri, string $prefix): void
    {
        self::assertSame($prefix, $namespaceUri->prefix());
    }

    /**
     * @return iterable<string, array{NamespaceUri, string}>
     */
    public static function provideEachNamespaceHasItsConventionalPrefixCases(): iterable
    {
        yield 'SVG' => [NamespaceUri::Svg, 'svg'];

        yield 'XLink' => [NamespaceUri::Xlink, 'xlink'];

        yield 'Sodipodi' => [NamespaceUri::Sodipodi, 'sodipodi'];

        yield 'Inkscape' => [NamespaceUri::Inkscape, 'inkscape'];

        yield 'XML' => [NamespaceUri::Xml, 'xml'];

        yield 'XML Events' => [NamespaceUri::XmlEvents, 'ev'];

        yield 'XHTML' => [NamespaceUri::Xhtml, 'html'];
    }
}
