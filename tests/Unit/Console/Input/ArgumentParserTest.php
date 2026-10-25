<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Console\Help\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\FileCollector;
use MathiasReker\PhpSvgOptimizer\Console\Type\Command;
use MathiasReker\PhpSvgOptimizer\Console\Type\Option;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CliOption;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\CommandHelp;
use MathiasReker\PhpSvgOptimizer\Console\ValueObject\ExampleCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentParser::class)]
#[CoversClass(CliOption::class)]
#[CoversClass(Option::class)]
#[CoversClass(ExampleCommand::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(Command::class)]
#[CoversClass(CommandHelp::class)]
#[CoversClass(FileCollector::class)]
#[CoversClass(Finder::class)]
final class ArgumentParserTest extends TestCase
{
    private const array EXAMPLE_ARGS = [
        'vendor/bin/svg-optimizer',
        '--config=config.json',
        'process',
        '/path/to/file.svg',
    ];

    private ArgumentParser $argumentParser;

    #[Test]
    public function hasOptionReturnsFalseIfOptionDoesNotExist(): void
    {
        $hasDryRunOption = $this->argumentParser->hasOption(Option::DryRun);
        self::assertFalse($hasDryRunOption);
    }

    #[Test]
    public function hasOptionDetectsAnOptionPresentAlongsideANonOptionPositionalArgument(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            '--dry-run',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertTrue($argumentParser->hasOption(Option::DryRun));
    }

