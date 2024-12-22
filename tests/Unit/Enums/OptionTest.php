<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Enums;

use MathiasReker\PhpSvgOptimizer\Enums\Option;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class OptionTest extends TestCase
{
    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideGetShorthandCases(): iterable
    {
        yield [Option::HELP, '-h'];
        yield [Option::CONFIG, '-c'];
        yield [Option::DRY_RUN, '-d'];
        yield [Option::QUIET, '-q'];
        yield [Option::VERSION, '-v'];
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideGetFullCases(): iterable
    {
        yield [Option::HELP, '--help'];
        yield [Option::CONFIG, '--config'];
        yield [Option::DRY_RUN, '--dry-run'];
        yield [Option::QUIET, '--quiet'];
        yield [Option::VERSION, '--version'];
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideGetDescriptionCases(): iterable
    {
        yield [Option::HELP, 'Display help for the command.'];
        yield [Option::CONFIG, 'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.'];
        yield [Option::DRY_RUN, 'Only calculate potential savings without modifying the files.'];
        yield [Option::QUIET, 'Suppress all output except errors.'];
        yield [Option::VERSION, 'Display the version of the library.'];
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideEnumValuesCases(): iterable
    {
        yield [Option::HELP, 'help'];
        yield [Option::CONFIG, 'config'];
        yield [Option::DRY_RUN, 'dry-run'];
        yield [Option::QUIET, 'quiet'];
        yield [Option::VERSION, 'version'];
    }

    #[DataProvider('provideGetShorthandCases')]
    public function testGetShorthand(Option $option, string $shorthand): void
    {
        self::assertSame($shorthand, $option->getShorthand());
    }

    #[DataProvider('provideGetFullCases')]
    public function testGetFull(Option $option, string $full): void
    {
        self::assertSame($full, $option->getFull());
    }

    #[DataProvider('provideGetDescriptionCases')]
    public function testGetDescription(Option $option, string $description): void
    {
        self::assertSame($description, $option->getDescription());
    }

    #[DataProvider('provideEnumValuesCases')]
    public function testEnumValues(Option $option, string $value): void
    {
        self::assertSame($value, $option->value);
    }
}
