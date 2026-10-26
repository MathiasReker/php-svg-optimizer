<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Architecture\Rule;

/**
 * @internal
 *
 * @see PackageLayerTest
 */
final class PackageLayer
{
    private const string PACKAGE = 'MathiasReker\PhpSvgOptimizer\\';

    private const string TESTS = self::PACKAGE . 'Tests\\';

    private const string EXCEPTION = self::PACKAGE . 'Exception\\';

    /**
     * @param string $name the fully qualified class name
     *
     * @return bool true if the class belongs to the package and not to its tests
     */
    public static function isPackageClass(string $name): bool
    {
        return str_starts_with($name, self::PACKAGE) && !str_starts_with($name, self::TESTS);
    }

    /**
     * @param string $name the fully qualified class name of a package class
     *
     * @return bool true if the class is in the root namespace or in the exception namespace
     */
    public static function isRootApi(string $name): bool
    {
        return !str_contains(mb_substr($name, mb_strlen(self::PACKAGE)), '\\') || str_starts_with($name, self::EXCEPTION);
    }
}
