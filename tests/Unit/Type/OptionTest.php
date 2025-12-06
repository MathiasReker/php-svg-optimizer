<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Type;

use MathiasReker\PhpSvgOptimizer\Type\Option;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Option::class)]
final class OptionTest extends TestCase
{
    #[DataProvider('provideGetShorthandCases')]
    public function testGetShorthand(Option $option, string $shorthand): void
    {
        self::assertSame($shorthand, $option->getShorthand());
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideGetShorthandCases(): iterable
    {
        yield [Option::HELP, '-h'];
        yield [Option::CONFIG, '-c'];
        yield [Option::DRY_RUN, '-d'];
        yield [Option::ALLOW_RISKY, '-r'];
        yield [Option::QUIET, '-q'];
        yield [Option::VERSION, '-v'];
    }

    #[DataProvider('provideGetFullCases')]
    public function testGetFull(Option $option, string $full): void
    {
        self::assertSame($full, $option->getFull());
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideGetFullCases(): iterable
    {
        yield [Option::HELP, '--help'];
        yield [Option::CONFIG, '--config'];
        yield [Option::DRY_RUN, '--dry-run'];
        yield [Option::ALLOW_RISKY, '--allow-risky'];
        yield [Option::QUIET, '--quiet'];
        yield [Option::VERSION, '--version'];
    }

    #[DataProvider('provideGetDescriptionCases')]
    public function testGetDescription(Option $option, string $description): void
    {
        self::assertSame($description, $option->getDescription());
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideGetDescriptionCases(): iterable
    {
        yield [Option::HELP, 'Display help for the command.'];
        yield [Option::CONFIG, 'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.'];
        yield [Option::DRY_RUN, 'Only calculate potential savings without modifying the files.'];
        yield [Option::ALLOW_RISKY, 'Explicitly enables risky rules, allowing them to be applied.'];
        yield [Option::QUIET, 'Suppress all output except errors.'];
        yield [Option::VERSION, 'Display the version of the library.'];
    }

    #[DataProvider('provideEnumValuesCases')]
    public function testEnumValues(Option $option, string $value): void
    {
        self::assertSame($value, $option->value);
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideEnumValuesCases(): iterable
    {
        yield [Option::HELP, 'help'];
        yield [Option::CONFIG, 'config'];
        yield [Option::DRY_RUN, 'dry-run'];
        yield [Option::ALLOW_RISKY, 'allow-risky'];
        yield [Option::QUIET, 'quiet'];
        yield [Option::VERSION, 'version'];
    }

    public function testShorthandValues(): void
    {
        self::assertSame('-h', Option::HELP->getShorthand());
        self::assertSame('-c', Option::CONFIG->getShorthand());
        self::assertSame('-d', Option::DRY_RUN->getShorthand());
        self::assertSame('-r', Option::ALLOW_RISKY->getShorthand());
        self::assertSame('-a', Option::WITH_ALL_RULES->getShorthand());
        self::assertSame('-q', Option::QUIET->getShorthand());
        self::assertSame('-v', Option::VERSION->getShorthand());
    }

    public function testFullValues(): void
    {
        self::assertSame('--help', Option::HELP->getFull());
        self::assertSame('--config', Option::CONFIG->getFull());
        self::assertSame('--dry-run', Option::DRY_RUN->getFull());
        self::assertSame('--allow-risky', Option::ALLOW_RISKY->getFull());
        self::assertSame('--with-all-rules', Option::WITH_ALL_RULES->getFull());
        self::assertSame('--quiet', Option::QUIET->getFull());
        self::assertSame('--version', Option::VERSION->getFull());
    }

    public function testDescriptionValues(): void
    {
        self::assertSame('Display help for the command.', Option::HELP->getDescription());
        self::assertSame(
            'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.',
            Option::CONFIG->getDescription()
        );
        self::assertSame(
            'Only calculate potential savings without modifying the files.',
            Option::DRY_RUN->getDescription()
        );
        self::assertSame(
            'Explicitly enables risky rules, allowing them to be applied.',
            Option::ALLOW_RISKY->getDescription()
        );
        self::assertSame(
            'Enable all non-risky rules. Use --allow-risky to include risky rules as well.',
            Option::WITH_ALL_RULES->getDescription()
        );
        self::assertSame(
            'Suppress all output except errors.',
            Option::QUIET->getDescription()
        );
        self::assertSame(
            'Display the version of the library.',
            Option::VERSION->getDescription()
        );
    }
}
