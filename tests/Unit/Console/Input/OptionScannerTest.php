<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\Option;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionScanner;
use MathiasReker\PhpSvgOptimizer\Console\Input\SuggestionTrait;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OptionScanner::class)]
#[CoversTrait(SuggestionTrait::class)]
#[UsesClass(Option::class)]
#[UsesClass(RuleSet::class)]
final class OptionScannerTest extends TestCase
{
    /**
     * @param list<string>                       $arguments
     * @param list<string|array{Option, string}> $expected
     *
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideScansPathsAndOptionsInTheOrderGivenCases')]
    #[Test]
    public function scansPathsAndOptionsInTheOrderGiven(array $arguments, array $expected): void
    {
        self::assertSame($expected, (new OptionScanner())->scan($arguments));
    }

    /**
     * @return iterable<string, array{list<string>, list<string|array{Option, string}>}>
     */
    public static function provideScansPathsAndOptionsInTheOrderGivenCases(): iterable
    {
        yield 'nothing' => [[], []];

        yield 'paths' => [['a.svg', 'icons'], ['a.svg', 'icons']];

        yield 'a flag' => [['--dry-run'], [[Option::DryRun, '']]];

        yield 'a short flag' => [['-q'], [[Option::Quiet, '']]];

        yield 'a long option with an attached value' => [['--config=file.json'], [[Option::Config, 'file.json']]];

        yield 'a long option with a separate value' => [['--config', 'file.json'], [[Option::Config, 'file.json']]];

        yield 'a short option with a separate value' => [['-c', 'file.json'], [[Option::Config, 'file.json']]];

        yield 'a short option with an attached value' => [['-cfile.json'], [[Option::Config, 'file.json']]];

        yield 'a short option with an equals sign' => [['-c=file.json'], [[Option::Config, 'file.json']]];

        yield 'a value that contains an equals sign' => [['--config={"a":"b=c"}'], [[Option::Config, '{"a":"b=c"}']]];

        yield 'combined short flags' => [['-dq'], [[Option::DryRun, ''], [Option::Quiet, '']]];

        yield 'combined short flags ending in an option' => [['-dqs', 'security'], [[Option::DryRun, ''], [Option::Quiet, ''], [Option::Sets, 'security']]];

        yield 'combined short flags with an attached value' => [['-dsminification'], [[Option::DryRun, ''], [Option::Sets, 'minification']]];

        yield 'options after paths' => [['a.svg', '--dry-run', 'b.svg'], ['a.svg', [Option::DryRun, ''], 'b.svg']];

        yield 'a path after a value option with an attached value' => [['--rules=removeComments', 'a.svg'], [[Option::Rules, 'removeComments'], 'a.svg']];

        yield 'a path after a flag' => [['--dry-run', 'a.svg'], [[Option::DryRun, ''], 'a.svg']];

        yield 'the end of options' => [['--dry-run', '--', '--not-an-option', '-x'], [[Option::DryRun, ''], '--not-an-option', '-x']];

        yield 'the end of options without paths' => [['--'], []];

        yield 'a repeated option' => [['-s', 'security', '-s', 'cleanup'], [[Option::Sets, 'security'], [Option::Sets, 'cleanup']]];

        yield 'an empty argument' => [[''], ['']];
    }

    /**
     * @param list<string> $arguments
     *
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideScanRejectsInvalidOptionsCases')]
    #[Test]
    public function scanRejectsInvalidOptions(array $arguments, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new OptionScanner())->scan($arguments);
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideScanRejectsInvalidOptionsCases(): iterable
    {
        yield 'an unknown long option' => [['--nope'], 'Unknown option "--nope". Run with --help to see valid options.'];

        yield 'an unknown long option with a suggestion' => [['--dryrun'], 'Unknown option "--dryrun". Did you mean "--dry-run"? Run with --help to see valid options.'];

        yield 'an unknown short option' => [['-x'], 'Unknown option "-x". Run with --help to see valid options.'];

        yield 'an unknown short option in a cluster' => [['-dx'], 'Unknown option "-x". Run with --help to see valid options.'];

        yield 'a lone dash' => [['-'], 'Unknown option "-". Run with --help to see valid options.'];

        yield 'a removed long option' => [['--with-all-rules'], 'Option "--with-all-rules" was removed; without --sets, --rules or a configuration the default rules run.'];

        yield 'a removed short option' => [['-a'], 'Option "-a" was removed; without --sets, --rules or a configuration the default rules run.'];

        yield 'a flag with a value' => [['--dry-run=1'], 'Option "--dry-run" does not take a value.'];

        yield 'a value after a short flag in a cluster' => [['-dq=1'], 'Unknown option "-=". Run with --help to see valid options.'];

        yield 'an option without a value at the end' => [['--config'], 'Option "--config" requires a value.'];

        yield 'an option with an empty attached value' => [['--config='], 'Option "--config" requires a value.'];

        yield 'an option followed by an empty argument' => [['--config', ''], 'Option "--config" requires a value.'];

        yield 'a short option without a value' => [['-r'], 'Option "-r" requires a value.'];

        yield 'an option followed by another option' => [['-c', '--dry-run'], 'Option "-c" requires a value.'];

        yield 'an option followed by the end of options' => [['--rules', '--', 'a.svg'], 'Option "--rules" requires a value.'];

        yield 'an option with an empty value in a cluster' => [['-dc='], 'Option "-c" requires a value.'];

        yield 'an empty attached value does not take the next argument' => [['--rules=', 'a.svg'], 'Option "--rules" requires a value.'];
    }
}
