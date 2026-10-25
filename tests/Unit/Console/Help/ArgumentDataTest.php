<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Console\Help;

use MathiasReker\PhpSvgOptimizer\Console\Help\ArgumentData;
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
#[CoversClass(ArgumentData::class)]
#[CoversClass(CliOption::class)]
#[CoversClass(Command::class)]
#[CoversClass(CommandHelp::class)]
#[CoversClass(ExampleCommand::class)]
#[CoversClass(Option::class)]
final class ArgumentDataTest extends TestCase
{
    private ArgumentData $argumentData;

    #[Test]
    public function getFormat(): void
    {
        self::assertSame(
            'vendor/bin/svg-optimizer [options] process <path1> <path2> ...',
            $this->argumentData->getFormat()
        );
    }

    #[Test]
    public function getCommands(): void
    {
        $commands = $this->argumentData->getCommands();

        self::assertCount(1, $commands);
        self::assertArrayHasKey(Command::Process->value, $commands);

        $processCommand = $commands[Command::Process->value];
        self::assertSame(Command::Process->getTitle(), $processCommand->getTitle());
        self::assertSame(Command::Process->getDescription(), $processCommand->getDescription());
    }

    #[Test]
    public function getOptions(): void
    {
        $options = $this->argumentData->getOptions();

        self::assertCount(\count(Option::cases()), $options);

        foreach (Option::cases() as $option) {
            self::assertArrayHasKey($option->value, $options);
            $argumentOption = $options[$option->value];
            self::assertSame($option->getShorthand(), $argumentOption->getShorthand());
            self::assertSame($option->getFull(), $argumentOption->getFull());
            self::assertSame($option->getDescription(), $argumentOption->getDescription());
        }
    }

    #[Test]
    public function getExamples(): void
    {
        $examples = $this->argumentData->getExamples();

        self::assertCount(6, $examples);

        $expectedCommands = [
            'vendor/bin/svg-optimizer --with-all-rules --dry-run process /path/to/svgs',
            'vendor/bin/svg-optimizer --config=config.json process /path/to/file.svg',
            'vendor/bin/svg-optimizer --quiet --with-all-rules process /path/to/file.svg',
            'vendor/bin/svg-optimizer --with-all-rules --allow-risky process /path/to/file.svg',
            'vendor/bin/svg-optimizer --with-all-rules process /path/to/file.svg',
            'vendor/bin/svg-optimizer --set=security,minification process /path/to/svgs',
        ];

        foreach ($examples as $key => $example) {
            self::assertSame($expectedCommands[$key], $example->getCommand());
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideGetOptionCases')]
    #[Test]
    public function getOption(string $optionValue, string $expectedFullName): void
    {
        $cliOption = $this->argumentData->getOption($optionValue);
        self::assertSame($expectedFullName, $cliOption->getFull());
    }

    /**
     * @return list<list<string>>
     */
    public static function provideGetOptionCases(): iterable
    {
        $options = [];
        foreach (Option::cases() as $option) {
            $options[] = [$option->value, $option->getFull()];
        }

        return $options;
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[DataProvider('provideGetOptionByNameCases')]
    #[Test]
    public function getOptionByName(string $optionName, string $expectedFullName): void
    {
        $cliOption = $this->argumentData->getOptionByName($optionName);
        self::assertSame($expectedFullName, $cliOption->getFull());
    }

    /**
     * @return list<list<string>>
     */
    public static function provideGetOptionByNameCases(): iterable
    {
        $options = [];
        foreach (Option::cases() as $option) {
            $options[] = [$option->getFull(), $option->getFull()];

            if ('' !== $option->getShorthand()) {
                $options[] = [$option->getShorthand(), $option->getFull()];
            }
        }

        return $options;
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionThrowsExceptionForUnknownOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->argumentData->getOption('unknown');
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionByNameThrowsExceptionForUnknownName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->argumentData->getOptionByName('unknown');
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->argumentData = new ArgumentData();
    }
}
