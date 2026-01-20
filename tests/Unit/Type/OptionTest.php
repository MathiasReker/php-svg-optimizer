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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Option::class)]
final class OptionTest extends TestCase
{
    #[DataProvider('provideOptimizeCases')]
    #[Test]
    public function optimize(Option $option, string $shorthand): void
    {
        self::assertSame($shorthand, $option->getShorthand());
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideOptimizeCases(): iterable
    {
        yield [Option::Help, '-h'];
        yield [Option::Config, '-c'];
        yield [Option::DryRun, '-d'];
        yield [Option::AllowRisky, '-r'];
        yield [Option::Quiet, '-q'];
        yield [Option::Version, '-v'];
    }

    #[DataProvider('provideEnumValuesCases')]
    #[Test]
    public function enumValues(Option $option, string $value): void
    {
        self::assertSame($value, $option->value);
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideEnumValuesCases(): iterable
    {
        yield [Option::Help, 'help'];
        yield [Option::Config, 'config'];
        yield [Option::DryRun, 'dry-run'];
        yield [Option::AllowRisky, 'allow-risky'];
        yield [Option::Quiet, 'quiet'];
        yield [Option::Version, 'version'];
    }

    #[Test]
    public function shorthandValues(): void
    {
        self::assertSame('-h', Option::Help->getShorthand());
        self::assertSame('-c', Option::Config->getShorthand());
        self::assertSame('-d', Option::DryRun->getShorthand());
        self::assertSame('-r', Option::AllowRisky->getShorthand());
        self::assertSame('-a', Option::WithAllRules->getShorthand());
        self::assertSame('-q', Option::Quiet->getShorthand());
        self::assertSame('-v', Option::Version->getShorthand());
    }

    #[Test]
    public function fullValues(): void
    {
        self::assertSame('--help', Option::Help->getFull());
        self::assertSame('--config', Option::Config->getFull());
        self::assertSame('--dry-run', Option::DryRun->getFull());
        self::assertSame('--allow-risky', Option::AllowRisky->getFull());
        self::assertSame('--with-all-rules', Option::WithAllRules->getFull());
        self::assertSame('--quiet', Option::Quiet->getFull());
        self::assertSame('--version', Option::Version->getFull());
    }

    #[DataProvider('provideGetFullCases')]
    #[Test]
    public function getFull(Option $option, string $full): void
    {
        self::assertSame($full, $option->getFull());
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideGetFullCases(): iterable
    {
        yield [Option::Help, '--help'];
        yield [Option::Config, '--config'];
        yield [Option::DryRun, '--dry-run'];
        yield [Option::AllowRisky, '--allow-risky'];
        yield [Option::Quiet, '--quiet'];
        yield [Option::Version, '--version'];
    }

    #[Test]
    public function descriptionValues(): void
    {
        self::assertSame('Display help for the command.', Option::Help->getDescription());
        self::assertSame(
            'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.',
            Option::Config->getDescription()
        );
        self::assertSame(
            'Only calculate potential savings without modifying the files.',
            Option::DryRun->getDescription()
        );
        self::assertSame(
            'Explicitly enables risky rules, allowing them to be applied.',
            Option::AllowRisky->getDescription()
        );
        self::assertSame(
            'Enable all non-risky rules. Use --allow-risky to include risky rules as well.',
            Option::WithAllRules->getDescription()
        );
        self::assertSame(
            'Suppress all output except errors.',
            Option::Quiet->getDescription()
        );
        self::assertSame(
            'Display the version of the library.',
            Option::Version->getDescription()
        );
    }

    #[DataProvider('provideGetDescriptionCases')]
    #[Test]
    public function getDescription(Option $option, string $description): void
    {
        self::assertSame($description, $option->getDescription());
    }

    /**
     * @return iterable<array{Option, string}>
     */
    public static function provideGetDescriptionCases(): iterable
    {
        yield [Option::Help, 'Display help for the command.'];
        yield [Option::Config, 'Path to a JSON file with custom optimization rules. If not provided, all default optimizations will be applied.'];
        yield [Option::DryRun, 'Only calculate potential savings without modifying the files.'];
        yield [Option::AllowRisky, 'Explicitly enables risky rules, allowing them to be applied.'];
        yield [Option::Quiet, 'Suppress all output except errors.'];
        yield [Option::Version, 'Display the version of the library.'];
    }
}
