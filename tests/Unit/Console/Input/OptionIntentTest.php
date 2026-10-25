<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Help\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\OptionIntent;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleSetParser;
use MathiasReker\PhpSvgOptimizer\Console\Type\Command;
use MathiasReker\PhpSvgOptimizer\Console\Type\Option;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CliOption;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandHelp;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\ExampleCommand;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(OptionIntent::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(ArgumentParser::class)]
#[CoversClass(CliOption::class)]
#[CoversClass(Command::class)]
#[CoversClass(CommandHelp::class)]
#[CoversClass(ExampleCommand::class)]
#[CoversClass(Option::class)]
#[CoversClass(RuleSetParser::class)]
final class OptionIntentTest extends TestCase
{
    #[Test]
    public function isDryRunOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--dry-run'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isDryRun());
    }

    #[Test]
    public function isDryRunOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isDryRun());
    }

    #[Test]
    public function isQuietOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--quiet'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isQuiet());
    }

    #[Test]
    public function isQuietOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isQuiet());
    }

    #[Test]
    public function isHelpOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--help'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isHelp());
    }

    #[Test]
    public function isHelpOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isHelp());
    }

    #[Test]
    public function isVersionOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--version'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isVersion());
    }

    #[Test]
    public function isVersionOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isVersion());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getConfigPathOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--config=/path/to/config'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertSame('/path/to/config', $optionIntent->getConfigPath());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getConfigPathOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertSame('', $optionIntent->getConfigPath());
    }

    #[Test]
    public function allowRiskyOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--allow-risky'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->allowRisky());
    }

    #[Test]
    public function allowRiskyOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->allowRisky());
    }

    #[Test]
    public function withAllRulesOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--with-all-rules'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->withAllRules());
    }

    #[Test]
    public function withAllRulesOptionIsNotSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->withAllRules());
    }

    #[Test]
    public function forceColorOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--ansi'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->forceColor());
        self::assertFalse($optionIntent->forceNoColor());
    }

    #[Test]
    public function forceNoColorOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--no-ansi'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->forceColor());
        self::assertTrue($optionIntent->forceNoColor());
    }

    #[Test]
    public function neitherColorOptionIsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->forceColor());
        self::assertFalse($optionIntent->forceNoColor());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function multipleOptionsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--dry-run', '--quiet', '--allow-risky', '--config=/path/to/config'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertTrue($optionIntent->isDryRun());
        self::assertTrue($optionIntent->isQuiet());
        self::assertTrue($optionIntent->allowRisky());
        self::assertSame('/path/to/config', $optionIntent->getConfigPath());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function noOptionsSet(): void
    {
        $args = ['vendor/bin/svg-optimizer'];
        $argumentParser = new ArgumentParser($args);

        $optionIntent = new OptionIntent($argumentParser);
        self::assertFalse($optionIntent->isDryRun());
        self::assertFalse($optionIntent->isQuiet());
        self::assertFalse($optionIntent->isHelp());
        self::assertFalse($optionIntent->isVersion());
        self::assertFalse($optionIntent->allowRisky());
        self::assertSame('', $optionIntent->getConfigPath());
        self::assertSame([], $optionIntent->getRuleSets());
    }

    /**
     * @param list<string>  $args     The arguments after the script name
     * @param list<RuleSet> $expected The expected rule sets
     *
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideGetRuleSetsCases')]
    #[Test]
    public function getRuleSets(array $args, array $expected): void
    {
        $optionIntent = new OptionIntent(new ArgumentParser(['vendor/bin/svg-optimizer', ...$args]));

        self::assertSame($expected, $optionIntent->getRuleSets());
    }

    /**
     * @return iterable<string, array{list<string>, list<RuleSet>}>
     */
    public static function provideGetRuleSetsCases(): iterable
    {
        yield 'single set' => [['--set=security'], [RuleSet::Security]];

        yield 'comma-separated sets' => [['--set=security,minification'], [RuleSet::Security, RuleSet::Minification]];

        yield 'spaces around the names' => [['--set', ' cleanup , structure '], [RuleSet::Cleanup, RuleSet::Structure]];

        yield 'repeated option' => [['-s', 'standards,conversion', '--dry-run', '--set=cleanup'], [RuleSet::Standards, RuleSet::Conversion, RuleSet::Cleanup]];

        yield 'every set' => [['-s=security,standards,cleanup,conversion,structure,minification'], RuleSet::cases()];
    }

    /**
     * @param list<string> $args The arguments after the script name
     *
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideGetRuleSetsThrowsForAnUnknownSetCases')]
    #[Test]
    public function getRuleSetsThrowsForAnUnknownSet(array $args, string $name): void
    {
        $optionIntent = new OptionIntent(new ArgumentParser(['vendor/bin/svg-optimizer', ...$args]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Unknown rule set "%s". Valid rule sets are: security, standards, cleanup, conversion, structure, minification.', $name));

        $optionIntent->getRuleSets();
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideGetRuleSetsThrowsForAnUnknownSetCases(): iterable
    {
        yield 'unknown name' => [['--set=lossless'], 'lossless'];

        yield 'unknown name after a known one' => [['--set=security,risky'], 'risky'];

        yield 'unknown name in a later option' => [['--set=security', '-s', 'Cleanup'], 'Cleanup'];

        yield 'empty name' => [['--set=security,,cleanup'], ''];
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getRuleSetsThrowsForASetOptionWithoutValue(): void
    {
        $optionIntent = new OptionIntent(new ArgumentParser(['vendor/bin/svg-optimizer', '--set=']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "--set" requires a value.');

        $optionIntent->getRuleSets();
    }
}
