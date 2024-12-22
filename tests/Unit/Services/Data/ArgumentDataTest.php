<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MathiasReker\PhpSvgOptimizer\Tests\Unit\Services\Data;

use MathiasReker\PhpSvgOptimizer\Services\Data\ArgumentData;
use MathiasReker\PhpSvgOptimizer\ValueObjects\ArgumentOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObjects\CommandOptionValueObject;
use MathiasReker\PhpSvgOptimizer\ValueObjects\ExampleCommandValueObject;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ArgumentData::class)]
#[CoversClass(CommandOptionValueObject::class)]
#[CoversClass(ArgumentOptionValueObject::class)]
#[CoversClass(ExampleCommandValueObject::class)]
final class ArgumentDataTest extends TestCase
{
    private const string HELP_OPTION = 'help';

    private const string PROCESS_COMMAND = 'process';

    private const string EXAMPLE_COMMAND = 'vendor/bin/svg-optimizer --dry-run process /path/to/svgs';

    private ArgumentData $argumentData;

    public function testGetOptions(): void
    {
        $options = $this->argumentData->getOptions();

        Assert::assertArrayHasKey(self::HELP_OPTION, $options);
        $helpOption = $options[self::HELP_OPTION];

        Assert::assertSame('-h', $helpOption->getShorthand());
        Assert::assertSame('--help', $helpOption->getFull());
        Assert::assertSame('Display help for the command.', $helpOption->getDescription());
    }

    public function testGetCommands(): void
    {
        $commands = $this->argumentData->getCommands();

        Assert::assertArrayHasKey(self::PROCESS_COMMAND, $commands);
        $processCommand = $commands[self::PROCESS_COMMAND];

        Assert::assertSame('Process', $processCommand->getTitle());
        Assert::assertSame('Provide a list of directories or files to process.', $processCommand->getDescription());
    }

    public function testGetOption(): void
    {
        $option = $this->argumentData->getOption(self::HELP_OPTION);

        Assert::assertInstanceOf(ArgumentOptionValueObject::class, $option);
        Assert::assertSame('-h', $option->getShorthand());
        Assert::assertSame('--help', $option->getFull());
        Assert::assertSame('Display help for the command.', $option->getDescription());
    }

    public function testGetExamples(): void
    {
        $examples = $this->argumentData->getExamples();

        Assert::assertCount(3, $examples);
        $example = $examples[0];

        Assert::assertSame(self::EXAMPLE_COMMAND, $example->getCommand());
    }

    public function testGetFormat(): void
    {
        $format = $this->argumentData->getFormat();

        Assert::assertSame('vendor/bin/svg-optimizer [options] process <path1> <path2> ...', $format);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->argumentData = new ArgumentData();
    }
}
