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
    private const int EXPECTED_EXAMPLES_COUNT = 4;

    /**
     * An example command that should be present in the argument data.
     * This is used to verify that the example commands are correctly set up.
     */
    private const string EXAMPLE_COMMAND = 'vendor/bin/svg-optimizer --dry-run process /path/to/svgs';

    private ArgumentData $argumentData;

    public function testGetOptions(): void
    {
        $options = $this->argumentData->getOptions();

        self::assertArrayHasKey(Option::HELP->value, $options);
        $helpOption = $options[Option::HELP->value];

        self::assertSame(Option::HELP->getShorthand(), $helpOption->getShorthand());
        self::assertSame(Option::HELP->getFull(), $helpOption->getFull());
        self::assertSame(Option::HELP->getDescription(), $helpOption->getDescription());
    }

    public function testGetCommands(): void
    {
        $commands = $this->argumentData->getCommands();

        self::assertArrayHasKey(Command::PROCESS->value, $commands);
        $processCommand = $commands[Command::PROCESS->value];

        self::assertSame(Command::PROCESS->getTitle(), $processCommand->getTitle());
        self::assertSame(Command::PROCESS->getDescription(), $processCommand->getDescription());
    }

    /**
     * @throws \InvalidArgumentException If the option does not exist
     */
    public function testGetOption(): void
    {
        $argumentOptionValueObject = $this->argumentData->getOption(Option::HELP->value);

        self::assertSame(Option::HELP->getShorthand(), $argumentOptionValueObject->getShorthand());
        self::assertSame(Option::HELP->getFull(), $argumentOptionValueObject->getFull());
        self::assertSame(Option::HELP->getDescription(), $argumentOptionValueObject->getDescription());
    }

    public function testGetExamples(): void
    {
        $examples = $this->argumentData->getExamples();

        self::assertCount(self::EXPECTED_EXAMPLES_COUNT, $examples);
        $example = $examples[0];

        self::assertSame(self::EXAMPLE_COMMAND, $example->getCommand());
    }

    public function testGetFormat(): void
    {
        $format = $this->argumentData->getFormat();

        self::assertSame('vendor/bin/svg-optimizer [options] process <path1> <path2> ...', $format);
    }

    /**
     * @throws \InvalidArgumentException If the option does not exist
     */
    public function testGetOptionThrowsForUnknownOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "unknown" not found.');

        $this->argumentData->getOption('unknown');
    }

    /**
     * @throws \InvalidArgumentException If the option does not exist
     */
    public function testGetOptionByNameReturnsCorrectOption(): void
    {
        $argumentOptionValueObject = $this->argumentData->getOptionByName(Option::HELP->getFull());
        self::assertSame(Option::HELP->getFull(), $argumentOptionValueObject->getFull());

        $dryRunOption = $this->argumentData->getOptionByName(Option::DRY_RUN->getShorthand());
        self::assertSame(Option::DRY_RUN->getFull(), $dryRunOption->getFull());
    }

    /**
     * @throws \InvalidArgumentException If the option does not exist
     */
    public function testGetOptionByNameThrowsForUnknownName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "nonexistent" not found.');

        $this->argumentData->getOptionByName('nonexistent');
    }

    public function testExamplesContainCorrectCommands(): void
    {
        $examples = $this->argumentData->getExamples();

        self::assertStringContainsString(self::EXAMPLE_COMMAND, $examples[0]->getCommand());
        self::assertStringContainsString('vendor/bin/svg-optimizer --config config.json process /path/to/file.svg', $examples[1]->getCommand());
        self::assertStringContainsString('vendor/bin/svg-optimizer --quiet process /path/to/file.svg', $examples[2]->getCommand());
    }

    public function testGetOptionsContainsAllDefinedOptions(): void
    {
        $options = $this->argumentData->getOptions();

        foreach (Option::cases() as $option) {
            self::assertArrayHasKey($option->value, $options);
        }
    }

    public function testGetCommandsContainsOnlyProcess(): void
    {
        $commands = $this->argumentData->getCommands();

        self::assertCount(1, $commands);
        self::assertArrayHasKey(Command::PROCESS->value, $commands);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->argumentData = new ArgumentData();
    }
}
