<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Input;

use MathiasReker\PhpSvgOptimizer\Console\Input\ArgumentParser;
use MathiasReker\PhpSvgOptimizer\Console\Input\FileCollector;
use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Service\Filesystem\Finder;
use MathiasReker\PhpSvgOptimizer\Type\Command;
use MathiasReker\PhpSvgOptimizer\Type\Option;
use MathiasReker\PhpSvgOptimizer\ValueObject\CliOption;
use MathiasReker\PhpSvgOptimizer\ValueObject\CommandHelp;
use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommand;
use PHPUnit\Framework\Attributes\CoversClass;
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
    private const int EXPECTED_POSITIONAL_ARGUMENT_INDEX = 2;

    private const int EXPECTED_POSITIONAL_ARGUMENT_START_INDEX = 3;

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
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getNextPositionalArgumentIndexReturnsCorrectIndex(): void
    {
        $index = $this->argumentParser->getArgumentIndex();
        self::assertSame(self::EXPECTED_POSITIONAL_ARGUMENT_INDEX, $index);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getNextPositionalArgumentStartIndex(): void
    {
        $index = $this->argumentParser->getArgumentStartIndex();
        self::assertSame(self::EXPECTED_POSITIONAL_ARGUMENT_START_INDEX, $index);
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
        $args = [
            'vendor/bin/svg-optimizer',
            '--config=',
        ];

        $argumentParser = new ArgumentParser($args);

        $value = $argumentParser->getOption(Option::Config);

        self::assertSame('', $value);
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function invalidOption(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--config=',
            '--foo=',
        ];

        $argumentParser = new ArgumentParser($args);

        $value = $argumentParser->getOption(Option::Config);

        self::assertSame('', $value);
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
    public function getArgumentIndexThrowsExceptionWhenNoPositionalArg(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--config=config.json', '--dry-run'];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please follow the following format:');

        $argumentParser->getArgumentIndex();
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
            'onlypositional',
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
