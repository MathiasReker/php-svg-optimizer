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
    #[DataProvider('provideOptionCases')]
    #[Test]
    public function testOption(
        Option $option,
        string $value,
        string $shorthand,
        string $full,
        string $description,
    ): void {
        self::assertSame($value, $option->value);
        self::assertSame($shorthand, $option->getShorthand());
        self::assertSame($full, $option->getFull());
        self::assertSame($description, $option->getDescription());
    }

    /**
     * @return \Generator<array{Option, string, string, string, string}>
     */
    public static function provideOptionCases(): iterable
    {
        yield 'Help' => [
            Option::Help,
            'help',
            '-h',
            '--help',
            'Display help for the command.',
        ];

        yield 'Config' => [
            Option::Config,
            'config',
            '-c',
            '--config',
            'Path to a JSON file, or a raw JSON string, with custom optimization rules. If not provided, all default optimizations will be applied.',
        ];

        yield 'DryRun' => [
            Option::DryRun,
            'dry-run',
            '-d',
            '--dry-run',
            'Only calculate potential savings without modifying the files.',
        ];

        yield 'AllowRisky' => [
            Option::AllowRisky,
            'allow-risky',
            '-r',
            '--allow-risky',
            'Explicitly enables risky rules, allowing them to be applied.',
        ];

        yield 'WithAllRules' => [
            Option::WithAllRules,
            'with-all-rules',
            '-a',
            '--with-all-rules',
            'Enable all non-risky rules. Use --allow-risky to include risky rules as well.',
        ];

        yield 'Quiet' => [
            Option::Quiet,
            'quiet',
            '-q',
            '--quiet',
            'Suppress all output except errors.',
        ];

        yield 'Version' => [
            Option::Version,
            'version',
            '-v',
            '--version',
            'Display the version of the library.',
        ];
    }
}
