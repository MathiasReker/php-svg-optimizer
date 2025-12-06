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
use MathiasReker\PhpSvgOptimizer\ValueObject\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommandValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\OptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentParser::class)]
#[CoversClass(ArgumentOptionValueObject::class)]
#[CoversClass(Option::class)]
#[CoversClass(ExampleCommandValueObject::class)]
#[CoversClass(ArgumentData::class)]
#[CoversClass(Command::class)]
#[CoversClass(OptionValueObject::class)]
#[CoversClass(FileCollector::class)]
#[CoversClass(Finder::class)]
final class ArgumentParserTest extends TestCase
{
    /**
     * The index of the next positional argument.
     */
    private const int EXPECTED_POSITIONAL_ARGUMENT_INDEX = 2;

    private const int EXPECTED_POSITIONAL_ARGUMENT_START_INDEX = 3;

    private const array EXAMPLE_ARGS = [
        'vendor/bin/svg-optimizer',
        '--config=config.json',
        'process',
        '/path/to/file.svg',
    ];

    private ArgumentParser $argumentParser;

    public function testHasOptionReturnsFalseIfOptionDoesNotExist(): void
    {
        $hasDryRunOption = $this->argumentParser->hasOption(Option::DRY_RUN);
        self::assertFalse($hasDryRunOption);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetOptionReturnsCorrectValue(): void
    {
        $configOptionValue = $this->argumentParser->getOption(Option::CONFIG);
        self::assertSame('config.json', $configOptionValue);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetOptionThrowsExceptionIfOptionDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "dry-run" not found in the command-line arguments.');

        $this->argumentParser->getOption(Option::DRY_RUN);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetNextPositionalArgumentIndexReturnsCorrectIndex(): void
    {
        $index = $this->argumentParser->getArgumentIndex();
        self::assertSame(self::EXPECTED_POSITIONAL_ARGUMENT_INDEX, $index);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetNextPositionalArgumentStartIndex(): void
    {
        $index = $this->argumentParser->getArgumentStartIndex();
        self::assertSame(self::EXPECTED_POSITIONAL_ARGUMENT_START_INDEX, $index);
    }

    public function testHasOptionReturnsTrueForVersionOption(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '-v',
        ];

        $argumentParser = new ArgumentParser($args);

        $hasVersionOption = $argumentParser->hasOption(Option::VERSION);
        self::assertTrue($hasVersionOption);
    }

    public function testHasOptionIgnoresNonOptionArguments(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertFalse($argumentParser->hasOption(Option::CONFIG));
    }

    public function testHasInvalidOptionArguments(): void
    {
        $args = [
            'foo',
            'bar',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertFalse($argumentParser->hasOption(Option::CONFIG));
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testEmptyConfigOption(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--config=',
        ];

        $argumentParser = new ArgumentParser($args);

        $value = $argumentParser->getOption(Option::CONFIG);

        self::assertSame('', $value);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testInvalidOption(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--config=',
            '--foo=',
        ];

        $argumentParser = new ArgumentParser($args);

        $value = $argumentParser->getOption(Option::CONFIG);

        self::assertSame('', $value);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetOptionHandlesEqualsInValue(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--config=foo=bar=baz',
        ];

        $argumentParser = new ArgumentParser($args);

        $value = $argumentParser->getOption(Option::CONFIG);

        self::assertSame('foo=bar=baz', $value);
    }

    public function testParseIsEmpty(): void
    {
        $argumentParser = new ArgumentParser([]);

        self::assertTrue($argumentParser->isEmpty());
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testGetPathsThrowsIfNoSvgFilesFound(): void
    {
        // Create temporary directory with no SVG files
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
    public function testGetArgumentIndexThrowsExceptionWhenNoPositionalArg(): void
    {
        $args = ['vendor/bin/svg-optimizer', '--config=config.json', '--dry-run'];

        $argumentParser = new ArgumentParser($args);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please follow the following format:');

        $argumentParser->getArgumentIndex();
    }

    public function testHasOptionReturnsFalseOnInvalidOptionName(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--unknown-option=value',
            'process',
            '/path/to/file.svg',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertFalse($argumentParser->hasOption(Option::CONFIG));
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testValidateOptionsDoesNotThrowWithValidOptions(): void
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
    public function testValidateOptionsThrowsExceptionForUnknownOption(): void
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
    public function testValidateOptionsAcceptsShorthandOptions(): void
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

    public function testHasOptionReturnsTrueForWithAllRulesLong(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '--with-all-rules',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertTrue($argumentParser->hasOption(Option::WITH_ALL_RULES));
    }

    public function testHasOptionReturnsTrueForWithAllRulesShort(): void
    {
        $args = [
            'vendor/bin/svg-optimizer',
            '-a',
        ];

        $argumentParser = new ArgumentParser($args);

        self::assertTrue($argumentParser->hasOption(Option::WITH_ALL_RULES));
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function testValidateOptionsAcceptsWithAllRules(): void
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
    public function testValidateOptionsAcceptsWithAllRulesShort(): void
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

    #[\Override]
    protected function setUp(): void
    {
        $this->argumentParser = new ArgumentParser(self::EXAMPLE_ARGS);
    }
}
