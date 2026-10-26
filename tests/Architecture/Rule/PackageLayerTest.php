<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Architecture\Rule;

use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PackageLayer::class)]
final class PackageLayerTest extends TestCase
{
    private const string PACKAGE = 'MathiasReker\PhpSvgOptimizer\\';

    #[DataProvider('provideTellsWhetherAClassBelongsToThePackageCases')]
    #[Test]
    public function tellsWhetherAClassBelongsToThePackage(string $name, bool $expected): void
    {
        self::assertSame($expected, PackageLayer::isPackageClass($name));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideTellsWhetherAClassBelongsToThePackageCases(): iterable
    {
        yield 'root class' => [self::PACKAGE . 'SvgOptimizer', true];

        yield 'internal class' => [self::PACKAGE . 'Pass\Pipeline', true];

        yield 'test class' => [self::PACKAGE . 'Tests\Unit\MetricsTest', false];

        yield 'vendor class' => [Rule::class, false];
    }

    #[DataProvider('provideTellsWhetherAPackageClassBelongsToThePublicApiCases')]
    #[Test]
    public function tellsWhetherAPackageClassBelongsToThePublicApi(string $name, bool $expected): void
    {
        self::assertSame($expected, PackageLayer::isRootApi($name));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideTellsWhetherAPackageClassBelongsToThePublicApiCases(): iterable
    {
        yield 'root class' => [self::PACKAGE . 'SvgOptimizer', true];

        yield 'exception' => [self::PACKAGE . 'Exception\InvalidSvgException', true];

        yield 'internal class' => [self::PACKAGE . 'Pass\Pipeline', false];

        yield 'internal exception' => [self::PACKAGE . 'Xml\ParseException', false];
    }
}