    #[Test]
    public function hasOptionIgnoresTheScriptNameSlot(): void
    {
        $args = ['-v'];

        $argumentParser = new ArgumentParser($args);

        self::assertFalse($argumentParser->hasOption(Option::Version));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionReturnsCorrectValue(): void
    {
        $configOptionValue = $this->argumentParser->getOption(Option::Config);
        self::assertSame('config.json', $configOptionValue);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionThrowsExceptionIfOptionDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "dry-run" not found in the command-line arguments.');

        $this->argumentParser->getOption(Option::DryRun);
    }

    /**
     * @param list<string> $args The arguments after the script name
     *
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideGetOptionAcceptsEveryConfigValueFormCases')]
    #[Test]
    public function getOptionAcceptsEveryConfigValueForm(array $args): void
    {
        $argumentParser = new ArgumentParser(['vendor/bin/svg-optimizer', ...$args]);

        $argumentParser->validateOptions();

        self::assertSame('config.json', $argumentParser->getOption(Option::Config));
        self::assertTrue($argumentParser->hasOption(Option::DryRun));
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function provideGetOptionAcceptsEveryConfigValueFormCases(): iterable
    {
        yield 'long option with equals sign' => [['--config=config.json', '--dry-run', 'process', 'file.svg']];
        yield 'short option with equals sign' => [['-c=config.json', '--dry-run', 'process', 'file.svg']];
        yield 'long option with separate value' => [['--config', 'config.json', '--dry-run', 'process', 'file.svg']];
        yield 'short option with separate value' => [['-c', 'config.json', '--dry-run', 'process', 'file.svg']];
        yield 'options after the paths' => [['process', 'file.svg', '-d', '--config', 'config.json']];
        yield 'options between the command and the paths' => [['process', '-c', 'config.json', '-d', 'file.svg']];
        yield 'flag given twice' => [['-d', '--config=config.json', '--dry-run', 'process', 'file.svg']];
        yield 'value on a flag that takes none' => [['--dry-run=yes', '-c', 'config.json', 'process', 'file.svg']];
    }

    /**
     * @param list<string> $args
     *
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideValidateOptionsRejectsMalformedOptionsCases')]
    #[Test]
    public function validateOptionsRejectsMalformedOptions(array $args, string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Unknown option: "%s". Run with --help to see valid options.', $name));

        (new ArgumentParser(['vendor/bin/svg-optimizer', ...$args]))->validateOptions();
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function provideValidateOptionsRejectsMalformedOptionsCases(): iterable
    {
        yield 'combined short flags' => [['-dq', 'process', 'file.svg'], '-dq'];
        yield 'single dash' => [['process', '-'], '-'];
        yield 'uppercase long option' => [['--DRY-RUN', 'process', 'file.svg'], '--DRY-RUN'];
        yield 'long option with one dash' => [['-dry-run', 'process', 'file.svg'], '-dry-run'];
        yield 'short option with two dashes' => [['--d', 'process', 'file.svg'], '--d'];
        yield 'misspelled option' => [['--dryrun', 'process', 'file.svg'], '--dryrun'];
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionTakesTheFirstOfRepeatedConfigOptions(): void
    {
        $argumentParser = new ArgumentParser(['vendor/bin/svg-optimizer', '-c', 'first.json', '--config=second.json', 'process', 'file.svg']);

        self::assertSame('first.json', $argumentParser->getOption(Option::Config));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionTakesAJsonValueContainingSpacesAndEqualsSigns(): void
    {
        $argumentParser = new ArgumentParser(['vendor/bin/svg-optimizer', '--config={"a": true, "b=c": false}', 'process', 'file.svg']);

        self::assertSame('{"a": true, "b=c": false}', $argumentParser->getOption(Option::Config));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsIgnoresOptionsAndConfigValuesAnywhereBeforeTheEndOfOptionsMarker(): void
    {
        $tempDir = sys_get_temp_dir() . '/options_anywhere_' . uniqid();
        mkdir($tempDir);
        $svgFile = $tempDir . '/file.svg';
        file_put_contents($svgFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $argumentParser = new ArgumentParser([
            'vendor/bin/svg-optimizer',
            '-c',
            '{"removeComments":true}',
            'process',
            $svgFile,
            '--dry-run',
            '--config',
            'config.json',
        ]);

        try {
            self::assertSame([(string) realpath($svgFile)], $argumentParser->getPaths());
            self::assertTrue($argumentParser->hasOption(Option::DryRun));
        } finally {
            unlink($svgFile);
            rmdir($tempDir);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionThrowsIfTheSeparateConfigValueIsTheEndOfOptionsMarker(): void
    {
        $argumentParser = new ArgumentParser(['vendor/bin/svg-optimizer', 'process', '--config', '--', 'file.svg']);

        self::assertFalse($argumentParser->hasOption(Option::DryRun));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "--config" requires a value.');

        $argumentParser->getOption(Option::Config);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsThrowsForAnUnknownCommand(): void
    {
        $argumentParser = new ArgumentParser(['vendor/bin/svg-optimizer', 'optimize', __FILE__]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown command "optimize". Please follow the following format: vendor/bin/svg-optimizer [options] process <path1> <path2> ...');

        $argumentParser->getPaths();
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsThrowsIfTheCommandIsAPath(): void
    {
        $argumentParser = new ArgumentParser(['vendor/bin/svg-optimizer', __FILE__]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Unknown command "%s".', __FILE__));

        $argumentParser->getPaths();
    }

    #[Test]
    public function hasOptionReturnsTrueForVersionOption(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '-v',
        ];

        $argumentParser = new ArgumentParser($args);

        $hasVersionOption = $argumentParser->hasOption(Option::Version);
        self::assertTrue($hasVersionOption);
    }

    #[Test]
    public function hasOptionIgnoresNonOptionArguments(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertFalse($argumentParser->hasOption(Option::Config));
    }

    #[Test]
    public function hasInvalidOptionArguments(): void
    {
        $args = [
            'foo',
            'bar',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertFalse($argumentParser->hasOption(Option::Config));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function emptyConfigOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "--config" requires a value.');

        (new ArgumentParser(['vendor/bin/svg-optimizer', '--config=']))->getOption(Option::Config);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function invalidOption(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--config=a.json',
            '--foo=',
        ];

        $argumentParser = new ArgumentParser($args);

        $value = $argumentParser->getOption(Option::Config);

        self::assertSame('a.json', $value);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionHandlesEqualsInValue(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--config=foo=bar=baz',
        ];

        $argumentParser = new ArgumentParser($args);

        $value = $argumentParser->getOption(Option::Config);

        self::assertSame('foo=bar=baz', $value);
    }

    #[Test]
    public function parseIsEmpty(): void
    {
        $argumentParser = new ArgumentParser([]);

        self::assertTrue($argumentParser->isEmpty());
    }

    #[Test]
    public function isEmptyReturnsFalseAtTheMinimumArgumentCountBoundary(): void
    {
        $argumentParser = new ArgumentParser(['vendor/bin/svg-optimizer', 'file.svg']);

        self::assertFalse($argumentParser->isEmpty());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsThrowsIfNoSvgFilesFound(): void
    {
        $tempDir = sys_get_temp_dir() . '/empty_dir_' . uniqid();
        mkdir($tempDir);

        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            $tempDir,
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No valid .svg files found to optimize.');

        try {
            $argumentParser->getPaths();
        } finally {
            rmdir($tempDir);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsThrowsWhenNoCommandIsGiven(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--config=config.json', '--dry-run'];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No command given. Please follow the following format:');

        $argumentParser->getPaths();
    }

    #[Test]
    public function hasOptionReturnsFalseOnInvalidOptionName(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--unknown-option=value',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertFalse($argumentParser->hasOption(Option::Config));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function validateOptionsDoesNotThrowWithValidOptions(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--config=config.json',
            '--dry-run',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectNotToPerformAssertions();
        $argumentParser->validateOptions();
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function validateOptionsThrowsExceptionForUnknownOption(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--invalid-option=value',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown option: "--invalid-option". Run with --help to see valid options.');

        $argumentParser->validateOptions();
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function validateOptionsAcceptsShorthandOptions(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '-d',
            '-c=config.json',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectNotToPerformAssertions();
        $argumentParser->validateOptions();
    }

    #[Test]
    public function hasOptionReturnsTrueForWithAllRulesLong(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--with-all-rules',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertTrue($argumentParser->hasOption(Option::WithAllRules));
    }

    #[Test]
    public function hasOptionReturnsTrueForWithAllRulesShort(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '-a',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertTrue($argumentParser->hasOption(Option::WithAllRules));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function validateOptionsAcceptsWithAllRules(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--with-all-rules',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectNotToPerformAssertions();
        $argumentParser->validateOptions();
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function validateOptionsAcceptsWithAllRulesShort(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '-a',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectNotToPerformAssertions();
        $argumentParser->validateOptions();
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionThrowsExceptionIfOptionHasNoValue(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--config',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "--config" requires a value.');

        $argumentParser->getOption(Option::Config);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsThrowsIfNoPositionalArgumentsFoundAfterCommand(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            'process',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No positional arguments found. Please provide at least one SVG file or directory.');

        $argumentParser->getPaths();
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsAllowsAFileNameStartingWithADashAfterTheEndOfOptionsMarker(): void
    {
        $tempDir = sys_get_temp_dir() . '/dash_file_' . uniqid();
        mkdir($tempDir);
        $dashFile = $tempDir . '/-weird-name.svg';
        file_put_contents($dashFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            '--',
            $dashFile,
        ];

        $argumentParser = new ArgumentParser($args);

        try {
            $svgFiles = $argumentParser->getPaths();

            self::assertSame([(string) realpath($dashFile)], $svgFiles);
        } finally {
            unlink($dashFile);
            rmdir($tempDir);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsKeepsThePathsGivenBeforeAndAfterTheEndOfOptionsMarker(): void
    {
        $tempDir = sys_get_temp_dir() . '/marker_paths_' . uniqid();
        mkdir($tempDir);
        $before = $tempDir . '/before.svg';
        $after = $tempDir . '/-after.svg';
        file_put_contents($before, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($after, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $argumentParser = new ArgumentParser(['vendor/bin/svg-optimizer', 'process', $before, '--', $after]);

        try {
            self::assertSame([(string) realpath($before), (string) realpath($after)], $argumentParser->getPaths());
        } finally {
            unlink($before);
            unlink($after);
            rmdir($tempDir);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function validateOptionsIgnoresArgumentsAfterTheEndOfOptionsMarker(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            '--',
            '-not-an-option.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectNotToPerformAssertions();
        $argumentParser->validateOptions();
    }

    #[Test]
    public function hasOptionIgnoresArgumentsAfterTheEndOfOptionsMarker(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            '--',
            '--dry-run',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertFalse($argumentParser->hasOption(Option::DryRun));
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsThrowsIfOnlyTheEndOfOptionsMarkerFollowsTheCommand(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            '--',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No positional arguments found. Please provide at least one SVG file or directory.');

        $argumentParser->getPaths();
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getPathsThrowsIfPathIsNotValidDirectoryOrFile(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            '/definitely/not/a/real/path/xyz123',
        ];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"/definitely/not/a/real/path/xyz123" is not a valid directory or file.');

        $argumentParser->getPaths();
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->argumentParser = new ArgumentParser(self::EXAMPLE_ARGS);
    }
}
