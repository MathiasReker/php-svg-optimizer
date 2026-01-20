<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Service\Data;

use MathiasReker\PhpSvgOptimizer\Service\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\Type\Command;
use MathiasReker\PhpSvgOptimizer\Type\Option;
use MathiasReker\PhpSvgOptimizer\ValueObject\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\ExampleCommandValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObject\OptionValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentData::class)]
#[CoversClass(Option::class)]
#[CoversClass(ArgumentOptionValueObject::class)]
#[CoversClass(ExampleCommandValueObject::class)]
#[CoversClass(Command::class)]
#[CoversClass(OptionValueObject::class)]
final class ArgumentDataTest extends TestCase
{
    /**
     * The expected number of examples in the argument data.
     */
    private const int EXPECTED_EXAMPLES_COUNT = 5;

    /**
     * An example command that should be present in the argument data.
     * This is used to verify that the example commands are correctly set up.
     */
    private const string EXAMPLE_COMMAND = 'vendor/bin/svg-optimizer --with-all-rules --dry-run process /path/to/svgs';

    private ArgumentData $argumentData;

    /**
     * @throws \InvalidArgumentException If the option does not exist
     */
    #[Test]
    public function getOptionThrowsForUnknownOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "unknown" not found.');

        $this->argumentData->getOption('unknown');
    }

    /**
     * @throws \InvalidArgumentException If the option does not exist
     */
    #[Test]
    public function getOption(): void
    {
        $argumentOptionValueObject = $this->argumentData->getOption(Option::Help->value);

        self::assertSame(Option::Help->getShorthand(), $argumentOptionValueObject->getShorthand());
        self::assertSame(Option::Help->getFull(), $argumentOptionValueObject->getFull());
        self::assertSame(Option::Help->getDescription(), $argumentOptionValueObject->getDescription());
    }

    /**
     * @throws \InvalidArgumentException If the option does not exist
     */
    #[Test]
    public function getOptionByNameReturnsCorrectOption(): void
    {
        $argumentOptionValueObject = $this->argumentData->getOptionByName(Option::Help->getFull());
        self::assertSame(Option::Help->getFull(), $argumentOptionValueObject->getFull());

        $dryRunOption = $this->argumentData->getOptionByName(Option::DryRun->getShorthand());
        self::assertSame(Option::DryRun->getFull(), $dryRunOption->getFull());
    }

    /**
     * @throws \InvalidArgumentException If the option does not exist
     */
    #[Test]
    public function getOptionByNameThrowsForUnknownName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "nonexistent" not found.');

        $this->argumentData->getOptionByName('nonexistent');
    }

    #[Test]
    public function examplesContainCorrectCommands(): void
    {
        $examples = $this->argumentData->getExamples();

        self::assertStringContainsString(self::EXAMPLE_COMMAND, $examples[0]->getCommand());
        self::assertStringContainsString('vendor/bin/svg-optimizer --config=config.json process /path/to/file.svg', $examples[1]->getCommand());
        self::assertStringContainsString('vendor/bin/svg-optimizer --quiet --with-all-rules process /path/to/file.svg', $examples[2]->getCommand());
    }

    #[Test]
    public function getExamples(): void
    {
        $examples = $this->argumentData->getExamples();

        self::assertCount(self::EXPECTED_EXAMPLES_COUNT, $examples);
        $example = $examples[0];

        self::assertSame(self::EXAMPLE_COMMAND, $example->getCommand());
    }

    #[Test]
    public function getOptionsContainsAllDefinedOptions(): void
    {
        $options = $this->argumentData->getOptions();

        foreach (Option::cases() as $option) {
            self::assertArrayHasKey($option->value, $options);
        }
    }

    #[Test]
    public function getOptions(): void
    {
        $options = $this->argumentData->getOptions();

        self::assertArrayHasKey(Option::Help->value, $options);
        $helpOption = $options[Option::Help->value];

        self::assertSame(Option::Help->getShorthand(), $helpOption->getShorthand());
        self::assertSame(Option::Help->getFull(), $helpOption->getFull());
        self::assertSame(Option::Help->getDescription(), $helpOption->getDescription());
    }

    #[Test]
    public function getCommandsContainsOnlyProcess(): void
    {
        $commands = $this->argumentData->getCommands();
        self::assertCount(1, $commands);
        self::assertArrayHasKey(Command::Process->value, $commands);
    }

    #[Test]
    public function getCommands(): void
    {
        $commands = $this->argumentData->getCommands();

        self::assertArrayHasKey(Command::Process->value, $commands);
        $processCommand = $commands[Command::Process->value];

        self::assertSame(Command::Process->getTitle(), $processCommand->getTitle());
        self::assertSame(Command::Process->getDescription(), $processCommand->getDescription());
    }

    #[Test]
    public function getOptionsContainsAllOptions(): void
    {
        $options = $this->argumentData->getOptions();
        foreach (Option::cases() as $option) {
            self::assertArrayHasKey($option->value, $options);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionReturnsCorrectOption(): void
    {
        $argumentOptionValueObject = $this->argumentData->getOption(Option::Help->value);
        self::assertSame(Option::Help->getFull(), $argumentOptionValueObject->getFull());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionThrowsExceptionForUnknownOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "unknown" not found.');
        $this->argumentData->getOption('unknown');
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionByNameReturnsCorrectOptionByShorthand(): void
    {
        $argumentOptionValueObject = $this->argumentData->getOptionByName(Option::DryRun->getShorthand());
        self::assertSame(Option::DryRun->getFull(), $argumentOptionValueObject->getFull());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionByNameReturnsCorrectOptionByFullName(): void
    {
        $argumentOptionValueObject = $this->argumentData->getOptionByName(Option::Help->getFull());
        self::assertSame(Option::Help->getFull(), $argumentOptionValueObject->getFull());
    }

    /**
     * @throws \InvalidArgumentException
     */
    #[Test]
    public function getOptionByNameThrowsExceptionForUnknownName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "nonexistent" not found.');
        $this->argumentData->getOptionByName('nonexistent');
    }

    #[Test]
    public function getExamplesReturnsExpectedCommands(): void
    {
        $examples = $this->argumentData->getExamples();

        self::assertCount(5, $examples);

        self::assertStringContainsString('--dry-run process /path/to/svgs', $examples[0]->getCommand());
        self::assertStringContainsString('--config=config.json process /path/to/file.svg', $examples[1]->getCommand());
        self::assertStringContainsString('--quiet --with-all-rules process /path/to/file.svg', $examples[2]->getCommand());
        self::assertStringContainsString('--allow-risky process /path/to/file.svg', $examples[3]->getCommand());
        self::assertStringContainsString('--with-all-rules process /path/to/file.svg', $examples[4]->getCommand());
    }

    #[Test]
    public function getFormatReturnsExpectedString(): void
    {
        $format = $this->argumentData->getFormat();
        self::assertSame('vendor/bin/svg-optimizer [options] process <path1> <path2> ...', $format);
    }

    #[Test]
    public function getFormat(): void
    {
        $format = $this->argumentData->getFormat();

        self::assertSame('vendor/bin/svg-optimizer [options] process <path1> <path2> ...', $format);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->argumentData = new ArgumentData();
    }
}
