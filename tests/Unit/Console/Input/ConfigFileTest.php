<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\ConfigFile;
use MathiasReker\PhpSvgOptimizer\Console\Input\RuleNameTrait;
use MathiasReker\PhpSvgOptimizer\Console\Input\SuggestionTrait;
use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;
use MathiasReker\PhpSvgOptimizer\Tests\Double\TemporaryDirectoryTrait;
use MathiasReker\PhpSvgOptimizer\Tests\Double\UnreadableFileStreamWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ConfigFile::class)]
#[CoversTrait(RuleNameTrait::class)]
#[CoversTrait(SuggestionTrait::class)]
final class ConfigFileTest extends TestCase
{
    use TemporaryDirectoryTrait;

    #[Test]
    public function emptyHasNothingSelected(): void
    {
        $configFile = ConfigFile::empty();

        self::assertSame([], $configFile->getRules());
        self::assertSame([], $configFile->getExcludedRules());
        self::assertSame([], $configFile->getRuleSets());
        self::assertFalse($configFile->isDefaultSelected());
        self::assertFalse($configFile->isRiskyAllowed());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromJsonReadsEveryKey(): void
    {
        $configFile = ConfigFile::fromJson('{"$schema": "https://example.com/schema.json", "sets": ["default", "security"], "rules": {"removeUnusedIds": true, "sortAttributes": false, "minifyColors": true}, "allowRisky": true}');

        self::assertSame([Rule::RemoveUnusedIds, Rule::MinifyColors], $configFile->getRules());
        self::assertSame([Rule::SortAttributes], $configFile->getExcludedRules());
        self::assertSame([RuleSet::Security], $configFile->getRuleSets());
        self::assertTrue($configFile->isDefaultSelected());
        self::assertTrue($configFile->isRiskyAllowed());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromJsonAcceptsAnEmptyObject(): void
    {
        $configFile = ConfigFile::fromJson('{}');

        self::assertSame([], $configFile->getRules());
        self::assertFalse($configFile->isRiskyAllowed());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromJsonAcceptsAnEmptyRulesObject(): void
    {
        self::assertSame([], ConfigFile::fromJson('{"rules": {}, "sets": []}')->getRules());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromJsonSkipsAByteOrderMark(): void
    {
        self::assertTrue(ConfigFile::fromJson("\xEF\xBB\xBF{\"allowRisky\": true}")->isRiskyAllowed());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromFileReadsTheFile(): void
    {
        $path = $this->createTemporaryDirectory() . '/svg-optimizer.json';
        file_put_contents($path, '{"sets": ["cleanup"], "rules": {"removeComments": false}}');

        $configFile = ConfigFile::fromFile($path);

        self::assertSame([RuleSet::Cleanup], $configFile->getRuleSets());
        self::assertSame([Rule::RemoveComments], $configFile->getExcludedRules());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromFileNamesTheFileInTheErrors(): void
    {
        $path = $this->createTemporaryDirectory() . '/svg-optimizer.json';
        file_put_contents($path, '{"rules": {"banana": true}}');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid configuration file "%s": Unknown rule "banana".', $path));

        ConfigFile::fromFile($path);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromFileRejectsAMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file "missing.json" does not exist.');

        ConfigFile::fromFile('missing.json');
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromFileRejectsADirectory(): void
    {
        $directory = $this->createTemporaryDirectory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Configuration "%s" is a directory, not a file.', $directory));

        ConfigFile::fromFile($directory);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function fromFileRejectsAFileThatCannotBeRead(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file "unreadable://file.svg" cannot be read.');

        UnreadableFileStreamWrapper::run(static fn (string $path): ConfigFile => ConfigFile::fromFile($path));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideFromJsonRejectsAnInvalidConfigurationCases')]
    #[Test]
    public function fromJsonRejectsAnInvalidConfiguration(string $json, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        ConfigFile::fromJson($json);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideFromJsonRejectsAnInvalidConfigurationCases(): iterable
    {
        yield 'invalid JSON' => ['{"rules": ', 'Invalid inline configuration: The JSON is invalid: Syntax error'];

        yield 'an empty string' => ['', 'Invalid inline configuration: The JSON is invalid:'];

        yield 'a list' => ['[]', 'Invalid inline configuration: The configuration must be a JSON object.'];

        yield 'a scalar' => ['true', 'Invalid inline configuration: The configuration must be a JSON object.'];

        yield 'an unknown key' => ['{"banana": 1}', 'Invalid inline configuration: Unknown key "banana". Valid keys are: $schema, sets, rules, allowRisky.'];

        yield 'a misspelled key' => ['{"allowrisky": true}', 'Unknown key "allowrisky". Did you mean "allowRisky"? Valid keys are:'];

        yield 'a numeric key' => ['{"0": true}', 'Unknown key "0".'];

        yield 'the 9.x format' => ['{"removeComments": true}', 'Invalid inline configuration: The 9.x format is no longer supported: "removeComments" belongs below "rules". See UPGRADE-10.0.md.'];

        yield 'the 9.x format with a renamed rule' => ['{"convertColorsToHex": true, "sets": ["security"]}', 'The 9.x format is no longer supported: "convertColorsToHex" belongs below "rules".'];

        yield 'a rule name that is not a boolean' => ['{"removeComments": "yes"}', 'Unknown key "removeComments".'];

        yield 'an unknown rule' => ['{"rules": {"banana": true}}', 'Invalid inline configuration: Unknown rule "banana".'];

        yield 'a rule of 9.x' => ['{"rules": {"scopeSvgStyles": true}}', 'Rule "scopeSvgStyles" was renamed to "prefixIdsAndClasses".'];

        yield 'a numeric rule name' => ['{"rules": {"0": true}}', 'Unknown rule "0".'];

        yield 'a rule that is not a boolean' => ['{"rules": {"removeComments": "yes"}}', 'The value of rule "removeComments" must be true or false.'];

        yield 'a nested rule value' => ['{"rules": {"removeComments": {"a": true}}}', 'The value of rule "removeComments" must be true or false.'];

        yield 'rules that are not an object' => ['{"rules": ["removeComments"]}', 'The value of "rules" must be an object.'];

        yield 'rules that are a boolean' => ['{"rules": true}', 'The value of "rules" must be an object.'];

        yield 'sets that are not a list' => ['{"sets": "security"}', 'The value of "sets" must be a list of strings.'];

        yield 'sets that are an object' => ['{"sets": {"a": "security"}}', 'The value of "sets" must be a list of strings.'];

        yield 'a set that is not a string' => ['{"sets": [1]}', 'The value of "sets" must be a list of strings.'];

        yield 'a nested set' => ['{"sets": [["security"]]}', 'The value of "sets" must be a list of strings.'];

        yield 'an unknown set' => ['{"sets": ["banana"]}', 'Unknown rule set "banana".'];

        yield 'allowRisky that is not a boolean' => ['{"allowRisky": "yes"}', 'The value of "allowRisky" must be true or false.'];

        yield 'a schema that is not a string' => ['{"$schema": 1}', 'The value of "$schema" must be a string.'];
    }
}
